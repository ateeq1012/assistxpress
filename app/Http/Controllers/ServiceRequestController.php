<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

use App\Models\ServiceRequest;
use App\Models\ServiceRequestView;
use App\Models\Service;
use App\Models\CustomField;
use App\Models\Status;
use App\Models\ServicePriority;
use App\Models\Group;
use App\Models\User;
use App\Models\UserGroup;
use App\Models\ServiceRequestCustomField;
use App\Models\ServiceRequestComment;
use App\Models\Workflow;
use App\Models\WorkflowStatusTransition;
use App\Models\Sla;
use App\Models\ServiceRequestAttachment;
use App\Models\ServiceDomain;
use App\Models\ServiceDomainGroup;
use App\Models\ServiceRequestAuditLog;

use App\Notifications\EmailNotification;
use Illuminate\Support\Facades\Notification;
use App\Mail\CustomMail;
use Illuminate\Support\Facades\Mail;

use App\Helpers\GeneralHelper;
use App\Helpers\SlaHelper;

use Illuminate\Support\Facades\Http;

use ZipArchive;

use App\Exports\ServiceRequestExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

use App\Services\NotificationService;

class ServiceRequestController extends Controller
{
	public function index(Request $request)
	{
		$service_domains = ServiceDomain::select('id', 'name', 'color', 'enabled')->get()->toArray();
		$services = Service::select('id', 'name', 'color', 'enabled')->get()->toArray();
		$statuses = Status::get()->toArray();
		$priorities = ServicePriority::get()->toArray();
		$sla_rules = Sla::pluck('name', 'id')->toArray();
		$users = User::get()->toArray();
		$groups = Group::get()->toArray();

		$service_request = new \App\Models\ServiceRequest();
		$system_fields = $service_request->getAllServiceRequestFields();
		$selected_fields = $system_fields;
		
		$custom_fields = CustomField::orderBy('field_type')->get()->toArray();
		foreach ($custom_fields as $custom_field) {
			$_EXCLUDED_FIELDS = ['File Upload'];
			if(!in_array($custom_field['name'], $_EXCLUDED_FIELDS))
			{
				// $selected_fields[$custom_field['field_id']] = $custom_field['name'];
			}
		}

		$not_selected_fields = [];
		return view('service_requests.index', [
			'service_domains' => $service_domains,
			'services' => $services,
			'custom_fields' => $custom_fields,
			'statuses' => $statuses,
			'priorities' => $priorities,
			'sla_rules' => $sla_rules,
			'users' => $users,
			'groups' => $groups,
			'selected_fields' => $selected_fields,
			'not_selected_fields' => $not_selected_fields,
		]);
	}

	public function get_service_request_data(Request $request)
	{
		ini_set("memory_limit", '8192M');
		set_time_limit(300);

		$user_group_ids = Auth::user()->load('groups')->groups->pluck('id')->toArray();
		$user_service_domain_ids = ServiceDomainGroup::whereIn('group_id', $user_group_ids)->pluck('service_domain_id')->toArray();

		$service_request = new \App\Models\ServiceRequest();
		$system_fields = $service_request->getAllServiceRequestFields();
		$_EXCLUDED_FIELDS = ['File Upload'];
		$custom_fields = CustomField::whereNotIn('field_type', $_EXCLUDED_FIELDS)->get()->toArray();

		$statuses = Status::select('id', 'name', 'color')->get()->toArray();
		$statuses_lkp = array_column($statuses, null, 'id');
		$priorities = ServicePriority::select('id', 'name', 'color')->get()->toArray();
		$priorities_lkp = array_column($priorities, null, 'id');
		$users = User::select('id', 'name')->get()->toArray();
		$users_lkp = array_column($users, 'name', 'id');
		$groups = Group::select('id', 'name')->get()->toArray();
		$groups_lkp = array_column($groups, 'name', 'id');
		$services = Service::select('id', 'name', 'color')->get()->toArray();
		$services_lkp = array_column($services, null, 'id');
        $sla_rules = Sla::get()->toArray();
        $sla_rules_lkp = array_column($sla_rules, null, 'id');

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
		$query = DB::table('service_request_view')->select(array_keys($final_fields))->whereIn('service_domain_id', $user_service_domain_ids);
		// Handle search functionality (filter service_requests based on the search value)
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
			$_EQUAL = ['id', 'service_domain_id', 'service_id', 'status_id', 'priority_id', 'sla_rule_id', 'created_by', 'creator_group_id', 'executor_id', 'executor_group_id', 'updated_by'];

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
			$column = $columns[$order[0]['column']] ?? 'service_request_id'; // Default to 'service_request_id' if column is not found
			$direction = $order[0]['dir'];

			$query->orderBy($column, $direction);
		}

		// Handle pagination
		$service_requests = $query->offset($start)->limit($length)->get();

		$service_request_ids = array_unique(array_column($service_requests->toArray(), 'id'));
		$service_domain_ids = array_unique(array_column($query->offset($start)->limit($length)->get()->toArray(), 'service_domain_id'));
		$latest_comments = ServiceRequestComment::select('service_request_id', 'text')
		    ->whereIn('service_request_id', $service_request_ids)
		    ->orderBy('service_request_id')
		    ->orderByDesc('created_at')
		    ->get()
		    ->unique('service_request_id');

		$latest_comments_lkp = [];
		if(!empty($latest_comments)) {
			$latest_comments_lkp = array_column($latest_comments->toArray(), 'text', 'service_request_id');
		}

		$service_domain_lkp = [];
		if(count($service_domain_ids) > 0) {
			$service_domain_lkp = ServiceDomain::select('id', 'name', 'color')->whereIn('id', $service_domain_ids)->get()->toArray();
			$service_domain_lkp = array_column($service_domain_lkp, null, 'id');
		}

		// Total records count (for pagination purposes)
		$totalRecords = ServiceRequest::count();
		$filteredRecords = $query->count();  // Can be optimized by querying only filtered records

		// Prepare the data for DataTable response
		$data = $service_requests->map(
			function ($service_request, $index) use (
				$start,
				$final_fields,
				$statuses_lkp,
				$priorities_lkp,
				$users_lkp,
				$groups_lkp,
				$services_lkp,
				$service_domain_lkp,
				$sla_rules_lkp,
				$latest_comments_lkp,
			) {
				$row = ['sr_no' => $start + $index + 1];
				
				// Loop through each field in the final fields
				foreach ($final_fields as $field => $name) {
					// Mapping IDs to labels for specific fields
					switch ($field) {
						case 'service_domain_id':
							$service_Label = $service_domain_lkp[$service_request->service_domain_id]['name'] ?? '';
							$row[$field] = $service_Label ? 
								"<span class='label tbl-label color-parent-td' style='background-color: {$service_domain_lkp[$service_request->service_domain_id]['color']}; color: " . GeneralHelper::invert_color($service_domain_lkp[$service_request->service_domain_id]['color']) . ";'>{$service_Label}</span>" 
								: '';
							break;
						case 'service_id':
							$service_Label = $services_lkp[$service_request->service_id]['name'] ?? '';
							$row[$field] = $service_Label ? 
								"<span class='label tbl-label color-parent-td' style='background-color: {$services_lkp[$service_request->service_id]['color']}; color: " . GeneralHelper::invert_color($services_lkp[$service_request->service_id]['color']) . ";'>{$service_Label}</span>" 
								: '';
							break;
						case 'sla_rule_id':
							$service_Label = $sla_rules_lkp[$service_request->sla_rule_id]['name'] ?? '';
							$row[$field] = $service_Label ? 
								"<span class='label tbl-label color-parent-td' style='background-color: {$sla_rules_lkp[$service_request->sla_rule_id]['color']}; color: " . GeneralHelper::invert_color($sla_rules_lkp[$service_request->sla_rule_id]['color']) . ";'>{$service_Label}</span>" 
								: '';
							break;
						case 'status_id':
							$statusLabel = $statuses_lkp[$service_request->status_id]['name'] ?? '';
							$row[$field] = $statusLabel ? 
								"<span class='label tbl-label color-parent-td' style='background-color: {$statuses_lkp[$service_request->status_id]['color']}; color: " . GeneralHelper::invert_color($statuses_lkp[$service_request->status_id]['color']) . ";'>{$statusLabel}</span>" 
								: '';
							break;
						case 'priority_id':
							$priorityLabel = $priorities_lkp[$service_request->priority_id]['name'] ?? '';
							$row[$field] = $priorityLabel ? 
								"<span class='label tbl-label color-parent-td' style='background-color: {$priorities_lkp[$service_request->priority_id]['color']}; color: " . GeneralHelper::invert_color($priorities_lkp[$service_request->priority_id]['color']) . ";'>{$priorityLabel}</span>" 
								: "<span class='label tbl-label color-parent-td'><small>{not-set}</small></span>";
							break;
						case 'created_by':
						case 'updated_by':
						case 'executor_id':
							$row[$field] = $users_lkp[$service_request->{$field}] ?? '';
							break;
						case 'creator_group_id':
						case 'executor_group_id':
							$row[$field] = $groups_lkp[$service_request->{$field}] ?? '';
							break;
						default:
							// For all other fields, just use the value from the service_request
							$row[$field] = $service_request->{$field} ?? '';
							break;
					}
				}
				
				$row['latest_comment'] = $latest_comments_lkp[$service_request->id] ?? ''; 
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
		$services = []; // Service::where('enabled', true)->get();

		$user_group_ids = Auth::user()->load('groups')->groups->pluck('id')->toArray();
		$user_service_domain_ids = ServiceDomainGroup::whereIn('group_id', $user_group_ids)->pluck('service_domain_id')->toArray();

		$service_domains = ServiceDomain::select('id', 'name')->whereIn('id', $user_service_domain_ids)->where('enabled', true)->get();
		$statuses = Status::orderBy('order')->get();
		$priorities = ServicePriority::orderBy('order')->get();
		$creator = Auth::user();
		$creator_groups = $creator->load('groups')->groups->pluck('name', 'id')->toArray();
		$all_groups = []; //Group::where('enabled', true)->pluck('name', 'id')->toArray();

		return view('service_requests.create', compact('services', 'service_domains', 'statuses', 'priorities', 'creator_groups', 'all_groups'));
	}

	public function store(Request $request)
	{
		$now_ts = date('Y-m-d H:i:s');

		$validator = Validator::make($request->all(), [
			'service_domain_id' => 'required|numeric',
			'service_id' => 'required|numeric',
		], [
			//msgs
		], [
			'service_domain_id' => 'Service Domain',
			'service_id' => 'ServiceRequest Type',
		]);
		if ($validator->fails()) {
			return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
		}

		$service_id = $request->input('service_id', null);

		$service = Service::where('id', $service_id)->first();

		if (empty($service)) {
			return response()->json(['success' => false, 'errors' => ['service_id' => ['ServiceRequest Type not found']]], 422);
		}

		$service_settings = $service->settings ? json_decode($service->settings, true) : [];

		$field_ids = [];
		if(isset($service_settings['custom_fields']) && count($service_settings['custom_fields']) > 0) {
			$field_ids = $service_settings['custom_fields'];
		}

		$field_settings = [];
		$attribute_names = [
			'service_domain_id' => 'Service Domain Name',
			'service_id' => 'ServiceRequest Type',
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
			'service_domain_id' => 'required|numeric',
			'service_id' => 'required|numeric',
			'subject' => ['required', 'string', 'max:1000' ],
			'description' => ['nullable', 'string', 'max:10000' ],
			'status_id' => 'required|numeric|min:1',
			'priority_id' => 'nullable|numeric|min:1',
		];

		$creator = Auth::user();
		$creator_groups = $creator->load('groups')->groups->where('enabled', true)->pluck('name', 'id')->toArray();
		$all_groups = Group::where('enabled', true)->pluck('name', 'id')->toArray();
		$all_users = User::where('enabled', true)->pluck('name', 'id')->toArray();

		$req_creator_group = $request->input('creator_group_id', null);
		if($req_creator_group == null && count($creator_groups) > 0) {
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
					$rules[] = 'date_format:H:i'; 
					break;
				default:
					break;
			}

			if ((is_array($rules) && count($rules) > 0) || (is_string($rules) && trim($rules) != '')) {
				$validation_array[$fid] = $rules;
			}
		}

		$validator_final = Validator::make($request->all(), $validation_array, [], $attribute_names);

		if ($validator_final->fails()) {
			return response()->json(['success' => false, 'errors' => $validator_final->errors()], 422);
		}
		$validatedData = $validator_final->validated();

		DB::beginTransaction();

		try {
			$service_request = new ServiceRequest();

			$service_request->service_domain_id = trim($validatedData['service_domain_id']);
			$service_request->subject = GeneralHelper::cleanText($validatedData['subject']);
			$service_request->description = isset($validatedData['description']) ? GeneralHelper::cleanText($validatedData['description']) : null;
			$service_request->service_id = trim($validatedData['service_id']);
			$service_request->status_id = trim($validatedData['status_id']);
			$service_request->priority_id = isset($validatedData['priority_id']) ? trim($validatedData['priority_id']) : null;
			$service_request->creator_group_id = isset($validatedData['creator_group_id']) ? trim($validatedData['creator_group_id']) : null;
			$service_request->created_by = $creator->id;
			$service_request->updated_by = $creator->id;
			$service_request->executor_id = isset($validatedData['executor_id']) ? trim($validatedData['executor_id']) : null;
			$service_request->executor_group_id = isset($validatedData['executor_group_id']) ? trim($validatedData['executor_group_id']) : null;
			$service_request->planned_start = isset($validatedData['planned_start']) ? trim($validatedData['planned_start']) : null;
			$service_request->planned_end = isset($validatedData['planned_end']) ? trim($validatedData['planned_end']) : null;
			$service_request->created_at = $now_ts;
			$service_request->updated_at = $now_ts;

			if (!$service_request->save()) {
				throw new \Exception("Failed to save the service_request.");
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
								'service_request_id' => $service_request->id,
								'field_id' => $field_config['id'],
								'value' => implode(', ', $sel_opts),
								'jsonval' => json_encode($validatedData[$field_id]),
							];
						}
					} else {
						$custom_field_data[] = [
							'service_request_id' => $service_request->id,
							'field_id' => $field_config['id'],
							'value' => $validatedData[$field_id],
							'jsonval' => null,
						];
					}
				}
			}

			if (!empty($custom_field_data)) {
				ServiceRequestCustomField::insert($custom_field_data);
			}

			$service_request_attachements_data = [];
			$storagePath = 'public/uploads/service_request_attachements';
			$counter = 1;
			foreach ($file_fields as $file_field) {
				if (isset($validatedData[$file_field['field_id']])) {
					if ($file_field['has_multiple']) {
						foreach ($validatedData[$file_field['field_id']] as $file) {
							$filename = time()+$counter . '_' . $file->getClientOriginalName();
							$path = $file->storeAs($storagePath, $filename);
							$service_request_attachements_data[] = [
								'name' => $file->getClientOriginalName(),
								'url' => $path,
								'service_request_id' => $service_request->id,
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
						$service_request_attachements_data[] = [
							'name' => $file->getClientOriginalName(),
							'url' => $path,
							'service_request_id' => $service_request->id,
							'field_id' => $file_field['id'],
							'created_by' => $creator->id,
							'created_at' => $now_ts,
						];
						$counter++;
					}
				}
			}
			
			if (!empty($service_request_attachements_data)) {
				ServiceRequestAttachment::insert($service_request_attachements_data);
			}

			DB::commit();

			$notificationService = new NotificationService();
			$notification = $notificationService->serviceRequestCreated($service_request->id, true);
			return response()->json(['success' => true, 'message' => 'ServiceRequest Created Successfully'], 201);
		
		} catch (\Exception $e) {
		
			DB::rollBack();
			return response()->json(['error' => $e->getMessage()], 500);
		}
	}

	public function edit($id)
	{
		$service_request = ServiceRequestView::findOrFail($id);
		$user_group_ids = Auth::user()->load('groups')->groups->pluck('id')->toArray();
		$user_service_domain_ids = ServiceDomainGroup::whereIn('group_id', $user_group_ids)->pluck('service_domain_id')->toArray();

		if(isset($service_request->service_domain_id) && !in_array($service_request->service_domain_id, $user_service_domain_ids)) {
			return redirect()->route('service_requests.index')->with('error', 'You are not allowed to Modify this Service Request!');
		}

		$service_domains = ServiceDomain::select('id', 'name')->whereIn('id', $user_service_domain_ids)->where('enabled', true)->get();
		$services = Service::where('enabled', true)->orWhere('id', $service_request->service_id)->get();
		$this_service = array_column($services->toArray(), null, 'id');

		$custom_fields_ids = [];
		if (isset($this_service[$service_request->service_id]) && isset($this_service[$service_request->service_id]['settings'])) {
			$this_service_settings = json_decode($this_service[$service_request->service_id]['settings'], true);
			if (isset($this_service_settings['custom_fields'])) {
				$custom_fields_ids = $this_service_settings['custom_fields'];
			}

		}

		if (count($custom_fields_ids) > 0) {

			$service_custom_fields = CustomField::whereIn('id', $custom_fields_ids)->where('field_type', 'Checkbox Group')->pluck('id')->toArray();

			$service_request_json_values = ServiceRequestCustomField::where('service_request_id', $id)
				->whereNotNull('jsonval')
				->whereIn('field_id', $service_custom_fields)
				->pluck('jsonval', 'field_id')
				->mapWithKeys(function ($value, $key) {
					return ['cf_' . $key => $value];
				})
				->toArray();
			foreach ($service_request_json_values as $fid => $fv) {
				$service_request->$fid = json_decode($fv, true);
			}
		}

		$statuses = Status::orderBy('order')->get();
		$priorities = ServicePriority::orderBy('order')->get();
		$creator = new \App\Models\User();
		$creator->id = $service_request->created_by;
		$creator_groups = $creator->load('groups')->groups->pluck('name', 'id')->toArray();
		$assignee_group = [];
		if(isset($service_request->executor_group_id)) {
			$assignee_group = Group::where('id', $service_request->executor_group_id)->pluck('name', 'id')->toArray();
		}
		$assignee = [];
		if(isset($service_request->executor_id)) {
			$assignee = User::where('id', $service_request->executor_id)->pluck('name', 'id')->toArray();
		}
		$all_groups = [];



		// For History
		$service_request_info = ServiceRequest::with('status', 'priority', 'service', 'creator', 'executor', 'creatorGroup', 'executorGroup', 'serviceRequestCustomField', 'updater', 'sla')->findOrFail($id);

		$service_request_logs = ServiceRequestAuditLog::with(['creator' => function ($query) {
			    $query->select('id', 'email', 'phone', 'name');
			}])
			->where('service_request_id', $id)
			->orderBy('created_at', 'desc')
			->get()
			->toArray();
		$service_request_comments = ServiceRequestComment::with('creator')->where('service_request_id', $id)->orderBy('created_at', 'desc')->get()->toArray();

		$log_field_ids = [];
		foreach ($service_request_logs as $log) {
			if($log['field_type'] == 2) {
				$log_field_ids[$log['field_name']] = true; 
			}
		}

		$lkp_search = [];
		foreach ($service_request_logs as $key => $log_val) {
			if(in_array($log_val['field_name'], ['service_domain_id', 'service_id', 'status_id', 'priority_id', 'executor_id', 'executor_group_id', 'sla_rule_id', ])) {
				if(isset($log_val['old_value'])) {
					$lkp_search[$log_val['field_name']][$log_val['old_value']] = $log_val['old_value'];
				}
				if(isset($log_val['new_value'])) {
					$lkp_search[$log_val['field_name']][$log_val['new_value']] = $log_val['new_value'];
				}
			}
		}

		$service_request_agg = [];
		foreach ($service_request_logs as $service_request_log) {
			$service_request_agg[$service_request_log['created_at'] .'_'. $service_request_log['created_by']]['ts'] = $service_request_log['created_at'];
			$service_request_agg[$service_request_log['created_at'] .'_'. $service_request_log['created_by']]['creator'] = $service_request_log['creator'] ?? [];
			unset($service_request_log['creator']);
			$service_request_agg[$service_request_log['created_at'] .'_'. $service_request_log['created_by']]['data'][] = $service_request_log;
		}
		$service_request_logs = $service_request_agg;

		$fileds_to_make_history = ServiceRequest::$fileds_to_make_history;

		$service_domain_lkp = $service_lkp = $priority_lkp = $status_lkp = $user_lkp = $executor_group_lkp = $sla_rule_lkp = [];
		if(isset($lkp_search['service_domain_id'])) {
			$service_domain_lkp = ServiceDomain::whereIn('id', $lkp_search['service_domain_id'])->pluck('name', 'id')->toArray();
		}
		if(isset($lkp_search['service_id'])) {
			$service_lkp = Service::whereIn('id', $lkp_search['service_id'])->pluck('name', 'id')->toArray();
		}
		if(isset($lkp_search['status_id'])) {
			$status_lkp = Status::whereIn('id', $lkp_search['status_id'])->pluck('name', 'id')->toArray();
		}
		if(isset($lkp_search['priority_id'])) {
			$priority_lkp = ServicePriority::whereIn('id', $lkp_search['priority_id'])->pluck('name', 'id')->toArray();
		}
		if(isset($lkp_search['executor_id'])) {
			$user_lkp = User::whereIn('id', $lkp_search['executor_id'])->pluck('name', 'id')->toArray();
		}
		if(isset($lkp_search['executor_group_id'])) {
			$executor_group_lkp = Group::whereIn('id', $lkp_search['executor_group_id'])->pluck('name', 'id')->toArray();
		}
		if(isset($lkp_search['sla_rule_id'])) {
			$sla_rule_lkp = Sla::whereIn('id', $lkp_search['sla_rule_id'])->pluck('name', 'id')->toArray();
		}

		$custom_fields_lkp = array_column(
			CustomField::whereIn('id', array_column($service_request_info->ServiceRequestCustomField->toArray(), 'field_id'))->orWhereIn('field_id', $log_field_ids)->get()->toArray(),
			null,
			'id'
		);

		$custom_field_id_lkp = array_column($custom_fields_lkp, 'name', 'field_id');

        $slaInfo = [];
        if(isset($service_request->sla_rule_id) ) {
			$service_request_status = $service_request_info->status;
			$sla_rule = $service_request_info->sla;
            
            $slaInfo = SlaHelper::getSlaInfo($service_request_status, $service_request, $sla_rule);
        }

		return view('service_requests.edit', compact(
			// edit
			'service_request', 'service_domains', 'services', 'statuses', 'priorities', 'creator_groups', 'all_groups', 'assignee_group', 'assignee',

			// for history
			'service_request_info', 'custom_fields_lkp', 'fileds_to_make_history', 'service_request_logs', 'custom_field_id_lkp', 'service_domain_lkp', 'service_lkp', 'status_lkp', 'priority_lkp', 'user_lkp', 'executor_group_lkp', 'sla_rule_lkp',

			// Comments
			'service_request_comments',
			// show sla
			'slaInfo'
		));
	}

	public function update(Request $request, $id)
	{
		$now_ts = date('Y-m-d H:i:s');

		$service_request = ServiceRequest::with('serviceRequestCustomField')->where('id', $id)->first();

		if (empty($service_request)) {
			return response()->json(['success' => false, 'errors' => ['service_request' => ['ServiceRequest Not Found!']]], 422);
		}

		$validator = Validator::make($request->all(), [
			'service_domain_id' => 'required|numeric',
			'service_id' => 'required|numeric',
		], [
			//msgs
		], [
			'service_domain_id' => 'Service Domain',
			'service_id' => 'ServiceRequest Type',
		]);
		if ($validator->fails()) {
			return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
		}

		$service_id = $request->input('service_id', null);

		$service = Service::where('id', $service_id)->first();

		if (empty($service)) {
			return response()->json(['success' => false, 'errors' => ['service_id' => ['ServiceRequest Type not found']]], 422);
		}

		$service_settings = $service->settings ? json_decode($service->settings, true) : [];

		$field_ids = [];
		if(isset($service_settings['custom_fields']) && count($service_settings['custom_fields']) > 0) {
			$field_ids = $service_settings['custom_fields'];
		}

		$field_settings = [];
		$attribute_names = [
			'service_domain_id' => 'Service Domain Name',
			'service_id' => 'ServiceRequest Type',
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
			'service_domain_id' => 'required|numeric',
			'service_id' => 'required|numeric',
			'subject' => ['required', 'string', 'max:1000' ],
			'description' => ['nullable', 'string', 'max:10000' ],
			'status_id' => 'required|numeric|min:1',
			'priority_id' => 'nullable|numeric|min:1',
		];

		$creator = Auth::user();
		$creator_groups = $creator->load('groups')->groups->where('enabled', true)->pluck('name', 'id')->toArray();
		$all_groups = Group::where('enabled', true)->pluck('name', 'id')->toArray();
		$all_users = User::where('enabled', true)->pluck('name', 'id')->toArray();

		/*$req_creator_group = $request->input('creator_group_id', null);
		if(count($creator_groups) == null && count($creator_groups) > 0) {
			$req_creator_group = array_keys($creator_groups)[0];
		}*/

		$attribute_names['creator_group_id'] = 'Creator Group';
		$validation_array['creator_group_id'] = ['nullable', 'integer'];
		/*if(count($creator_groups) > 1) {
			$validation_array['creator_group_id'][] = 'in:' . implode(',', array_keys($creator_groups));
		}
		$request->merge(['creator_group_id' => $req_creator_group]);*/

		$attribute_names['executor_group_id'] = 'Executor Group';
		$validation_array['executor_group_id'] = ['nullable', 'in:' . implode(',', array_keys($all_groups)) ];
		$attribute_names['executor_id'] = 'Executor';
		$validation_array['executor_id'] = ['nullable', 'in:' . implode(',', array_keys($all_users)) ];

		$custom_fields = [];
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
					$rules = ['nullable']; // Attachement are not required on UPDATE. later thiss will be handled through WFE

					$allowMultiple = isset($fs_settings['allow_multiple']) && $fs_settings['allow_multiple'] === 'yes';
					$file_fields[$fs['field_id']] = [
						'id' => $fs['id'],
						'field_id' => $fs['field_id'],
						'name' => $fs['name'],
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
					$rules[] = 'date_format:H:i'; 
					break;

				default:
					break;
			}

			if ((is_array($rules) && count($rules) > 0) || (is_string($rules) && trim($rules) != '')) {
				$validation_array[$fid] = $rules;
			}
		}

		$validator_final = Validator::make($request->all(), $validation_array, [], $attribute_names);

		if ($validator_final->fails()) {
			return response()->json(['success' => false, 'errors' => $validator_final->errors()], 422);
		}

		$validatedData = $validator_final->validated();


		$originalServiceRequest = $service_request->getAttributes();
		
		$original_status = $originalServiceRequest['status_id']; 
		$new_status = $validatedData['status_id'] ?? null;


		DB::beginTransaction();

		try {
			$service_request->service_domain_id = trim($validatedData['service_domain_id']);
			$service_request->subject = trim($validatedData['subject']);
			$service_request->description = isset($validatedData['description']) ? trim($validatedData['description']) : null;
			$service_request->service_id = trim($validatedData['service_id']);
			$service_request->status_id = trim($validatedData['status_id']);
			$service_request->priority_id = isset($validatedData['priority_id']) ? trim($validatedData['priority_id']) : null;
			$service_request->creator_group_id = isset($validatedData['creator_group_id']) ? trim($validatedData['creator_group_id']) : $service_request->creator_group_id;
			$service_request->updated_by = $creator->id;
			$service_request->executor_id = isset($validatedData['executor_id']) ? trim($validatedData['executor_id']) : null;
			$service_request->executor_group_id = isset($validatedData['executor_group_id']) ? trim($validatedData['executor_group_id']) : null;
			$service_request->planned_start = isset($validatedData['planned_start']) ? trim($validatedData['planned_start']) : null;
			$service_request->planned_end = isset($validatedData['planned_end']) ? trim($validatedData['planned_end']) : null;
			$service_request->updated_at = $now_ts;
			$service_request->sla_rule_id = $service_request->sla_rule_id; // avoide making false history
			$service_request->response_time = $service_request->response_time; // avoide making false history

			// set TTO and auto assign user if not already assigned.
			if(isset($service_request->executor_group_id) && isset($creator_groups[$service_request->executor_group_id]) ) {
				if(
					$service_request->response_time == null &&
					isset($new_status) && $original_status != $new_status
				) {
					$service_request->response_time = $now_ts; // set response time if null
				}
				
				if(!isset($service_request->executor_id)) {
					$service_request->executor_id = Auth::user()->id; // set assignee
				}
			}

			if (!$service_request->save()) {
				throw new \Exception("Failed to save the service_request.");
			}

        	$standrd_fields_logs = $this->logServiceRequestChanges($service_request->id, $originalServiceRequest, $validatedData, $creator->id, $now_ts);

			ServiceRequestCustomField::where('service_request_id', $service_request->id)->delete();

			$custom_field_data = [];
			$custom_field_history = [];
			$original_custom_field_data = array_column($service_request->serviceRequestCustomField->toArray(), null, 'field_id');

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
								'service_request_id' => $service_request->id,
								'field_id' => $field_config['id'],
								'value' => implode(', ', $sel_opts),
								'jsonval' => json_encode($validatedData[$field_id]),
							];

							if(isset($original_custom_field_data[$field_config['id']])) {
								$original_json_val = json_decode($original_custom_field_data[$field_config['id']]['jsonval'], true);
								$original_opts = array_values($original_json_val);
								$new_opts = array_values($validatedData[$field_id]);
								sort($original_opts);
								sort($new_opts);

								if ($original_opts !== $new_opts) {
									$custom_field_history[] = [
										'service_request_id' => $service_request->id,
										'field_name' => $field_config['field_id'],
										'field_type' => 2,
										'old_value' => implode(', ', $original_opts),
										'new_value' => implode(', ', $new_opts),
										'created_by' => $creator->id,
										'created_at' => $now_ts,
									];
								}
							} else {
								$custom_field_history[] = [
									'service_request_id' => $service_request->id,
									'field_name' => $field_config['field_id'],
									'field_type' => 2,
									'old_value' => null,
									'new_value' => implode(', ', $sel_opts),
									'created_by' => $creator->id,
									'created_at' => $now_ts,
								];
							}
						}
					} else {
						$custom_field_data[] = [
							'service_request_id' => $service_request->id,
							'field_id' => $field_config['id'],
							'value' => $validatedData[$field_id],
							'jsonval' => null,
						];
						if(isset($original_custom_field_data[$field_config['id']])) {
							if (trim($original_custom_field_data[$field_config['id']]['value']) != trim($validatedData[$field_id])) {
								$custom_field_history[] = [
									'service_request_id' => $service_request->id,
									'field_name' => $field_config['field_id'],
									'field_type' => 2,
									'old_value' => $original_custom_field_data[$field_config['id']]['value'],
									'new_value' => $validatedData[$field_id],
									'created_by' => $creator->id,
									'created_at' => $now_ts,
								];
							}
						} else {
							$custom_field_history[] = [
								'service_request_id' => $service_request->id,
								'field_name' => $field_config['field_id'],
								'field_type' => 2,
								'old_value' => null,
								'new_value' => $validatedData[$field_id],
								'created_by' => $creator->id,
								'created_at' => $now_ts,
							];
						}
					}
				}
			}

			if (!empty($custom_field_data)) {
				ServiceRequestCustomField::insert($custom_field_data);
			}
			if (!empty($custom_field_history)) {
				ServiceRequestAuditLog::insert($custom_field_history);
			}
			$service_request_attachements_data = [];
			$storagePath = 'public/uploads/service_request_attachements';
			$counter = 1;
			$file_attachement_history = [];
			foreach ($file_fields as $file_field) {
				if (isset($validatedData[$file_field['field_id']])) {
					if ($file_field['has_multiple']) {
						foreach ($validatedData[$file_field['field_id']] as $file) {
							$filename = time()+$counter . '_' . $file->getClientOriginalName();
							$path = $file->storeAs($storagePath, $filename);
							$service_request_attachements_data[] = [
								'name' => $file->getClientOriginalName(),
								'url' => $path,
								'service_request_id' => $service_request->id,
								'field_id' => $file_field['id'],
								'created_by' => $creator->id,
								'created_at' => $now_ts,
							];
							$file_attachement_history[] = [
								'service_request_id' => $service_request->id,
								'field_name' => $file_field['field_id'],
								'field_type' => 2,
								'old_value' => null,
								'new_value' => $file->getClientOriginalName(),
								'file_path' => $path,
								'created_by' => $creator->id,
								'created_at' => $now_ts,
							];
							$counter++;
						}
					} else {
						$file = $validatedData[$file_field['field_id']];
						$filename = time()+$counter . '_' . $file->getClientOriginalName();
						$path = $file->storeAs($storagePath, $filename);
						$service_request_attachements_data[] = [
							'name' => $file->getClientOriginalName(),
							'url' => $path,
							'service_request_id' => $service_request->id,
							'field_id' => $file_field['id'],
							'created_by' => $creator->id,
							'created_at' => $now_ts,
						];
						$file_attachement_history[] = [
							'service_request_id' => $service_request->id,
							'field_name' => $file_field['field_id'],
							'field_type' => 2,
							'old_value' => null,
							'new_value' => $file->getClientOriginalName(),
							'file_path' => $path,
							'created_by' => $creator->id,
							'created_at' => $now_ts,
						];
						$counter++;
					}
				}
			}
			
			if (!empty($service_request_attachements_data)) {
				ServiceRequestAttachment::insert($service_request_attachements_data);
				ServiceRequestAuditLog::insert($file_attachement_history);
			}

			DB::commit();

			if ($standrd_fields_logs > 0 || count($custom_field_history) > 0 || count($file_attachement_history) > 0) {
				$notificationService = new NotificationService();
				$notification = $notificationService->serviceRequestUpdated($service_request->id, true);
			}

			return response()->json(['success' => true, 'message' => 'ServiceRequest Updated Successfully'], 201);
		
		} catch (\Exception $e) {		
			DB::rollBack();
			return response()->json([
			    'error' => $e->getLine() . ": " . $e->getMessage() . "\n" . 
			    implode("\n", array_map(function($trace) {
			        return isset($trace['file']) ? $trace['file'] . ' on line ' . $trace['line'] : '';
			    }, $e->getTrace()))
			], 500);

		}
	}

	protected function logServiceRequestChanges($service_requestId, $originalData, $newData, $userId, $now_ts)
	{
		$fileds_to_make_history = ServiceRequest::$fileds_to_make_history;
		$history = [];
	    foreach ($fileds_to_make_history as $field_key => $field_label) {
	    	$original_value = $originalData[$field_key] ?? null;
	    	$new_value = $newData[$field_key] ?? null;

	        if ((isset($original_value) || isset($new_value)) && $original_value != $new_value) {
	            $history[] = [
	                'service_request_id' => $service_requestId,
	                'field_name' => $field_key,
					'field_type' => 1,
	                'old_value' => $original_value,
	                'new_value' => $new_value,
	                'created_by' => $userId,
	                'created_at' => $now_ts,
	            ];
	        }
	    }
	    if(count($history) > 0) {
			ServiceRequestAuditLog::insert($history);
	    }
	    return count($history);
	}

	public function add_comment(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'comment' => 'required|string',
			'service_request_id' => 'required|integer',
		], [
			//msgs
		], [
			'comment' => 'Comment',
			'service_request_id' => 'ServiceRequest Id',
		]);
		if ($validator->fails()) {
			return response()->json(['success' => false, 'msg' => 'Invalid Request'], 422);
		}
		
		$validatedData = $validator->validated();

		ServiceRequestComment::insert([
			'service_request_id' => $validatedData['service_request_id'],
			'text' => $validatedData['comment'],
			'created_by' => Auth::user()->id,
		]);

		return response()->json(['success' => true, 'msg' => 'Success'], 201);
	}

	public function show($id)
	{
		$service_request_info = ServiceRequest::with('status', 'priority', 'service', 'creator', 'executor', 'creatorGroup', 'executorGroup', 'serviceRequestCustomField', 'updater', 'sla')->findOrFail($id);
		
		$service_request_logs = ServiceRequestAuditLog::with(['creator' => function ($query) {
		    $query->select('id', 'email', 'phone', 'name');
		}])
		->where('service_request_id', $id)
		->orderBy('created_at', 'desc')
		->get()
		->toArray();
		$service_request_comments = ServiceRequestComment::with('creator')->where('service_request_id', $id)->orderBy('created_at', 'desc')->get()->toArray();

		$log_field_ids = [];
		foreach ($service_request_logs as $log) {
			if($log['field_type'] == 2) {
				$log_field_ids[$log['field_name']] = true; 
			}
		}

		$lkp_search = [];
		foreach ($service_request_logs as $key => $log_val) {
			if(in_array($log_val['field_name'], ['service_domain_id', 'service_id', 'status_id', 'priority_id', 'executor_id', 'executor_group_id', 'sla_rule_id', ])) {
				if(isset($log_val['old_value'])) {
					$lkp_search[$log_val['field_name']][$log_val['old_value']] = $log_val['old_value'];
				}
				if(isset($log_val['new_value'])) {
					$lkp_search[$log_val['field_name']][$log_val['new_value']] = $log_val['new_value'];
				}
			}
		}

		$service_request_agg = [];
		foreach ($service_request_logs as $service_request_log) {
			$service_request_agg[$service_request_log['created_at'] .'_'. $service_request_log['created_by']]['ts'] = $service_request_log['created_at'];
			$service_request_agg[$service_request_log['created_at'] .'_'. $service_request_log['created_by']]['creator'] = $service_request_log['creator'] ?? [];
			unset($service_request_log['creator']);
			$service_request_agg[$service_request_log['created_at'] .'_'. $service_request_log['created_by']]['data'][] = $service_request_log;
		}
		$service_request_logs = $service_request_agg;
		
		$fileds_to_make_history = ServiceRequest::$fileds_to_make_history;

		$service_domain_lkp = $service_lkp = $priority_lkp = $status_lkp = $user_lkp = $executor_group_lkp = $sla_rule_lkp = [];
		if(isset($lkp_search['service_domain_id'])) {
			$service_domain_lkp = ServiceDomain::whereIn('id', $lkp_search['service_domain_id'])->pluck('name', 'id')->toArray();
		}
		if(isset($lkp_search['service_id'])) {
			$service_lkp = Service::whereIn('id', $lkp_search['service_id'])->pluck('name', 'id')->toArray();
		}
		if(isset($lkp_search['status_id'])) {
			$status_lkp = Status::whereIn('id', $lkp_search['status_id'])->pluck('name', 'id')->toArray();
		}
		if(isset($lkp_search['priority_id'])) {
			$priority_lkp = ServicePriority::whereIn('id', $lkp_search['priority_id'])->pluck('name', 'id')->toArray();
		}
		if(isset($lkp_search['executor_id'])) {
			$user_lkp = User::whereIn('id', $lkp_search['executor_id'])->pluck('name', 'id')->toArray();
		}
		if(isset($lkp_search['executor_group_id'])) {
			$executor_group_lkp = Group::whereIn('id', $lkp_search['executor_group_id'])->pluck('name', 'id')->toArray();
		}
		if(isset($lkp_search['sla_rule_id'])) {
			$sla_rule_lkp = Sla::whereIn('id', $lkp_search['sla_rule_id'])->pluck('name', 'id')->toArray();
		}

		$custom_fields_lkp = array_column(
			CustomField::whereIn('id', array_column($service_request_info->ServiceRequestCustomField->toArray(), 'field_id'))->orWhereIn('field_id', array_keys($log_field_ids))->get()->toArray(),
			null,
			'id'
		);

		$custom_field_id_lkp = array_column($custom_fields_lkp, 'name', 'field_id');

        $slaInfo = [];
        if(isset($service_request_info->sla_rule_id) ) {
			$service_request_status = $service_request_info->status;
			$sla_rule = $service_request_info->sla;
            
            $slaInfo = SlaHelper::getSlaInfo($service_request_status, $service_request_info, $sla_rule);
        }

		return view('service_requests.show', compact('service_request_info', 'custom_fields_lkp', 'fileds_to_make_history', 'service_request_logs', 'custom_field_id_lkp', 'service_domain_lkp', 'service_lkp', 'status_lkp', 'priority_lkp', 'user_lkp', 'executor_group_lkp', 'sla_rule_lkp', 'service_request_comments', 'slaInfo'));
	}

	/**
	 * Remove the specified service_request from the database.
	 */
	public function destroy($id)
	{
		DB::beginTransaction();

		try {
			$service_request = ServiceRequest::findOrFail($id);

			DB::table('service_request_attachements')->where('service_request_id', $service_request->id)->delete();
			DB::table('service_request_audit_logs')->where('service_request_id', $service_request->id)->delete();
			DB::table('service_request_comments')->where('service_request_id', $service_request->id)->delete();
			DB::table('service_request_custom_fields')->where('service_request_id', $service_request->id)->delete();

			$service_request->delete();

			DB::commit();

			return redirect()->route('service_requests.index')->with('success', 'ServiceRequest deleted successfully.');
		} catch (\Exception $e) {
			DB::rollBack();

			return redirect()->route('service_requests.index')->with('error', 'Failed to delete service_request: ' . $e->getMessage());
		}
	}

	/**
	 * Remove the specified File from the database and storage.
	 */
	public function rm_file($id)
	{
		DB::beginTransaction();

		try {
			$service_requestFile = ServiceRequestAttachment::find($id);

			if ($service_requestFile) {
				if ($service_requestFile->url && \Storage::exists($service_requestFile->url)) {
					\Storage::delete($service_requestFile->url);
				}

				$service_requestFile->delete();

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

	public function get_fields(Request $request)
	{
		$changed_field = $request->input('changed_field', null);
		$id = $request->input('id', null);
		$mode = $request->input('mode', null);
		$service_request_id = $request->input('service_request_id', null);

		if ($changed_field == null OR $id == null OR $mode == null OR $mode == 'edit' AND $service_request_id == null ) {
			return response()->json(['success' => false, 'message' => 'Invalid Request'], 400);
		}
		
		if($changed_field == 'service_domain_id') {
			$service_domains = ServiceDomain::with('servicesEnabled', 'groupsEnabled')->select('id')->where('id', $id)->where('enabled', true)->first();
			if (!empty($service_domains)) {
				return response()->json(['success' => true, 'data' => ['services'=> $service_domains->servicesEnabled, 'groups'=> $service_domains->groupsEnabled]], 200);
			} else {
				return response()->json(['success' => true, 'data' => []], 200);
			}
		}

		// Else service_id field is changed. 

		$service = Service::where('id', $id)->first();
		if(empty($service)) {
			return response()->json(['success' => false, 'data' => [], 'message' => 'Selected Service Not Found. Please refresh the page and try again.'], 200);
		}
		$service_domain_id = $request->input('service_domain_id', null);
		if(empty($service_domain_id)) {
			return response()->json(['success' => false, 'data' => [], 'message' => 'Invalid Request'], 200);
		}
		// $service_domains = ServiceDomain::with('groups')->where('id', $service_domain_id)->first();

		$service_domains = ServiceDomain::with(['groups' => function ($query) {
			    $query->select('groups.id', 'groups.name');
			}])
			->where('id', $service_domain_id)
			->first();


		if(empty($service_domains)) {
			return response()->json(['success' => false, 'data' => [], 'message' => 'Selected Service Not Found. Please refresh the page and try again.'], 200);
		}

		$service_domain_groups_lkp = $service_domains->groups->map(function ($group) {
		    return ['id' => $group->id, 'name' => $group->name];
		})->all();

		$service_request = new \App\Models\ServiceRequest();
		$saved_custom_fields = [];
		$settings = [];

		if($service->settings) {
			$settings = json_decode($service->settings, true);
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
		$service_request_files = [];
		if ($mode == 'edit') {

			$service_request = ServiceRequest::where('id', $service_request_id)->first();

			$service_request_files_data = ServiceRequestAttachment::select('id', 'name', 'service_request_id', 'field_id', 'created_by', 'created_at')->with('creator')->where('service_request_id', $service_request_id)->get()->toArray();

			foreach ($service_request_files_data as $file) {
				if ($file['creator']) {
					$file['creator_name'] = $file['creator']['name'];
					$file['creator_email'] = $file['creator']['email'];
					$file['creator_phone'] = $file['creator']['phone'];
					unset($file['creator']);
				}
			   $service_request_files['cf_' . $file['field_id']][] = $file;
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

			$allowed_status_ids_query = WorkflowStatusTransition::select('status_to_id')
			    ->where('workflow_id', $service->workflow_id)
			    ->where('status_from_id', $service_request->status_id);

			$transition_types = array_flip(config('lookup')['transition_types']);

			$allowed_status_ids_query->where(function ($query) use ($service_request, $transition_types, $this_user_group_ids, $this_user) {


			    if ($this_user->id == $service_request->created_by) {
			        $query->where('transition_type', $transition_types['Issuer']);
			    }
			    
			    if (isset($this_user->role_id)) {
			        $query->orWhere(function ($q) use ($transition_types, $this_user) {
			            $q->where('transition_type', $transition_types['Issuer Group Users'])
			              ->where('role_id', $this_user->role_id);
			        });
			    }
			    
			    if ($this_user->id == $service_request->executor_id) {
			        $query->orWhere('transition_type', $transition_types['Receiver']);
			    }
			    
			    if (isset($this_user->role_id)) {
			        $query->orWhere(function ($q) use ($transition_types, $this_user) {
			            $q->where('transition_type', $transition_types['Receiver Group Users'])
			              ->where('role_id', $this_user->role_id);
			        });
			    }

		        $query->orWhere(function ($q) use ($transition_types, $this_user) {
		            $q->where('transition_type', $transition_types['General Users By Role'])
		              ->where('role_id', $this_user->role_id);
		        });

		        $query->orWhere(function ($q) use ($transition_types, $this_user_group_ids) {
		            $q->where('transition_type', $transition_types['General Users By Group'])
		              ->whereIn('group_id', $this_user_group_ids);
		        });
			});

			$allowed_status_ids = $allowed_status_ids_query->pluck('status_to_id')->unique()->toArray();

		} else {
			$allowed_status_ids = WorkflowStatusTransition::select('status_to_id')
				->where('workflow_id', $service->workflow_id)
				->whereNull('status_from_id')
				->where('transition_type', 0)
				->pluck('status_to_id')
				->unique()
				->toArray();
		}

		$allowed_statuses = Status::whereIn('id', $allowed_status_ids)->orWhere('id', $service_request->status_id)->orderBy('order')->get()->toArray();
		foreach ($allowed_statuses as $key => $asts) {
			$allowed_statuses[$key]['text_color'] = GeneralHelper::invert_color($asts['color']);
		}

		return response()->json(['success' => true, 'data' => compact('allowed_statuses', 'custom_fields', 'service_request_files', 'service_domain_groups_lkp')], 200);
	}

    public function search_service_domain_groups(Request $request)
    {
		$validator = Validator::make($request->all(), [
			'q' => 'required|string',
			'service_domain_id' => 'required|numeric',
			'enabled_only' => 'required|in:true,false',
			// 'service_id' => 'required|numeric',
		], [
			//msgs
		], [
			'q' => 'Search String',
			'service_domain_id' => 'Service Domain',
			'enabled_only' => 'Group Enabled Status',
			// 'service_id' => 'ServiceRequest Type',
		]);

		if ($validator->fails()) {
			return response()->json(['success' => false, 'msg' => 'Invalid Request'], 422);
		}
		
		$validatedData = $validator->validated();

        $service_domain_id = $validatedData['service_domain_id'];
		$search_term = $validatedData['q'];
		$enabled_only = filter_var($validatedData['enabled_only'], FILTER_VALIDATE_BOOLEAN);

		$service_domain_group_ids = ServiceDomainGroup::where('service_domain_id', $service_domain_id)->pluck('group_id')->toArray();
		$query = Group::whereIn('id', $service_domain_group_ids)->where('name', 'ILIKE', '%' . $search_term . '%');
        if($enabled_only == true) {
        	$query->where('enabled', true);
        }
        $groups_lkp = $query->get()->toArray();

		return response()->json(['success' => true, 'data' => $groups_lkp], 200);
    }

    public function search_group_users(Request $request)
    {
		$validator = Validator::make($request->all(), [
			'q' => 'required|string',
			'group_id' => 'required|numeric',
		], [
			//msgs
		], [
			'q' => 'Search String',
			'group_id' => 'User Group',
		]);
		if ($validator->fails()) {
			return response()->json(['success' => false, 'msg' => 'Invalid Request'], 422);
		}
		
		$validatedData = $validator->validated();

        $group_id = $validatedData['group_id'];
		$search_term = $validatedData['q'];


		$group_user_ids = UserGroup::where('group_id', $group_id)->pluck('user_id')->toArray();
		$users_lkp = User::whereIn('id', $group_user_ids)
			->where('enabled', true)
			->where('name', 'ILIKE', '%' . $search_term . '%')
			->get()->toArray();

		return response()->json(['success' => true, 'data' => $users_lkp], 200);
    }

	public function download_file($id)
	{
		$file = ServiceRequestAttachment::findOrFail($id);
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

		if ($fileSize > 10 * 1024 * 1024) {
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

    public function download(Request $request)
    {
        $search = $request->only(['dn_filters']);
        $searchParams = json_decode($search['dn_filters'], true);
        $now_ts = date('Ymd_Hms');
        return Excel::download(new ServiceRequestExport($searchParams), "service_requests_{$now_ts}.xlsx");
    }
}
