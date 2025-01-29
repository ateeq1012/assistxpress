<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use App\Models\TaskType;
use App\Models\Task;
use App\Models\Status;
use App\Models\User;
use App\Models\Role;
use App\Models\Group;
use App\Models\CustomField;
use App\Models\Workflow;
use App\Models\WorkflowStatusTransition;

use App\Helpers\GeneralHelper;

class TaskTypeController extends Controller
{
	/**
	 * Display a listing of the task_types with drag-and-drop support for ordering.
	 */
	public function index(Request $request)
	{
		$pageSize = $request->input('page_size', 10);
		$search = $request->input('search');
		$sortColumn = $request->input('sort', 'id');
		$sortDirection = $request->input('direction', 'asc');

		// Query the task_types with optional search
		$query = TaskType::query();

		if ($search) {
			$query->where(function ($q) use ($search) {
				$q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%'])
				  ->orWhereRaw('LOWER(description) LIKE ?', ['%' . strtolower($search) . '%']);
			});
		}

		$task_types = $query->orderBy($sortColumn, $sortDirection)->paginate($pageSize);

		return view('task_types.index', [
			'task_types' => $task_types,
			'pageSize' => $pageSize,
			'search' => $search,
			'sortColumn' => $sortColumn,
			'sortDirection' => $sortDirection,
		]);
	}

	/**
	 * Show the form for creating a new task_type.
	 */
	public function create()
	{
		$workflows = DB::table('workflows')->select('id', 'name')->get();
		return view('task_types.create', [
			'workflows' => $workflows
		]);
	}

	/**
	 * Store a newly created task_type in the database.
	 */
	public function store(Request $request)
	{
		$request->validate([
			'name' => 'required|string|max:255|unique:task_types',
			'description' => 'nullable|string',
			'settings' => 'nullable|json',
			'approval_settings' => 'nullable|json',
			'workflow_id' => 'required|integer',
			// 'is_planned' => 'required|boolean',
			'color' => 'nullable|string|max:7',
			'enabled' => 'required|boolean',
		]);

		$now_ts = now();

		$task_type = DB::table('task_types')->insertGetId([
			'name' => $request->name,
			'description' => $request->description,
			'settings' => $request->settings ?? null,
			'approval_settings' => $request->approval_settings ?? null,
			'workflow_id' => $request->workflow_id,
			// 'is_planned' => $request->is_planned,
			'color' => $request->color,
			'enabled' => $request->enabled,
			'created_by' => Auth::user()->id,
			'updated_by' => Auth::user()->id,
			'created_at' => $now_ts,
			'updated_at' => $now_ts,
		]);

		return redirect()->route('task_types.edit', ['task_type' => $task_type])->with('success', 'Status Type created successfully.');
	}

	/**
	 * Display the specified task_type.
	 */
	public function show($id)
	{
		$task_type = TaskType::findOrFail($id);
		return view('task_types.show', compact('task_type'));
	}

	/**
	 * Show the form for editing the specified task_type.
	 */
	public function edit($id)
	{
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

		$system_fields = $task->getTaskFields();

		$custom_fields = CustomField::select('id', 'field_id', 'name', 'description', 'field_type')->get()->toArray();

		$selected_custom_fields = [];
		foreach ($custom_fields as $sf_key => $custom_field) {

			if(in_array($custom_field['id'], $saved_custom_fields)) {
				$selected_custom_fields[] = $custom_field;
				unset($custom_fields[$sf_key]);
			}
		}

		$roles = DB::table('roles')->get();
		$groups = DB::table('groups')->get();
		
		$workflows = DB::table('workflows')->select('id', 'name')->get();

		return view('task_types.edit', compact('task_type', 'system_fields', 'selected_custom_fields', 'roles', 'custom_fields', 'workflows', 'saved_custom_fields'));
	}

	/**
	 * Update the specified task_type in the database.
	 */
	public function update(Request $request, $id)
	{
		$task_type = TaskType::findOrFail($id);

		$request->validate([
			'name' => 'required|string|max:255|unique:task_types,name,' . $id,
			'description' => 'nullable|string',
			'settings' => 'nullable|json',
			'approval_settings' => 'nullable|json',
			'workflow_id' => 'required|integer',
			// 'is_planned' => 'required|boolean',
			'color' => 'nullable|string|max:7',
			'enabled' => 'required|boolean',
		]);
		$selected_system_fields = $request->system_fields ?? null;
		$selected_system_fields = json_encode($selected_system_fields);

		$data = [
			'name' => $request->name,
			'description' => $request->description,
			// 'settings' => $selected_system_fields,
			// 'approval_settings' => $request->approval_settings ?? null,
			'workflow_id' => $request->workflow_id,
			// 'is_planned' => $request->is_planned,
			'color' => $request->color,
			'enabled' => $request->enabled,
			'updated_by' => Auth::user()->id,
			'updated_at' => now(),
		];
		$task_type->update($data);

		return redirect()->route('task_types.index')->with('success', 'Status Type updated successfully.');
	}

	public function save_task_type_custom_fields(Request $request)
	{
		$id = $request->input('id', null);
		if($id != null) {
			$id = (int) $id;
		}

		$selected_fields_req = $request->input('selected_fields');
		$selected_fields = json_decode($selected_fields_req, true);

		if(is_int($id) && is_array($selected_fields)) {

			$task_type = TaskType::find($id);

			if($task_type) {
				$settings = json_decode($task_type->settings, true);
				$settings['custom_fields'] = $selected_fields;
				$data = [
					'settings' => json_encode($settings),
				];
				
				$task_type->update($data);
				
				return response()->json(['message' => 'Custom fields saved successfully'], 200);
			} else {
				return response()->json(['message' => 'Task type not found'], 404);
			}
		} else {
			return response()->json(['message' => 'Invalid Request'], 400);
		}
	}

	/*MOVED TO TaskController*/
	/*public function get_fields(Request $request)
	{
		$id = $request->input('id', null);
		$mode = $request->input('mode', null);
		$task_id = $request->input('task_id', null);

		if ($id == null OR $mode == null OR $mode == 'edit' AND $task_id == null ) {
			return response()->json(['success' => false, 'message' => 'Invalid Request'], 400);
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

		return response()->json(['success' => true, 'data' => compact('allowed_statuses', 'custom_fields')], 200);
	}*/

	/**
	 * Remove the specified task_type from the database.
	 */
	public function destroy($id)
	{
		$tasks_count = User::where('role_id', $id)->count();
		if($tasks_count > 0) {
			$msg = "There is a task against this Task Type.";
			if($tasks_count > 1) {
				$msg = "There are {$tasks_count} tasks against this Task Type.";
			}
			return redirect()->route('task_types.index')->with('error', "This Task Type cannot be deleted, {$msg}");
		}
		else
		{
			$task_type = TaskType::findOrFail($id);
			$task_type->delete();

			return redirect()->route('task_types.index')->with('success', 'Status Type deleted successfully.');
		}
	}
}