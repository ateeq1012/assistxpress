<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\Status;
use App\Models\User;
use App\Models\Role;
use App\Models\Group;
use App\Models\CustomField;
use App\Models\Workflow;
use App\Models\WorkflowStatusTransition;

use App\Helpers\GeneralHelper;

class ServiceController extends Controller
{
	/**
	 * Display a listing of the services with drag-and-drop support for ordering.
	 */
	public function index(Request $request)
	{
		$pageSize = $request->input('page_size', 10);
		$search = $request->input('search');
		$sortColumn = $request->input('sort', 'id');
		$sortDirection = $request->input('direction', 'asc');

		// Query the services with optional search
		$query = Service::query();

		if ($search) {
			$query->where(function ($q) use ($search) {
				$q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%'])
				  ->orWhereRaw('LOWER(description) LIKE ?', ['%' . strtolower($search) . '%']);
			});
		}

		$services = $query->orderBy($sortColumn, $sortDirection)->paginate($pageSize);

		return view('services.index', [
			'services' => $services,
			'pageSize' => $pageSize,
			'search' => $search,
			'sortColumn' => $sortColumn,
			'sortDirection' => $sortDirection,
		]);
	}

	/**
	 * Show the form for creating a new service.
	 */
	public function create()
	{
		$workflows = DB::table('workflows')->select('id', 'name')->get();
		return view('services.create', [
			'workflows' => $workflows
		]);
	}

	/**
	 * Store a newly created service in the database.
	 */
	public function store(Request $request)
	{
		$request->validate([
			'name' => 'required|string|max:255|unique:services',
			'description' => 'nullable|string',
			'settings' => 'nullable|json',
			'approval_settings' => 'nullable|json',
			'workflow_id' => 'required|integer',
			// 'is_planned' => 'required|boolean',
			'color' => 'nullable|string|max:7',
			'enabled' => 'required|boolean',
		]);

		$now_ts = now();

		$service = DB::table('services')->insertGetId([
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

		return redirect()->route('services.edit', ['service' => $service])->with('success', 'Status Type created successfully.');
	}

	/**
	 * Display the specified service.
	 */
	public function show($id)
	{
		$service = Service::findOrFail($id);
		return view('services.show', compact('service'));
	}

	/**
	 * Show the form for editing the specified service.
	 */
	public function edit($id)
	{
		$service = Service::findOrFail($id);
		$service_request = new \App\Models\ServiceRequest();
		$saved_custom_fields = [];
		$settings = [];

		if($service->settings) {
			$settings = json_decode($service->settings, true);
		}
		
		if(isset($settings['custom_fields'])) {
			$saved_custom_fields = $settings['custom_fields'];
		}

		$system_fields = $service_request->getServiceRequestFields();

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

		return view('services.edit', compact('service', 'system_fields', 'selected_custom_fields', 'roles', 'custom_fields', 'workflows', 'saved_custom_fields'));
	}

	/**
	 * Update the specified service in the database.
	 */
	public function update(Request $request, $id)
	{
		$service = Service::findOrFail($id);

		$request->validate([
			'name' => 'required|string|max:255|unique:services,name,' . $id,
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
		$service->update($data);

		return redirect()->route('services.index')->with('success', 'Status Type updated successfully.');
	}

	public function save_service_custom_fields(Request $request)
	{
		$id = $request->input('id', null);
		if($id != null) {
			$id = (int) $id;
		}

		$selected_fields_req = $request->input('selected_fields');
		$selected_fields = json_decode($selected_fields_req, true);

		if(is_int($id) && is_array($selected_fields)) {

			$service = Service::find($id);

			if($service) {
				$settings = json_decode($service->settings, true);
				$settings['custom_fields'] = $selected_fields;
				$data = [
					'settings' => json_encode($settings),
				];
				
				$service->update($data);
				
				return response()->json(['message' => 'Custom fields saved successfully'], 200);
			} else {
				return response()->json(['message' => 'Service not found'], 404);
			}
		} else {
			return response()->json(['message' => 'Invalid Request'], 400);
		}
	}

	/*MOVED TO ServiceRequestController*/
	/*public function get_fields(Request $request)
	{
		$id = $request->input('id', null);
		$mode = $request->input('mode', null);
		$service_request_id = $request->input('service_request_id', null);

		if ($id == null OR $mode == null OR $mode == 'edit' AND $service_request_id == null ) {
			return response()->json(['success' => false, 'message' => 'Invalid Request'], 400);
		}
		
		$service = Service::findOrFail($id);
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
		if ($mode == 'edit') {

			$service_request = ServiceRequest::where('id', $service_request_id)->first();
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
			if ($this_user->id == $service_request->created_by) {
				$allowed_transition_types[] = $transition_types['Issuer'];
			}
			if (in_array($service_request->creator_group_id, $this_user_group_ids)) {
				$allowed_transition_types[] = $transition_types['Issuer Group Users'];
			}
			if ($this_user->id == $service_request->executor_id) {
				$allowed_transition_types[] = $transition_types['Receiver'];
			}
			if (in_array($service_request->executor_group_id, $this_user_group_ids)) {
				$allowed_transition_types[] = $transition_types['Receiver Group Users'];
			}

			$allowed_status_ids = WorkflowStatusTransition::select('status_to_id')
				->where('status_from_id', $service_request->status_id)
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
			$allowed_status_ids[] = $service_request->status_id;

		} else {
			$allowed_status_ids = WorkflowStatusTransition::select('status_to_id')
				->where('workflow_id', $service->workflow_id)
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
	 * Remove the specified service from the database.
	 */
	public function destroy($id)
	{
		$service_request_count = ServiceRequest::where('service_id', $id)->count();
		if($service_request_count > 0) {
			$msg = "There is a service request against this Service.";
			if($service_request_count > 1) {
				$msg = "There are {$service_request_count} service request against this Service.";
			}
			return redirect()->route('services.index')->with('error', "This Service cannot be deleted, {$msg}");
		}
		else
		{
			$service = Service::findOrFail($id);
			$service->delete();

			return redirect()->route('services.index')->with('success', 'Service deleted successfully.');
		}
	}
}