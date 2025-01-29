<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

use App\Models\Task;
use App\Models\TaskView;
use App\Models\TaskType;
use App\Models\CustomField;
use App\Models\Status;
use App\Models\TaskPriority;
use App\Models\Group;
use App\Models\User;
use App\Models\TaskCustomField;
use App\Models\Workflow;
use App\Models\WorkflowStatusTransition;
use App\Helpers\GeneralHelper;
use App\Models\Sla;
use App\Models\TaskAttachment;
use App\Models\Project;
use ZipArchive;

class TaskController extends Controller
{
	public function index(Request $request)
	{
		$projects = Project::select('id', 'name', 'color', 'enabled')->get()->toArray();
		$task_types = TaskType::select('id', 'name', 'color', 'enabled')->get()->toArray();
		$statuses = Status::get()->toArray();
		$priorities = TaskPriority::get()->toArray();
		$users = User::get()->toArray();
		$groups = Group::get()->toArray();

		$task = new \App\Models\Task();
		$system_fields = $task->getAllTaskFields();
		$selected_fields = $system_fields;
		
		$custom_fields = CustomField::orderBy('field_type')->get()->toArray();
		foreach ($custom_fields as $custom_field) {
			$_EXCLUDED_FIELDS = ['File Upload'];
			if(!in_array($custom_field['name'], $_EXCLUDED_FIELDS))
			{
				$selected_fields[$custom_field['field_id']] = $custom_field['name'];
			}
		}

		$not_selected_fields = [];
		return view('tasks.index', [
			'projects' => $projects,
			'task_types' => $task_types,
			'custom_fields' => $custom_fields,
			'statuses' => $statuses,
			'priorities' => $priorities,
			'users' => $users,
			'groups' => $groups,
			'selected_fields' => $selected_fields,
			'not_selected_fields' => $not_selected_fields,
		]);
	}

	public function get_task_data(Request $request)
	{
		ini_set("memory_limit", '8192M');
		set_time_limit(300);

		$task = new \App\Models\Task();
		$system_fields = $task->getAllTaskFields();
		$_EXCLUDED_FIELDS = ['File Upload'];
		$custom_fields = CustomField::whereNotIn('field_type', $_EXCLUDED_FIELDS)->get()->toArray();

		$statuses = Status::select('id', 'name', 'color')->get()->toArray();
		$statuses_lkp = array_column($statuses, null, 'id');
		$priorities = TaskPriority::select('id', 'name', 'color')->get()->toArray();
		$priorities_lkp = array_column($priorities, null, 'id');
		$users = User::select('id', 'name')->get()->toArray();
		$users_lkp = array_column($users, 'name', 'id');
		$groups = Group::select('id', 'name')->get()->toArray();
		$groups_lkp = array_column($groups, 'name', 'id');
		$task_types = TaskType::select('id', 'name', 'color')->get()->toArray();
		$task_types_lkp = array_column($task_types, null, 'id');


		$final_fields = $system_fields;
		foreach ($custom_fields as $custom_field) {
			$final_fields[$custom_field['field_id']] = $custom_field['name'];
		}

		$draw = $request->input('draw');
		$start = $request->input('start');
		$length = $request->input('length');
		$searchValue = $request->input('search.value', '');
		$filters = $request->input('filters', []);
		$order = $request->input('order');

		// Initialize the query builder
		$query = DB::table('task_view')->select(array_keys($final_fields));

		// Handle search functionality (filter tasks based on the search value)
		if (!empty($searchValue)) {
			$searchValue = strtolower(trim($searchValue));
			$query->where(function ($q) use ($searchValue) {
				$q->whereRaw('LOWER(subject) LIKE ?', ['%' . $searchValue . '%'])
				  ->orWhereRaw('LOWER(description) LIKE ?', ['%' . $searchValue . '%']);
			});
		}

		if (count($filters) > 0) {
			$custom_fields_lkp = array_column($custom_fields, null, 'field_id');
			$_LIKE = ['subject', 'description'];
			$_EQUAL = ['id', 'project_id', 'task_type_id', 'status_id', 'priority_id'];

			$_LIKE_CF = ['Text', 'Textarea', 'Checkbox Group'];
			$_EQUAL_CF = ['Number', 'Radio Buttons', 'Dropdown List', 'Date', 'Time', 'Date-Time Picker'];

			foreach ($custom_fields as $custom_field) {
				if(in_array($custom_field['field_type'], $_LIKE_CF)) {
					$_LIKE[] = $custom_field['field_id'];
				} else if(in_array($custom_field['field_type'], $_EQUAL_CF)) {
					$_EQUAL[] = $custom_field['field_id'];
				}
			}

			foreach ($filters as $field_id => $searchValue) {
				if(in_array($field_id, $_LIKE)) {
					$searchValue = strtolower(trim($searchValue));
					$query->where(function ($q) use ($field_id, $searchValue) {
						$q->whereRaw('LOWER('.$field_id.') LIKE ?', ['%' . $searchValue . '%']);
					});
				}
				if(in_array($field_id, $_EQUAL)) {
					$query->where(function ($q) use ($field_id, $searchValue) {
						$q->where($field_id, $searchValue);
					});
				}
			}
		}

		// Handle ordering functionality
		if ($order && isset($order[0]['column']) && isset($order[0]['dir'])) {
			$columns = array_keys($final_fields); // Assuming the columns map directly to fields
			$column = $columns[$order[0]['column']] ?? 'task_id'; // Default to 'task_id' if column is not found
			$direction = $order[0]['dir'];

			$query->orderBy($column, $direction);
		}

		// Handle pagination
		$tasks = $query->offset($start)->limit($length)->get();

		$project_ids = array_unique(array_column($query->offset($start)->limit($length)->get()->toArray(), 'project_id'));
		$project_lkp = [];
		if(count($project_ids) > 0) {
			$project_lkp = Project::select('id', 'name', 'color')->whereIn('id', $project_ids)->get()->toArray();
			$project_lkp = array_column($project_lkp, null, 'id');
		}

		// Total records count (for pagination purposes)
		$totalRecords = Task::count();
		$filteredRecords = $query->count();  // Can be optimized by querying only filtered records

		// Prepare the data for DataTable response
		$data = $tasks->map(function ($task, $index) use ($start, $final_fields, $statuses_lkp, $priorities_lkp, $users_lkp, $groups_lkp, $task_types_lkp, $project_lkp) {
			$row = ['sr_no' => $start + $index + 1];
			
			// Loop through each field in the final fields
			foreach ($final_fields as $field => $name) {
				// Mapping IDs to labels for specific fields
				switch ($field) {
					case 'project_id':
						$task_type_Label = $project_lkp[$task->project_id]['name'] ?? '';
						$row[$field] = $task_type_Label ? 
							"<span class='label tbl-label color-parent-td' style='background-color: {$project_lkp[$task->project_id]['color']}; color: " . GeneralHelper::invert_color($project_lkp[$task->project_id]['color']) . ";'>{$task_type_Label}</span>" 
							: '';
						break;
					case 'task_type_id':
						$task_type_Label = $task_types_lkp[$task->task_type_id]['name'] ?? '';
						$row[$field] = $task_type_Label ? 
							"<span class='label tbl-label color-parent-td' style='background-color: {$task_types_lkp[$task->task_type_id]['color']}; color: " . GeneralHelper::invert_color($task_types_lkp[$task->task_type_id]['color']) . ";'>{$task_type_Label}</span>" 
							: '';
						break;
					case 'status_id':
						$statusLabel = $statuses_lkp[$task->status_id]['name'] ?? '';
						$row[$field] = $statusLabel ? 
							"<span class='label tbl-label color-parent-td' style='background-color: {$statuses_lkp[$task->status_id]['color']}; color: " . GeneralHelper::invert_color($statuses_lkp[$task->status_id]['color']) . ";'>{$statusLabel}</span>" 
							: '';
						break;
					case 'priority_id':
						$priorityLabel = $priorities_lkp[$task->priority_id]['name'] ?? '';
						$row[$field] = $priorityLabel ? 
							"<span class='label tbl-label color-parent-td' style='background-color: {$priorities_lkp[$task->priority_id]['color']}; color: " . GeneralHelper::invert_color($priorities_lkp[$task->priority_id]['color']) . ";'>{$priorityLabel}</span>" 
							: "<span class='label tbl-label color-parent-td'><small>{not-set}</small></span>";
						break;
					case 'created_by':
					case 'updated_by':
					case 'executor_id':
						$row[$field] = $users_lkp[$task->{$field}] ?? '';
						break;
					case 'creator_group_id':
					case 'executor_group_id':
						$row[$field] = $groups_lkp[$task->{$field}] ?? '';
						break;
					default:
						// For all other fields, just use the value from the task
						$row[$field] = $task->{$field} ?? '';
						break;
				}
			}
			
			return $row;
		});


		// Return the response in DataTable format
		return response()->json([
			'draw' => (int) $draw,
			'recordsTotal' => $totalRecords,
			'recordsFiltered' => $filteredRecords,
			'data' => $data,
			'success' => true
		]);		
	}

	public function create()
	{
		$task_types = []; // TaskType::where('enabled', true)->get();
		$projects = Project::select('id', 'name')->where('enabled', true)->get();
		$statuses = Status::orderBy('order')->get();
		$priorities = TaskPriority::orderBy('order')->get();
		$creator = Auth::user();
		$creator_groups = $creator->load('groups')->groups->pluck('name', 'id')->toArray();
		$all_groups = []; //Group::where('enabled', true)->pluck('name', 'id')->toArray();

		return view('tasks.create', compact('task_types', 'projects', 'statuses', 'priorities', 'creator_groups', 'all_groups'));
	}

	public function store(Request $request)
	{

		$now_ts = date('Y-m-d H:i:s');

		$validator = Validator::make($request->all(), [
			'project_id' => 'required|numeric',
			'task_type' => 'required|numeric',
		], [
			//msgs
		], [
			'project_id' => 'Project',
			'task_type' => 'Task Type',
		]);
		if ($validator->fails()) {
			return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
		}

		$task_type_id = $request->input('task_type', null);

		$task_type = TaskType::where('id', $task_type_id)->first();

		if (empty($task_type)) {
			return response()->json(['success' => false, 'errors' => ['task_type' => ['Task Type not found']]], 422);
		}

		$task_type_settings = $task_type->settings ? json_decode($task_type->settings, true) : [];

		$field_ids = [];
		if(isset($task_type_settings['custom_fields']) && count($task_type_settings['custom_fields']) > 0) {
			$field_ids = $task_type_settings['custom_fields'];
		}

		$field_settings = [];
		$attribute_names = [
			'project_id' => 'Project Name',
			'task_type' => 'Task Type',
			'subject' => 'Subject',
			'description' => 'Description',
			'status_id' => 'Status',
			'priority_id' => 'Priority',
		];
		if (count($field_ids) > 0) {
			$custom_fields = CustomField::whereIn('id', $field_ids)->get()->toArray();
			if (!empty($custom_fields) && is_array($custom_fields)) {
				$field_settings = array_column($custom_fields, null, 'field_id');
			}
		}

		$validation_array = [
			'project_id' => 'required|numeric',
			'task_type' => 'required|numeric',
			'subject' => ['required','string','max:1000', 'regex:/^[\p{L}0-9_.()\[\] -]+$/u'],
			'description' => ['nullable','string','max:10000', 'regex:/^[\P{C}\n\r]+$/u'],
			'status_id' => 'required|numeric|min:1',
			'priority_id' => 'nullable|numeric|min:1',
		];
		$validation_messages = [
			'subject.regex' => 'The subject contains invalid characters. It may only include letters, numbers, spaces, and the following special characters: _ . ( ) [ ] -.',
			'description.regex' => 'Special characters or control characters found in description.',
		];

		$creator = Auth::user();
		$creator_groups = $creator->load('groups')->groups->where('enabled', true)->pluck('name', 'id')->toArray();
		$all_groups = Group::where('enabled', true)->pluck('name', 'id')->toArray();
		$all_users = User::where('enabled', true)->pluck('name', 'id')->toArray();

		$req_creator_group = $request->input('creator_group_id', null);
		if(count($creator_groups) == null && count($creator_groups) > 0) {
			$req_creator_group = array_keys($creator_groups)[0];
		}

		$attribute_names['creator_group_id'] = 'Creator Group';
		$validation_array['creator_group_id'] = ['nullable'];
		if(count($creator_groups) > 1) {
			$validation_array['creator_group_id'][] = 'in:' . implode(',', array_keys($creator_groups));
		}
		$request->merge(['creator_group_id' => $req_creator_group]);

		$attribute_names['executor_group_id'] = 'Executor Group';
		$validation_array['executor_group_id'] = ['nullable', 'in:' . implode(',', array_keys($all_groups)) ];
		$attribute_names['executor_id'] = 'Executor';
		$validation_array['executor_id'] = ['nullable', 'in:' . implode(',', array_keys($all_users)) ];

		$file_fields = [];
		foreach ($field_settings as $fid => $fs) {
			$rules = [];

			if ($fs['required'] == 1) {
				$rules[] = 'required';
			} else {
				$rules[] = 'nullable';
			}
			$fs_settings = [];
			if(trim($fs['settings']) != '') {
				$fs_settings = json_decode($fs['settings'], true);
			}
			$field_name = $fs['name'];
			$attribute_names[$fid] = $field_name;
			switch ($fs['field_type']) {
				case 'Text':
					if ($fs['required'] == true) {
						$rules[] = 'string';
						if (isset($fs_settings['max_length'])) {
							$rules[] = 'max:' . $fs_settings['max_length'];
						}
						if (isset($fs_settings['min_length'])) {
							$rules[] = 'min:' . $fs_settings['min_length'];
						}
					}
					// $rules = implode('|',$rules);
					break;
				case 'Number':
					$rules[] = 'numeric';
					if ($fs['required'] == true) {
						if (isset($fs_settings['min'])) {
							$rules[] = 'min:' . $fs_settings['min'];
						}
						if (isset($fs_settings['max'])) {
							$rules[] = 'max:' . $fs_settings['max'];
						}
					} else {
						$rules[] = 'nullable';
					}
					break;
				case 'Checkbox Group':
					if (isset($fs_settings['options'])) {
						$validKeys = array_keys($fs_settings['options']);
						
						$rules[] = function ($attribute, $value, $fail) use ($validKeys, $fs) {
							$invalid_options = [];
							foreach ($value as $key => $opt) {
								if (!in_array($key, $validKeys)) {
									$invalid_options[] = $opt;
								}
							}
							if (count($invalid_options) > 0) {
								$fail("Invalid Options (".implode(', ', $invalid_options).") provided for field {$fs['name']}");
							}
						};
						$rules[] = 'array';
					}
					break;
				case 'Radio Buttons':
				case 'Dropdown List':
					if (isset($fs_settings['options'])) {
						$validKeys = array_keys($fs_settings['options']);
						
						$rules[] = function ($attribute, $value, $fail) use ($validKeys, $fs) {
							if (!in_array($value, $validKeys)) {
								$fail("Invalid Options provided for field {$fs['name']}");
							}
						};
						$rules[] = 'numeric';
					}
					break;
				case 'File Upload':
					$allowMultiple = isset($fs_settings['allow_multiple']) && $fs_settings['allow_multiple'] === 'yes';
					$file_fields[$fs['field_id']] = [
						'id' => $fs['id'],
						'field_id' => $fs['field_id'],
						'has_multiple' => $allowMultiple,
					];

					if ($allowMultiple) {
						$rules[] = 'array';
						$rules[] = 'min:1';

						if (isset($fs_settings['max_file_size'])) {
							$maxFileSizeKB = $fs_settings['max_file_size'] * 1024;
							$rules[] = function ($attribute, $value, $fail) use ($maxFileSizeKB, $fs, $fs_settings) {
								$totalSize = 0;

								$errorMessages = [];
								foreach ($value as $file) {
									if ( $file->getSize() / 1024 > $maxFileSizeKB) {
										$errorMessages[] = "The total file size for field '{$fs['name']}' exceeds the limit of {$fs_settings['max_file_size']} MB.";
									}
								}
								if (!empty($errorMessages)) {
									$fail(implode("\n", $errorMessages));
								}
							};
						}

						if (isset($fs_settings['allowed_file_types'])) {
							$allowedTypes = explode(',', $fs_settings['allowed_file_types']);
							$cleanedTypes = array_map('trim', $allowedTypes);

							$rules[] = function ($attribute, $value, $fail) use ($cleanedTypes) {
								$errorMessages = [];
								foreach ($value as $file) {
									$fileExtension = $file->getClientOriginalExtension();
									if (!in_array($fileExtension, $cleanedTypes)) {
										$errorMessages[] = "Invalid file: {$file->getClientOriginalName()} must be of type (" . implode(',', $cleanedTypes) . ")";
									}
								}
								if (!empty($errorMessages)) {
									$fail(implode("<br>", $errorMessages));
								}
							};
						}
						/*if (isset($fs_settings['allowed_file_types'])) {
							$allowedTypes = explode(',', $fs_settings['allowed_file_types']);
							$cleanedTypes = array_map('trim', $allowedTypes);

							$rules[] = function ($attribute, $value, $fail) use ($cleanedTypes) {
								$errorMessages = [];
								
								foreach ($value as $file) {
									$fileMimeType = explode('/', $file->getMimeType());
									$fileMimeType = $fileMimeType[count($fileMimeType)-1];
									if (!in_array($fileMimeType, $cleanedTypes)) {
										$errorMessages[] = "Invalid file: {$file->getClientOriginalName()} must be of type (" . implode(',', $cleanedTypes) . ")";
									}
								}

								if (!empty($errorMessages)) {
									$fail(implode("<br>", $errorMessages));
								}
							};
						}*/
						
					} else {
						$rules[] = 'file';
						
						if (isset($fs_settings['max_file_size'])) {
							$rules[] = 'max:' . ($fs_settings['max_file_size'] * 1024);
						}

						if (isset($fs_settings['allowed_file_types'])) {
							$types = implode(',', array_map('trim', explode(',', $fs_settings['allowed_file_types'])));
							$rules[] = 'mimes:' . $types;
						}
					}
					break;
				case 'Textarea':
					$rules[] = 'string';
					if (isset($fs_settings['max_length'])) {
						$rules[] = 'max:' . $fs_settings['max_length'];
					}
					break;
				case 'Time':
					$rules[] = 'date_format:H:i:s'; 
					break;
				default:
					break;
			}

			if ((is_array($rules) && count($rules) > 0) || (is_string($rules) && trim($rules) != '')) {
				$validation_array[$fid] = $rules;
			}
		}

		$validator_final = Validator::make($request->all(), $validation_array, $validation_messages, $attribute_names);

		if ($validator_final->fails()) {
			return response()->json(['success' => false, 'errors' => $validator_final->errors()], 422);
		}
		$validatedData = $validator_final->validated();

		DB::beginTransaction();

		try {
			$task = new Task();

			$task->project_id = trim($validatedData['project_id']);
			$task->subject = trim($validatedData['subject']);
			$task->description = isset($validatedData['description']) ? trim($validatedData['description']) : null;
			$task->task_type_id = trim($validatedData['task_type']);
			$task->status_id = trim($validatedData['status_id']);
			$task->priority_id = isset($validatedData['priority_id']) ? trim($validatedData['priority_id']) : null;
			$task->creator_group_id = isset($validatedData['creator_group_id']) ? trim($validatedData['creator_group_id']) : null;
			$task->created_by = $creator->id;
			$task->updated_by = $creator->id;
			$task->executor_id = isset($validatedData['executor_id']) ? trim($validatedData['executor_id']) : null;
			$task->executor_group_id = isset($validatedData['executor_group_id']) ? trim($validatedData['executor_group_id']) : null;
			$task->planned_start = isset($validatedData['planned_start']) ? trim($validatedData['planned_start']) : null;
			$task->planned_end = isset($validatedData['planned_end']) ? trim($validatedData['planned_end']) : null;
			$task->created_at = $now_ts;
			$task->updated_at = $now_ts;

			if (!$task->save()) {
				throw new \Exception("Failed to save the task.");
			}

			$custom_field_data = [];
			foreach ($field_settings as $field_id => $field_config) {
				if (isset($validatedData[$field_id]) && !isset($file_fields[$field_id])) {

					if (in_array($field_config['field_type'], ['Dropdown List', 'Radio Buttons'])) {
						$validatedData[$field_id] = [
							$validatedData[$field_id] => json_decode($field_settings[$field_id]['settings'], true)['options'][$validatedData[$field_id]],
						];
					}

					if (in_array($field_config['field_type'], ['Checkbox Group', 'Radio Buttons', 'Dropdown List'])) {
						if (count($validatedData[$field_id]) > 0) {
							$sel_opts = $validatedData[$field_id];
							sort($sel_opts);
							$custom_field_data[] = [
								'task_id' => $task->id,
								'field_id' => $field_config['id'],
								'value' => implode(', ', $sel_opts),
								'jsonval' => json_encode($validatedData[$field_id]),
							];
						}
					} else {
						$custom_field_data[] = [
							'task_id' => $task->id,
							'field_id' => $field_config['id'],
							'value' => $validatedData[$field_id],
							'jsonval' => null,
						];
					}
				}
			}

			if (!empty($custom_field_data)) {
				TaskCustomField::insert($custom_field_data);
			}

			$task_attachements_data = [];
			$storagePath = 'public/uploads/task_attachements';
			$counter = 1;
			foreach ($file_fields as $file_field) {
				if (isset($validatedData[$file_field['field_id']])) {
					if ($file_field['has_multiple']) {
						foreach ($validatedData[$file_field['field_id']] as $file) {
							$filename = time()+$counter . '_' . $file->getClientOriginalName();
							$path = $file->storeAs($storagePath, $filename);
							$task_attachements_data[] = [
								'name' => $file->getClientOriginalName(),
								'url' => $path,
								'task_id' => $task->id,
								'field_id' => $file_field['id'],
								'created_by' => $creator->id,
								'created_at' => $now_ts,
							];
							$counter++;
						}
					} else {
						$file = $validatedData[$file_field['field_id']];
						$filename = time()+$counter . '_' . $file->getClientOriginalName();
						$path = $file->storeAs($storagePath, $filename);
						$task_attachements_data[] = [
							'name' => $file->getClientOriginalName(),
							'url' => $path,
							'task_id' => $task->id,
							'field_id' => $file_field['id'],
							'created_by' => $creator->id,
							'created_at' => $now_ts,
						];
						$counter++;
					}
				}
			}
			
			if (!empty($task_attachements_data)) {
				TaskAttachment::insert($task_attachements_data);
			}

			DB::commit();
			return response()->json(['success' => true, 'message' => 'Task Created Successfully'], 201);
		
		} catch (\Exception $e) {
		
			DB::rollBack();
			return response()->json(['error' => $e->getMessage()], 500);
		}
	}

	public function edit($id)
	{
		$task = TaskView::findOrFail($id);

		$projects = Project::select('id', 'name')->where('enabled', true)->get();
		$task_types = TaskType::where('enabled', true)->orWhere('id', $task->task_type_id)->get();
		$this_task_type = array_column($task_types->toArray(), null, 'id');

		$custom_fields_ids = [];
		if (isset($this_task_type[$task->task_type_id]) && isset($this_task_type[$task->task_type_id]['settings'])) {
			$this_task_type_settings = json_decode($this_task_type[$task->task_type_id]['settings'], true);
			if (isset($this_task_type_settings['custom_fields'])) {
				$custom_fields_ids = $this_task_type_settings['custom_fields'];
			}

		}

		if (count($custom_fields_ids) > 0) {

			$task_type_custom_fields = CustomField::whereIn('id', $custom_fields_ids)->where('field_type', 'Checkbox Group')->pluck('id')->toArray();

			$task_json_values = TaskCustomField::where('task_id', $id)
				->whereNotNull('jsonval')
				->whereIn('field_id', $task_type_custom_fields)
				->pluck('jsonval', 'field_id')
				->mapWithKeys(function ($value, $key) {
					return ['cf_' . $key => $value];
				})
				->toArray();
			foreach ($task_json_values as $fid => $fv) {
				$task->$fid = json_decode($fv, true);
			}
		}

		$statuses = Status::orderBy('order')->get();
		$priorities = TaskPriority::orderBy('order')->get();
		$creator = new \App\Models\User();
		$creator->id = $task->created_by;
		$creator_groups = $creator->load('groups')->groups->pluck('name', 'id')->toArray();
		$all_groups = []; //Group::where('enabled', true)->pluck('name', 'id')->toArray();

		return view('tasks.edit', compact('task', 'projects', 'task_types', 'statuses', 'priorities', 'creator_groups', 'all_groups'));
	}

	public function update(Request $request, $id)
	{
		$now_ts = date('Y-m-d H:i:s');

		$task = Task::where('id', $id)->first();
		if (empty($task)) {
			return response()->json(['success' => false, 'errors' => ['task' => ['Task Not Found!']]], 422);
		}

		$validator = Validator::make($request->all(), [
			'project_id' => 'required|numeric',
			'task_type' => 'required|numeric',
		], [
			//msgs
		], [
			'project_id' => 'Project',
			'task_type' => 'Task Type',
		]);
		if ($validator->fails()) {
			return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
		}

		$task_type_id = $request->input('task_type', null);

		$task_type = TaskType::where('id', $task_type_id)->first();

		if (empty($task_type)) {
			return response()->json(['success' => false, 'errors' => ['task_type' => ['Task Type not found']]], 422);
		}

		$task_type_settings = $task_type->settings ? json_decode($task_type->settings, true) : [];

		$field_ids = [];
		if(isset($task_type_settings['custom_fields']) && count($task_type_settings['custom_fields']) > 0) {
			$field_ids = $task_type_settings['custom_fields'];
		}

		$field_settings = [];
		$attribute_names = [
			'project_id' => 'Project Name',
			'task_type' => 'Task Type',
			'subject' => 'Subject',
			'description' => 'Description',
			'status_id' => 'Status',
			'priority_id' => 'Priority',
		];
		if (count($field_ids) > 0) {
			$custom_fields = CustomField::whereIn('id', $field_ids)->get()->toArray();
			if (!empty($custom_fields) && is_array($custom_fields)) {
				$field_settings = array_column($custom_fields, null, 'field_id');
			}
		}

		$validation_array = [
			'project_id' => 'required|numeric',
			'task_type' => 'required|numeric',
			'subject' => ['required','string','max:1000', 'regex:/^[\p{L}0-9_.()\[\] -]+$/u'],
			'description' => ['nullable','string','max:10000', 'regex:/^[\P{C}\n\r]+$/u'],
			'status_id' => 'required|numeric|min:1',
			'priority_id' => 'nullable|numeric|min:1',
		];
		$validation_messages = [
			'subject.regex' => 'The subject contains invalid characters. It may only include letters, numbers, spaces, and the following special characters: _ . ( ) [ ] -.',
			'description.regex' => 'Special characters or control characters found in description.',
		];

		$creator = Auth::user();
		$creator_groups = $creator->load('groups')->groups->where('enabled', true)->pluck('name', 'id')->toArray();
		$all_groups = Group::where('enabled', true)->pluck('name', 'id')->toArray();
		$all_users = User::where('enabled', true)->pluck('name', 'id')->toArray();

		$req_creator_group = $request->input('creator_group_id', null);
		if(count($creator_groups) == null && count($creator_groups) > 0) {
			$req_creator_group = array_keys($creator_groups)[0];
		}

		$attribute_names['creator_group_id'] = 'Creator Group';
		$validation_array['creator_group_id'] = ['nullable'];
		if(count($creator_groups) > 1) {
			$validation_array['creator_group_id'][] = 'in:' . implode(',', array_keys($creator_groups));
		}
		$request->merge(['creator_group_id' => $req_creator_group]);

		$attribute_names['executor_group_id'] = 'Executor Group';
		$validation_array['executor_group_id'] = ['nullable', 'in:' . implode(',', array_keys($all_groups)) ];
		$attribute_names['executor_id'] = 'Executor';
		$validation_array['executor_id'] = ['nullable', 'in:' . implode(',', array_keys($all_users)) ];

		foreach ($field_settings as $fid => $fs) {
			$rules = [];

			if ($fs['required'] == 1) {
				$rules[] = 'required';
			} else {
				$rules[] = 'nullable';
			}
			$fs_settings = [];
			if(trim($fs['settings']) != '') {
				$fs_settings = json_decode($fs['settings'], true);
			}
			$field_name = $fs['name'];
			$attribute_names[$fid] = $field_name;
			switch ($fs['field_type']) {
				case 'Text':
					if ($fs['required'] == true) {
						$rules[] = 'string';
						if (isset($fs_settings['max_length'])) {
							$rules[] = 'max:' . $fs_settings['max_length'];
						}
						if (isset($fs_settings['min_length'])) {
							$rules[] = 'min:' . $fs_settings['min_length'];
						}
					}
					// $rules = implode('|',$rules);
					break;
				case 'Number':
					$rules[] = 'numeric';
					if ($fs['required'] == true) {
						if (isset($fs_settings['min'])) {
							$rules[] = 'min:' . $fs_settings['min'];
						}
						if (isset($fs_settings['max'])) {
							$rules[] = 'max:' . $fs_settings['max'];
						}
					} else {
						$rules[] = 'nullable';
					}
					break;
				case 'Checkbox Group':
					if (isset($fs_settings['options'])) {
						$validKeys = array_keys($fs_settings['options']);
						
						$rules[] = function ($attribute, $value, $fail) use ($validKeys, $fs) {
							$invalid_options = [];
							foreach ($value as $key => $opt) {
								if (!in_array($key, $validKeys)) {
									$invalid_options[] = $opt;
								}
							}
							if (count($invalid_options) > 0) {
								$fail("Invalid Options (".implode(', ', $invalid_options).") provided for field {$fs['name']}");
							}
						};
						$rules[] = 'array';
					}
					break;
				case 'Radio Buttons':
				case 'Dropdown List':
					if (isset($fs_settings['options'])) {
						$validKeys = array_keys($fs_settings['options']);
						
						$rules[] = function ($attribute, $value, $fail) use ($validKeys, $fs) {
							if (!in_array($value, $validKeys)) {
								$fail("Invalid Options provided for field {$fs['name']}");
							}
						};
						$rules[] = 'numeric';
					}
					break;
				case 'File Upload':
					$rules = ['nullable']; // Attachement are not required on UPDATE. later thiss will be handled through WFE

					$allowMultiple = isset($fs_settings['allow_multiple']) && $fs_settings['allow_multiple'] === 'yes';
					$file_fields[$fs['field_id']] = [
						'id' => $fs['id'],
						'field_id' => $fs['field_id'],
						'has_multiple' => $allowMultiple,
					];

					if ($allowMultiple) {
						$rules[] = 'array';
						$rules[] = 'min:1';

						if (isset($fs_settings['max_file_size'])) {
							$maxFileSizeKB = $fs_settings['max_file_size'] * 1024;
							$rules[] = function ($attribute, $value, $fail) use ($maxFileSizeKB, $fs, $fs_settings) {
								$totalSize = 0;

								$errorMessages = [];
								foreach ($value as $file) {
									if ( $file->getSize() / 1024 > $maxFileSizeKB) {
										$errorMessages[] = "The total file size for field '{$fs['name']}' exceeds the limit of {$fs_settings['max_file_size']} MB.";
									}
								}
								if (!empty($errorMessages)) {
									$fail(implode("\n", $errorMessages));
								}
							};
						}

						if (isset($fs_settings['allowed_file_types'])) {
							$allowedTypes = explode(',', $fs_settings['allowed_file_types']);
							$cleanedTypes = array_map('trim', $allowedTypes);
							$cleanedTypes = array_map('strtolower', $cleanedTypes);

							$rules[] = function ($attribute, $value, $fail) use ($cleanedTypes) {
								$errorMessages = [];
								foreach ($value as $file) {
									$fileExtension = strtolower($file->getClientOriginalExtension());
									if (!in_array($fileExtension, $cleanedTypes)) {
										$errorMessages[] = "Invalid file: {$file->getClientOriginalName()} must be of type (" . implode(',', $cleanedTypes) . ")";
									}
								}
								if (!empty($errorMessages)) {
									$fail(implode("<br>", $errorMessages));
								}
							};
						}
						/*if (isset($fs_settings['allowed_file_types'])) {
							$allowedTypes = explode(',', $fs_settings['allowed_file_types']);
							$cleanedTypes = array_map('trim', $allowedTypes);
							$cleanedTypes = array_map('strtolower', $cleanedTypes);

							$rules[] = function ($attribute, $value, $fail) use ($cleanedTypes, $fs) {
								$errorMessages = [];
								
								foreach ($value as $file) {
									$fileMimeType = explode('/', $file->getMimeType());
									$fileMimeType = strtolower(end($fileMimeType));
									if (!in_array($fileMimeType, $cleanedTypes)) {
										$errorMessages[] = "Invalid file: {$file->getClientOriginalName()} must be of type (" . implode(',', $cleanedTypes) . ")";
									}
								}

								if (!empty($errorMessages)) {
									$fail(implode("<br>", $errorMessages));
								}
							};
						}*/
						
					} else {
						$rules[] = 'file';
						
						if (isset($fs_settings['max_file_size'])) {
							$rules[] = 'max:' . ($fs_settings['max_file_size'] * 1024);
						}

						if (isset($fs_settings['allowed_file_types'])) {
							$types = implode(',', array_map('trim', explode(',', $fs_settings['allowed_file_types'])));
							$rules[] = 'mimes:' . $types;
						}
					}
					break;
				case 'Textarea':
					$rules[] = 'string';
					if (isset($fs_settings['max_length'])) {
						$rules[] = 'max:' . $fs_settings['max_length'];
					}
					break;
				case 'Time':
					$rules[] = 'date_format:H:i:s'; 
					break;

				default:
					break;
			}

			if ((is_array($rules) && count($rules) > 0) || (is_string($rules) && trim($rules) != '')) {
				$validation_array[$fid] = $rules;
			}
		}

		$validator_final = Validator::make($request->all(), $validation_array, $validation_messages, $attribute_names);

		if ($validator_final->fails()) {
			return response()->json(['success' => false, 'errors' => $validator_final->errors()], 422);
		}

		$validatedData = $validator_final->validated();

		DB::beginTransaction();

		try {
			$task->subject = trim($validatedData['subject']);
			$task->description = isset($validatedData['description']) ? trim($validatedData['description']) : null;
			$task->task_type_id = trim($validatedData['task_type']);
			$task->status_id = trim($validatedData['status_id']);
			$task->priority_id = isset($validatedData['priority_id']) ? trim($validatedData['priority_id']) : null;
			$task->creator_group_id = isset($validatedData['creator_group_id']) ? trim($validatedData['creator_group_id']) : null;
			$task->updated_by = $creator->id;
			$task->executor_id = isset($validatedData['executor_id']) ? trim($validatedData['executor_id']) : null;
			$task->executor_group_id = isset($validatedData['executor_group_id']) ? trim($validatedData['executor_group_id']) : null;
			$task->planned_start = isset($validatedData['planned_start']) ? trim($validatedData['planned_start']) : null;
			$task->planned_end = isset($validatedData['planned_end']) ? trim($validatedData['planned_end']) : null;
			$task->updated_at = $now_ts;

			if (!$task->save()) {
				throw new \Exception("Failed to save the task.");
			}

			$custom_field_data = [];

			TaskCustomField::where('task_id', $task->id)->delete();

			foreach ($field_settings as $field_id => $field_config) {
				if (isset($validatedData[$field_id]) && !isset($file_fields[$field_id])) {

					
					if (in_array($field_config['field_type'], ['Dropdown List', 'Radio Buttons'])) {
						$validatedData[$field_id] = [
							$validatedData[$field_id] => json_decode($field_settings[$field_id]['settings'], true)['options'][$validatedData[$field_id]],
						];
					}

					if (in_array($field_config['field_type'], ['Checkbox Group', 'Radio Buttons', 'Dropdown List'])) {
						if (count($validatedData[$field_id]) > 0) {
							$sel_opts = $validatedData[$field_id];
							sort($sel_opts);
							$custom_field_data[] = [
								'task_id' => $task->id,
								'field_id' => $field_config['id'],
								'value' => implode(', ', $sel_opts),
								'jsonval' => json_encode($validatedData[$field_id]),
							];
						}
					} else {
						$custom_field_data[] = [
							'task_id' => $task->id,
							'field_id' => $field_config['id'],
							'value' => $validatedData[$field_id],
							'jsonval' => null,
						];
					}
				}
			}

			if (!empty($custom_field_data)) {
				TaskCustomField::insert($custom_field_data);
			}
			$task_attachements_data = [];
			$storagePath = 'public/uploads/task_attachements';
			$counter = 1;
			foreach ($file_fields as $file_field) {
				if (isset($validatedData[$file_field['field_id']])) {
					if ($file_field['has_multiple']) {
						foreach ($validatedData[$file_field['field_id']] as $file) {
							$filename = time()+$counter . '_' . $file->getClientOriginalName();
							$path = $file->storeAs($storagePath, $filename);
							$task_attachements_data[] = [
								'name' => $file->getClientOriginalName(),
								'url' => $path,
								'task_id' => $task->id,
								'field_id' => $file_field['id'],
								'created_by' => $creator->id,
								'created_at' => $now_ts,
							];
							$counter++;
						}
					} else {
						$file = $validatedData[$file_field['field_id']];
						$filename = time()+$counter . '_' . $file->getClientOriginalName();
						$path = $file->storeAs($storagePath, $filename);
						$task_attachements_data[] = [
							'name' => $file->getClientOriginalName(),
							'url' => $path,
							'task_id' => $task->id,
							'field_id' => $file_field['id'],
							'created_by' => $creator->id,
							'created_at' => $now_ts,
						];
						$counter++;
					}
				}
			}
			
			if (!empty($task_attachements_data)) {
				TaskAttachment::insert($task_attachements_data);
			}

			DB::commit();
			return response()->json(['success' => true, 'message' => 'Task Created Successfully'], 201);
		
		} catch (\Exception $e) {		
			DB::rollBack();
			return response()->json(['error' => $e->getMessage()], 500);
		}
	}

	public function show($id)
	{
		$task = Task::with('status', 'priority', 'tasktype', 'creator', 'executor', 'creatorGroup', 'executorGroup', 'taskCustomField', 'updater', 'sla')->findOrFail($id);

		$custom_fields_lkp = array_column(
			CustomField::whereIn('id', array_column($task->TaskCustomField->toArray(), 'field_id'))->get()->toArray(),
			null,
			'id'
		);

		return view('tasks.show', compact('task', 'custom_fields_lkp'));
	}

	/**
	 * Remove the specified task from the database.
	 */
	public function destroy($id)
	{
		DB::beginTransaction();

		try {
			$task = Task::findOrFail($id);

			DB::table('task_attachements')->where('task_id', $task->id)->delete();
			DB::table('task_audit_logs')->where('task_id', $task->id)->delete();
			DB::table('task_comments')->where('task_id', $task->id)->delete();
			DB::table('task_custom_fields')->where('task_id', $task->id)->delete();

			$task->delete();

			DB::commit();

			return redirect()->route('tasks.index')->with('success', 'Task deleted successfully.');
		} catch (\Exception $e) {
			DB::rollBack();

			return redirect()->route('tasks.index')->with('error', 'Failed to delete task: ' . $e->getMessage());
		}
	}

	/**
	 * Remove the specified File from the database and storage.
	 */
	public function rm_file($id)
	{
		DB::beginTransaction();

		try {
			$taskFile = TaskAttachment::find($id);

			if ($taskFile) {
				if ($taskFile->url && \Storage::exists($taskFile->url)) {
					\Storage::delete($taskFile->url);
				}

				$taskFile->delete();

				DB::commit();

				return response()->json(['success' => true, 'message' => 'File deleted successfully.'], 200);
			} else {
				DB::rollBack();
				return response()->json(['success' => false, 'message' => 'File not found.'], 404);
			}
		} catch (\Exception $e) {
			DB::rollBack();

			\Log::error('File deletion error: ' . $e->getMessage());

			return response()->json(['success' => false, 'message' => 'Unable to delete file.'], 500);
		}
	}

	public function download_file($id)
	{
		$file = TaskAttachment::findOrFail($id);
		$filePath = storage_path('app/' . $file->url);
		
		if (!file_exists($filePath)) {
			return redirect()->back()->with('error', 'File not found.');
		}

		$fileSize = filesize($filePath);
		
		$extension = strtolower(pathinfo($file->url, PATHINFO_EXTENSION));
		
		$excludedExtensions = ['zip', 'tar', 'rar', 'gz', '7z'];

		if (in_array($extension, $excludedExtensions)) {
			return response()->download($filePath, $file->name);
		}

		if ($fileSize > 1 * 1024 * 1024) {
			$zipFileName = 'files_' . time() . '.zip';
			$zipFilePath = storage_path('app/public/' . $zipFileName);

			$zip = new ZipArchive();

			if ($zip->open($zipFilePath, ZipArchive::CREATE) === TRUE) {
				$zip->addFile($filePath, $file->name);
				$zip->close();
				return response()->download($zipFilePath)->deleteFileAfterSend(true);
			} else {
				return redirect()->back()->with('error', 'Failed to create zip file.');
			}
		} else {
			return response()->download($filePath, $file->name);
		}
	}

	public function get_fields(Request $request)
	{
		$changed_field = $request->input('changed_field', null);
		$id = $request->input('id', null);
		$mode = $request->input('mode', null);
		$task_id = $request->input('task_id', null);

		if ($changed_field == null OR $id == null OR $mode == null OR $mode == 'edit' AND $task_id == null ) {
			return response()->json(['success' => false, 'message' => 'Invalid Request'], 400);
		}
		
		if($changed_field == 'project_id') {
			$projects = Project::with('taskTypesEnabled', 'groupsEnabled')->select('id')->where('id', $id)->where('enabled', true)->first();
			if (!empty($projects)) {
				return response()->json(['success' => true, 'data' => ['task_types'=> $projects->taskTypesEnabled, 'groups'=> $projects->groupsEnabled]], 200);
			} else {
				return response()->json(['success' => true, 'data' => []], 200);
			}
		}

		$task_type = TaskType::findOrFail($id);
		$task = new \App\Models\Task();
		$saved_custom_fields = [];
		$settings = [];

		if($task_type->settings) {
			$settings = json_decode($task_type->settings, true);
		}
		
		if(isset($settings['custom_fields'])) {
			$saved_custom_fields = $settings['custom_fields'];
		}

		$custom_fields = CustomField::select('id', 'field_id', 'name', 'description', 'field_type', 'required', 'settings')->whereIn('id', $saved_custom_fields)->get()->toArray();
		$custom_fields = array_column($custom_fields, null, 'id');

		$custom_fields_sorted = [];
		foreach ($saved_custom_fields as $cfk) {
			if(isset($custom_fields[$cfk])) {
				$custom_fields_sorted[] = $custom_fields[$cfk];
			}
		}

		$allowed_status_ids = [];
		if ($mode == 'edit') {

			$task = Task::where('id', $task_id)->first();

			$task_files_data = TaskAttachment::select('id', 'name', 'task_id', 'field_id', 'created_by', 'created_at')->with('creator')->where('task_id', $task_id)->get()->toArray();

			$task_files = [];
			foreach ($task_files_data as $file) {
				if ($file['creator']) {
					$file['creator_name'] = $file['creator']['name'];
					$file['creator_email'] = $file['creator']['email'];
					$file['creator_phone'] = $file['creator']['phone'];
					unset($file['creator']);
				}
			   $task_files['cf_' . $file['field_id']][] = $file;
			}


			$this_user = User::with('groups')->where('id', Auth::user()->id)->first();
			$this_user_group_ids = array_column($this_user->groups->toArray(), 'id');

			// [New] => 0
			// [Issuer] => 1
			// [Issuer Group Users] => 2
			// [Receiver] => 3
			// [Receiver Group Users] => 4
			// [General Users By Role] => 5
			// [General Users By Group] => 6


			$transition_types = array_flip(config('lookup')['transition_types']);

			$allowed_transition_types = [];
			if ($this_user->id == $task->created_by) {
				$allowed_transition_types[] = $transition_types['Issuer'];
			}
			if (in_array($task->creator_group_id, $this_user_group_ids)) {
				$allowed_transition_types[] = $transition_types['Issuer Group Users'];
			}
			if ($this_user->id == $task->executor_id) {
				$allowed_transition_types[] = $transition_types['Receiver'];
			}
			if (in_array($task->executor_group_id, $this_user_group_ids)) {
				$allowed_transition_types[] = $transition_types['Receiver Group Users'];
			}

			$allowed_status_ids = WorkflowStatusTransition::select('status_to_id')
				->where('status_from_id', $task->status_id)
				->where(function ($query) use ($allowed_transition_types, $this_user_group_ids, $this_user) {
					if (!empty($allowed_transition_types)) {
						$query->whereIn('transition_type', $allowed_transition_types);
					}
					$query->orWhereIn('group_id', $this_user_group_ids)
						  ->orWhere('role_id', $this_user->role_id);
				})
				->pluck('status_to_id')
				->unique()
				->toArray();
			$allowed_status_ids[] = $task->status_id;

		} else {
			$allowed_status_ids = WorkflowStatusTransition::select('status_to_id')
				->where('workflow_id', $task_type->workflow_id)
				->whereNull('status_from_id')
				->where('transition_type', 0)
				->pluck('status_to_id')
				->unique()
				->toArray();
		}

		$allowed_statuses = Status::whereIn('id', $allowed_status_ids)->orderBy('order')->get()->toArray();
		foreach ($allowed_statuses as $key => $asts) {
			$allowed_statuses[$key]['text_color'] = GeneralHelper::invert_color($asts['color']);
		}

		return response()->json(['success' => true, 'data' => compact('allowed_statuses', 'custom_fields', 'task_files')], 200);
	}
}
