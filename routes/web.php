<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ServiceDomainController;
use App\Http\Controllers\ServicePriorityController;
use App\Http\Controllers\ServiceRequestController;
use App\Http\Controllers\WorkflowController;
use App\Http\Controllers\CustomFieldController;
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

    Route::resource('service_priorities', ServicePriorityController::class);
    

    Route::resource('service_domains', ServiceDomainController::class);
    Route::post('/service_domains/{service_domain}/add-users', [ServiceDomainController::class, 'add_users'])->name('service_domains.add_users');
    Route::post('/service_domains/{id}/add-users-bulk', [ServiceDomainController::class, 'add_users_bulk'])->name('service_domains.add_users_bulk');
    Route::delete('service_domains/{service_domain}/remove-user/{user}', [ServiceDomainController::class, 'remove_user'])->name('service_domains.remove_user');
    Route::post('/service_domains/{service_domain}/add-groups', [ServiceDomainController::class, 'add_groups'])->name('service_domains.add_groups');
    Route::delete('service_domains/{service_domain}/remove-group/{group}', [ServiceDomainController::class, 'remove_group'])->name('service_domains.remove_group');
    
    Route::resource('custom_fields', CustomFieldController::class);

    Route::resource('services', ServiceController::class);
    Route::post('services/save-service-custom-fields', [ServiceController::class, 'save_service_custom_fields'])->name('services.save_service_custom_fields');
    Route::post('services/get-fields', [ServiceController::class, 'get_fields'])->name('services.get_fields');

    Route::resource('workflows', WorkflowController::class);
    Route::post('workflows/save-workflows-statuses', [WorkflowController::class, 'save_workflow_statuses'])->name('workflows.save_workflow_statuses');
    
    Route::resource('service_requests', ServiceRequestController::class);
    Route::post('service_requests/get-fields', [ServiceRequestController::class, 'get_fields'])->name('service_requests.get_fields');
    Route::post('service_requests/get-service_request-data', [ServiceRequestController::class, 'get_service_request_data'])->name('service_requests.get_service_request_data');
    Route::delete('service_requests/{id}/rm-file', [ServiceRequestController::class, 'rm_file'])->name('service_requests.rm_file');
    Route::get('service_requests/download-file/{id}', [ServiceRequestController::class, 'download_file'])->name('service_requests.download_file');
    Route::post('service_requests/search-service_domain-groups', [ServiceRequestController::class, 'search_service_domain_groups'])->name('service_requests.search_service_domain_groups');
    Route::post('service_requests/search-group-users', [ServiceRequestController::class, 'search_group_users'])->name('service_requests.search_group_users');
    Route::post('service_requests/add-comment', [ServiceRequestController::class, 'add_comment'])->name('service_requests.add_comment');
    Route::post('/service_requests/download', [ServiceRequestController::class, 'download'])->name('service_requests.download');
    
    Route::resource('sla_rules', SlaController::class);

});