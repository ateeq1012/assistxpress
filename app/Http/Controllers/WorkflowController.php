<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use App\Models\Workflow;
use App\Models\Role;
use App\Models\Group;
use App\Models\CustomField;
use App\Models\Service;
use App\Models\WorkflowStatusTransition;
use App\Helpers\GeneralHelper;

class WorkflowController extends Controller
{
    public function index(Request $request)
    {
        $pageSize = $request->input('page_size', 10);
        $search = $request->input('search');
        $sortColumn = $request->input('sort', 'id');
        $sortDirection = $request->input('direction', 'asc');

        // Query the workflow with optional search
        $query = Workflow::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%'])
                  ->orWhereRaw('LOWER(description) LIKE ?', ['%' . strtolower($search) . '%']);
            });
        }

        $workflows = $query->orderBy($sortColumn, $sortDirection)->paginate($pageSize);

        return view('workflows.index', [
            'workflows' => $workflows,
            'pageSize' => $pageSize,
            'search' => $search,
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
        ]);
    }

    public function create()
    {
        return view('workflows.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:workflows',
            'description' => 'nullable|string',
        ]);

        $now_ts = now();

        $workflow = DB::table('workflows')->insertGetId([
            'name' => $request->name,
            'description' => $request->description,
            'created_by' => Auth::user()->id,
            'updated_by' => Auth::user()->id,
            'created_at' => $now_ts,
            'updated_at' => $now_ts,
        ]);

        return redirect()->route('workflows.edit', ['workflow' => $workflow])->with('success', 'Workflow created successfully.');
    }

    public function show($id)
    {
        $workflow = Workflow::findOrFail($id);
        return view('workflows.show', compact('workflow'));
    }

    public function edit($id)
    {
        $workflow = Workflow::with('transitions')->findOrFail($id);

        $pwt = GeneralHelper::process_workflow_transitions($workflow);

        $saved_transitions = [
            'new'=> [],
            'creator'=> [],
            'creators_group_members'=> [],
            'executor'=> [],
            'executors_group_members'=> [],
            'general_by_role'=> [],
            'general_by_group'=> []
        ];

        $creator_member_roles = [];
        $executors_member_roles = [];
        $general_users_by_role = [];
        $general_users_by_group = [];

        if(isset($pwt['from_to'])) {
            $from_to = $pwt['from_to'];
        }
        // if(isset($pwt['transitions'])) {
        //     $saved_transitions = $pwt['transitions'];
        // }

        if(isset($pwt['creator_member_roles'])) {
            $creator_member_roles = $pwt['creator_member_roles'];
        }
        if(isset($pwt['executors_member_roles'])) {
            $executors_member_roles = $pwt['executors_member_roles'];
        }
        if(isset($pwt['general_users_by_role'])) {
            $general_users_by_role = $pwt['general_users_by_role'];
        }
        if(isset($pwt['general_users_by_group'])) {
            $general_users_by_group = $pwt['general_users_by_group'];
        }

        $roles = DB::table('roles')->where('enabled', true)->get();
        $groups = DB::table('groups')->get();
        $statuses = DB::table('statuses')->select('id', 'name', 'color')->orderBy('order')->get();
        return view('workflows.edit', compact('workflow', 'roles', 'groups', 'statuses', 'from_to', /*'saved_transitions',*/ 'creator_member_roles', 'executors_member_roles', 'general_users_by_role', 'general_users_by_group'));
    }

    public function update(Request $request, $id)
    {
        $now_ts = now();

        $workflow = Workflow::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:workflows,name,' . $id,
            'description' => 'nullable|string',
        ]);
        $selected_system_fields = $request->system_fields ?? null;
        $selected_system_fields = json_encode($selected_system_fields);

        $data = [
            'name' => $request->name,
            'description' => $request->description,
            'updated_by' => Auth::user()->id,
            'updated_at' => $now_ts,
        ];
        $workflow->update($data);

        return redirect()->route('workflows.index')->with('success', 'Workflow updated successfully.');
    }

    public function save_workflow_statuses(Request $request)
    {
        $id = $request->input('id', null);

        $created_by = Auth::user()->id;
        $created_at = date("'Y-m-d H:i:s'");

        $selected_transitions_req = $request->input('selected_transitions');
        $selected_transitions = json_decode($selected_transitions_req, true);
        $creator_member_roles = $request->input('creator_member_roles');
        $executors_member_roles = $request->input('executors_member_roles');
        $general_users_by_role = $request->input('general_users_by_role');
        $general_users_by_group = $request->input('general_users_by_group');

        if(
            is_numeric($id) && (int)$id == $id && is_array($selected_transitions) && 
            isset($selected_transitions['new']) && isset($selected_transitions['creator']) && isset($selected_transitions['executor']) &&
            is_array($selected_transitions['new']) && is_array($selected_transitions['creator']) && is_array($selected_transitions['executor']) &&
            count($selected_transitions['new']) > 0 && count($selected_transitions['creator']) > 0 && count($selected_transitions['executor']) > 0
        ) {

            $id = (int)$id;
            $workflow = Workflow::findOrFail($id);

            $data_to_insert = [];
            foreach ($selected_transitions['new'] as $stk => $stv)
            {
                $data_to_insert[] = [
                    'workflow_id' => $id,
                    'status_from_id' => null,
                    'status_to_id' => (int)$stv[1],
                    'transition_type' => 0,
                    'role_id' => null,
                    'group_id' => null,
                    'user_id' => null,
                    'created_by' => $created_by,
                    'created_at' => $created_at,
                ];
            }
            foreach ($selected_transitions['creator'] as $stk => $stv)
            {
                $data_to_insert[] = [
                    'workflow_id' => $id,
                    'status_from_id' => (int)$stv[0],
                    'status_to_id' => (int)$stv[1],
                    'transition_type' => 1,
                    'role_id' => null,
                    'group_id' => null,
                    'user_id' => null,
                    'created_by' => $created_by,
                    'created_at' => $created_at,
                ];
            }
            if(isset($selected_transitions['creators_group_members']) && is_array($selected_transitions['creators_group_members']))
            {
                if(!isset($creator_member_roles) || count($creator_member_roles) < 1)
                {
                    $creator_member_roles = [null];
                }
                foreach ($creator_member_roles as $role) {
                    foreach ($selected_transitions['creators_group_members'] as $stk => $stv) {
                        $data_to_insert[] = [
                            'workflow_id' => $id,
                            'status_from_id' => (int)$stv[0],
                            'status_to_id' => (int)$stv[1],
                            'transition_type' => 2,
                            'role_id' => $role!= null ? (int)$role : null,
                            'group_id' => null,
                            'user_id' => null,
                            'created_by' => $created_by,
                            'created_at' => $created_at,
                        ];
                    }
                }
            }
            foreach ($selected_transitions['executor'] as $stk => $stv)
            {
                $data_to_insert[] = [
                    'workflow_id' => $id,
                    'status_from_id' => (int)$stv[0],
                    'status_to_id' => (int)$stv[1],
                    'transition_type' => 3,
                    'role_id' => null,
                    'group_id' => null,
                    'user_id' => null,
                    'created_by' => $created_by,
                    'created_at' => $created_at,
                ];
            }
            if(isset($selected_transitions['executors_group_members']) && is_array($selected_transitions['executors_group_members']))
            {
                if(!isset($executors_member_roles) || count($executors_member_roles) < 1)
                {
                    $executors_member_roles = [null];
                }
                foreach ($executors_member_roles as $role) {
                    foreach ($selected_transitions['executors_group_members'] as $stk => $stv) {
                        $data_to_insert[] = [
                            'workflow_id' => $id,
                            'status_from_id' => (int)$stv[0],
                            'status_to_id' => (int)$stv[1],
                            'transition_type' => 4,
                            'role_id' => $role!= null ? (int)$role : null,
                            'group_id' => null,
                            'user_id' => null,
                            'created_by' => $created_by,
                            'created_at' => $created_at,
                        ];
                    }
                }
            }
            if(isset($selected_transitions['general_by_role']) && is_array($selected_transitions['general_by_role']))
            {
                if(!isset($general_users_by_role) || count($general_users_by_role) < 1)
                {
                    $general_users_by_role = [null];
                }
                foreach ($general_users_by_role as $role) {
                    foreach ($selected_transitions['general_by_role'] as $stk => $stv) {
                        $data_to_insert[] = [
                            'workflow_id' => $id,
                            'status_from_id' => (int)$stv[0],
                            'status_to_id' => (int)$stv[1],
                            'transition_type' => 5,
                            'role_id' => $role!= null ? (int)$role : null,
                            'group_id' => null,
                            'user_id' => null,
                            'created_by' => $created_by,
                            'created_at' => $created_at,
                        ];
                    }
                }
            }
            if(isset($selected_transitions['general_by_group']) && is_array($selected_transitions['general_by_group']))
            {
                if(!isset($general_users_by_group) || count($general_users_by_group) < 1)
                {
                    $general_users_by_group = [null];
                }
                foreach ($general_users_by_group as $group) {
                    foreach ($selected_transitions['general_by_group'] as $stk => $stv) {
                        $data_to_insert[] = [
                            'workflow_id' => $id,
                            'status_from_id' => (int)$stv[0],
                            'status_to_id' => (int)$stv[1],
                            'transition_type' => 6,
                            'role_id' => null,
                            'group_id' => $group!= null ? (int)$group : null,
                            'user_id' => null,
                            'created_by' => $created_by,
                            'created_at' => $created_at,
                        ];
                    }
                }
            }

            DB::table('workflow_status_transitions')->where('workflow_id', $id)->delete();
            DB::table('workflow_status_transitions')->insert($data_to_insert);
            return response()->json(['success' => true, 'message' => 'Workflow saved successfully'], 200);

        } else {

            $errors = [];
            if((int)$id != $id)
            {
                $errors[] = 'Invalid Workflow ID';
            }
            if(!isset($selected_transitions['new']) || !is_array($selected_transitions['new']) || count($selected_transitions['new']) < 1)
            {
                $errors[] = 'New Service Request';
            }
            if(!isset($selected_transitions['creator']) || !is_array($selected_transitions['creator']) || count($selected_transitions['creator']) < 1)
            {
                $errors[] = 'Creator';
            }
            if(!isset($selected_transitions['executor']) || !is_array($selected_transitions['executor']) || count($selected_transitions['executor']) < 1)
            {
                $errors[] = 'Executor';
            }
            return response()->json(['success' => false, 'message' => 'Transitions requried for ('. implode(', ', $errors).')'], 400);
        }
    }

    /**
     * Remove the specified service from the database.
     */
    public function destroy($id)
    {
        $services = Service::where('workflow_id', $id)->count();
        if($services > 0) {
            $msg = "There is a service against this Workflow.";
            if($services > 1) {
                $msg = "There are {$services} services against this Workflow.";
            }
            return redirect()->route('workflows.index')->with('error', "This Workflow cannot be deleted, {$msg}");
        }
        else
        {
            $workflow = Workflow::findOrFail($id);
            $workflow->delete();

            return redirect()->route('workflows.index')->with('success', 'Workflow deleted successfully.');
        }
    }
}
