<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\TaskPriorityController;
use App\Http\Controllers\TaskTypeController;
use App\Http\Controllers\WorkflowController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\CustomFieldController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\SlaController;
use App\Http\Middleware\CheckUserRoleRoute;
use App\Models\Group;

Route::GET('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::POST('/login', [AuthController::class, 'login'])->name('login.submit');
Route::POST('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth', CheckUserRoleRoute::class])->group(function () {

    Route::GET('/', function () {
        return view('home');
    })->name('home');

    Route::resource('roles', RoleController::class);

    Route::resource('users', UserController::class);
    Route::post('users/user_role_routes', [UserController::class, 'user_role_routes'])->name('user_role_routes');
    Route::post('/users/search', [UserController::class, 'search'])->name('users.search');
    Route::post('/users/download', [UserController::class, 'download'])->name('users.download');

    // Route::resource('groups', GroupController::class);
    Route::get('groups', [GroupController::class, 'index'])->name('groups.index');
    Route::get('groups/create', [GroupController::class, 'create'])->name('groups.create');
    Route::post('groups', [GroupController::class, 'store'])->name('groups.store');
    Route::get('groups/{group}', [GroupController::class, 'show'])->name('groups.show');
    Route::get('groups/{group}/edit', [GroupController::class, 'edit'])->name('groups.edit');
    Route::put('groups/{group}', [GroupController::class, 'update'])->name('groups.update');
    // Route::delete('groups/{group}', [GroupController::class, 'destroy'])->name('groups.destroy');
    Route::delete('groups/{group}', function (Group $group) {
        if ($group->created_by == Auth::id() && !$group->enabled) {
            $group->delete();
            return redirect()->route('groups.index')->with('success', 'Group deleted successfully.');
        }
        return redirect()->route('groups.index')->with('error', 'You are not authorized to delete this group, or the group is enabled.');
    })->name('groups.destroy');

    Route::post('/groups/search', [GroupController::class, 'search'])->name('groups.search');
    Route::post('/groups/{group}/add-users', [GroupController::class, 'add_users'])->name('groups.add_users');
    Route::post('/groups/{id}/add-users-bulk', [GroupController::class, 'add_users_bulk'])->name('groups.add_users_bulk');
    Route::delete('groups/{group}/remove-user/{user}', [GroupController::class, 'remove_user'])->name('groups.remove_user');

    Route::resource('statuses', StatusController::class);
    Route::post('/statuses/reorder', [StatusController::class, 'reorder'])->name('statuses.reorder');

    Route::resource('task_priorities', TaskPriorityController::class);
    

    Route::resource('projects', ProjectController::class);
    Route::post('/projects/{project}/add-users', [ProjectController::class, 'add_users'])->name('projects.add_users');
    Route::post('/projects/{id}/add-users-bulk', [ProjectController::class, 'add_users_bulk'])->name('projects.add_users_bulk');
    Route::delete('projects/{project}/remove-user/{user}', [ProjectController::class, 'remove_user'])->name('projects.remove_user');
    Route::post('/projects/{project}/add-groups', [ProjectController::class, 'add_groups'])->name('projects.add_groups');
    Route::delete('projects/{project}/remove-group/{group}', [ProjectController::class, 'remove_group'])->name('projects.remove_group');
    
    Route::resource('custom_fields', CustomFieldController::class);

    Route::resource('task_types', TaskTypeController::class);
    Route::post('task_types/save-task-type-custom-fields', [TaskTypeController::class, 'save_task_type_custom_fields'])->name('task_types.save_task_type_custom_fields');
    Route::post('task_types/get-fields', [TaskTypeController::class, 'get_fields'])->name('task_types.get_fields');

    Route::resource('workflows', WorkflowController::class);
    Route::post('workflows/save-workflows-statuses', [WorkflowController::class, 'save_workflow_statuses'])->name('workflows.save_workflow_statuses');
    
    Route::resource('tasks', TaskController::class);
    Route::post('tasks/get-fields', [TaskController::class, 'get_fields'])->name('tasks.get_fields');
    Route::post('tasks/get-task-data', [TaskController::class, 'get_task_data'])->name('tasks.get_task_data');
    Route::delete('tasks/{id}/rm-file', [TaskController::class, 'rm_file'])->name('tasks.rm_file');
    Route::get('tasks/download-file/{id}', [TaskController::class, 'download_file'])->name('tasks.download_file');

    
    Route::resource('sla_rules', SlaController::class);

});