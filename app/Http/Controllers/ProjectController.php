<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use App\Models\Project;
use App\Models\ProjectUser;
use App\Models\User;
use App\Models\TaskType;
use App\Models\Task;
use App\Models\ProjectTaskType;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $pageSize = $request->input('page_size', 10);
        $search = $request->input('search');
        $sortColumn = $request->input('sort', 'closed');
        $sortDirection = $request->input('direction', 'asc');

        // Query the projects with optional search
        $query = Project::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%'])
                  ->orWhereRaw('LOWER(description) LIKE ?', ['%' . strtolower($search) . '%']);
            });
        }

        $projects = $query->orderBy($sortColumn, $sortDirection)->paginate($pageSize);

        return view('projects.index', [
            'projects' => $projects,
            'pageSize' => $pageSize,
            'search' => $search,
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
        ]);
    }

    public function create()
    {
        return view('projects.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:projects',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'enabled' => 'nullable|boolean',
            'closed' => 'nullable|boolean',
        ]);

        $now_ts = now();

        Project::create([
            'name' => $request->name,
            'description' => $request->description,
            'color' => $request->color,
            'enabled' => false, // $request->enabled,
            'closed' => false, // $request->closed,
            'created_by' => Auth::user()->id,
            'updated_by' => Auth::user()->id,
            'created_at' => $now_ts,
            'updated_at' => $now_ts,
        ]);

        return redirect()->route('projects.index')->with('success', 'Project created successfully.');
    }

    public function show($id)
    {
        $project = Project::with(['creator', 'updater', 'members.role', 'groups' => function ($query) {
            $query->withCount('members');
        }])->where('id', $id)->firstOrFail();
        return view('projects.show', compact('project'));
    }
    public function edit($id)
    {
        $project = Project::with(['creator', 'updater', 'members.role', 'taskTypes', 'groups' => function ($query) {
            $query->withCount('members');
        }])->where('id', $id)->firstOrFail();
        $selected_task_type_ids = $project->taskTypes->pluck('id')->toArray();
        $task_types = TaskType::select('id', 'name')->where('enabled', true)->orWhereIn('id', $selected_task_type_ids)->get()->toArray();

        foreach ($task_types as $key => $task_type) {
            $task_types[$key]['checked'] = '';
            if (in_array($task_type['id'], $selected_task_type_ids)) {
                $task_types[$key]['checked'] = 'checked';
            }
        }
        return view('projects.edit', compact('project', 'task_types'));
    }

    public function update(Request $request, $id)
    {
        $project = Project::findOrFail($id);

        $validatedData = $request->validate([
            'task_types' => 'nullable|array',
            'task_types.*' => 'required|numeric|exists:task_types,id',
        ]);

        $task_types = $validatedData['task_types'] ?? [];

        $request->validate([
            'name' => 'required|string|max:255|unique:projects,name,' . $id,
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'enabled' => 'required|boolean',
            'closed' => 'required|boolean',
        ]);

        DB::transaction(function () use ($project, $request, $task_types, $id) {
            $project->update([
                'name' => $request->name,
                'description' => $request->description,
                'color' => $request->color,
                'enabled' => $request->enabled,
                'closed' => $request->closed,
                'updated_by' => Auth::user()->id,
                'updated_at' => now(),
            ]);

            ProjectTaskType::where('project_id', $id)
                ->whereNotIn('task_type_id', $task_types)
                ->delete();

            $existingTaskTypes = ProjectTaskType::where('project_id', $id)
                ->pluck('task_type_id')
                ->toArray();

            $newTaskTypes = array_diff($task_types, $existingTaskTypes);

            $insertData = array_map(function ($taskTypeId) use ($id) {
                return [
                    'project_id' => $id,
                    'task_type_id' => $taskTypeId
                ];
            }, $newTaskTypes);

            if (!empty($insertData)) {
                ProjectTaskType::insert($insertData);
            }

        });
        return redirect()->route('projects.index')->with('success', 'Project updated successfully.');
    }

    public function add_users(Request $request, $id)
    {
        $project = Project::findOrFail($id);
        $users = $request->input('users', []);
        $currentUserId = auth()->id(); // Get the ID of the currently authenticated user

        foreach ($users as $userId) {
            DB::table('project_users')->updateOrInsert(
                ['user_id' => $userId, 'project_id' => $project->id],
                ['created_by' => $currentUserId, 'created_at' => now()]
            );
        }

        return redirect()->route('projects.edit', $project->id)->with('success', 'Users added successfully.');
    }


    public function add_users_bulk(Request $request, $id)
    {
        $project = Project::findOrFail($id);
        $emails = explode("\n", $request->input('emails', []));
        $currentUserId = auth()->id(); // Get the ID of the currently authenticated user

        foreach ($emails as $email) {
            $email = trim($email);

            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $user = User::where('email', $email)->first();

                if ($user) {
                    // Add user to the project
                    DB::table('project_users')->updateOrInsert(
                        ['user_id' => $user->id, 'project_id' => $project->id],
                        ['created_by' => $currentUserId, 'created_at' => now()]
                    );
                }
            }
        }

        return redirect()->route('projects.edit', $project->id)->with('success', 'Users added successfully.');
    }

    public function remove_user(Request $request, $projectId, $userId)
    {
        $project = Project::findOrFail($projectId);
        $project->members()->detach($userId);

        return redirect()->route('projects.edit', $projectId)->with('success', 'User removed successfully.');
    }

    public function add_groups(Request $request, $id)
    {
        $project = Project::findOrFail($id);
        $groups = $request->input('groups', []);
        $currentUserId = auth()->id(); // Get the ID of the currently authenticated user

        foreach ($groups as $group_id) {
            DB::table('project_groups')->updateOrInsert(
                ['group_id' => $group_id, 'project_id' => $project->id],
                ['created_by' => $currentUserId, 'created_at' => now()]
            );
        }

        return redirect()->route('projects.edit', $project->id)->with('success', 'Group added successfully.');
    }


    public function remove_group(Request $request, $projectId, $group_id)
    {
        $project = Project::findOrFail($projectId);
        $project->groups()->detach($group_id);

        return redirect()->route('projects.edit', $projectId)->with('success', 'Group removed successfully.');
    }

    public function destroy($id)
    {
        $project = Project::findOrFail($id);
        $tasks = Task::where('project_id', $id)->count();
        if($tasks > 0) {
            return redirect()->back()->with('error', $tasks . ' exist against the project.');
        }
        DB::beginTransaction();
        try {
            DB::table('project_groups')->where('project_id', $id)->delete();
            DB::table('project_task_types')->where('project_id', $id)->delete();
            DB::table('project_users')->where('project_id', $id)->delete();

            $project->delete();
            DB::commit();
            return redirect()->route('projects.index')->with('success', 'Project deleted successfully.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to delete project: ' . $e->getMessage());
        }
    }

}
