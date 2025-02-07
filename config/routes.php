<?php

return [
    'home' => [
        'routes' => ['home-web-GET'],
        'description' => 'Home Page',
        'public' => true,
    ],
    #ROLES
        'role-list' => [
            'routes' => ['roles.index-web-GET'],
            'description' => 'Roles Table View',
            'public' => true,
        ],
        'role-create' => [
            'routes' => ['roles.create-web-GET', 'roles.store-web-POST'],
            'description' => 'Create Roles',
            'public' => true,
        ],
        'role-edit' => [
            'routes' => ['roles.edit-web-GET','roles.update-web-PUT'],
            'description' => 'Edit Roles',
            'public' => true,
        ],
        'role-view' => [
            'routes' => ['roles.show-web-GET'],
            'description' => 'View Roles',
            'public' => true,
        ],
        'role-delete' => [
            'routes' => ['roles.destroy-web-DELETE'],
            'description' => 'Delete Roles',
            'public' => true,
        ],
    #USERS
        'user-list' => [
            'routes' => ['users.index-web-GET'],
            'description' => 'Users Table View',
            'public' => true,
        ],
        'user-create' => [
            'routes' => ['users.create-web-GET', 'users.store-web-POST'],
            'description' => 'Create Users',
            'public' => true,
        ],
        'user-edit' => [
            'routes' => ['users.edit-web-GET','users.update-web-PUT'],
            'description' => 'Edit Users',
            'public' => true,
        ],
        'user-additional-acl' => [
            'routes' => ['user_role_routes-web-POST'],
            'description' => 'Edit User Additional Access Control',
            'public' => true,
        ],
        'user-view' => [
            'routes' => ['users.show-web-GET'],
            'description' => 'View Users',
            'public' => true,
        ],
        'user-delete' => [
            'routes' => ['users.destroy-web-DELETE'],
            'description' => 'Delete Users',
            'public' => true,
        ],
        'user-download' => [
            'routes' => ['users.download-web-POST'],
            'description' => 'Download Users',
            'public' => true,
        ],
    #GROUPS
        'group-list' => [
            'routes' => ['groups.index-web-GET'],
            'description' => 'Groups Table View',
            'public' => true,
        ],
        'group-create' => [
            'routes' => ['groups.create-web-GET', 'groups.store-web-POST'],
            'description' => 'Create Groups',
            'public' => true,
        ],
        'group-add-users' => [
            'routes' => ['groups.add_users-web-POST','groups.add_users_bulk-web-POST','users.search-web-POST'],
            'description' => 'Add Users to Groups',
            'public' => true,
        ],        
        'group-remove-users' => [
            'routes' => ['groups.remove_user-web-DELETE'],
            'description' => 'Remove Users to Groups',
            'public' => true,
        ],
        'group-edit' => [
            'routes' => ['groups.edit-web-GET','groups.update-web-PUT'],
            'description' => 'Edit Groups',
            'public' => true,
        ],
        'group-view' => [
            'routes' => ['groups.show-web-GET'],
            'description' => 'View Groups',
            'public' => true,
        ],
        'group-delete' => [
            'routes' => ['groups.destroy-web-DELETE'],
            'description' => 'Delete Groups',
            'public' => true,
        ],
    #STATUSES
        'status-list' => [
            'routes' => ['statuses.index-web-GET'],
            'description' => 'Statuses Table View',
            'public' => true,
        ],
        'status-create' => [
            'routes' => ['statuses.create-web-GET', 'statuses.store-web-POST'],
            'description' => 'Create Statuses',
            'public' => true,
        ],
        'status-edit' => [
            'routes' => ['statuses.edit-web-GET','statuses.update-web-PUT'],
            'description' => 'Edit Statuses',
            'public' => true,
        ],
        'status-reorder' => [
            'routes' => ['statuses.reorder-web-POST'],
            'description' => 'Change Status Order',
            'public' => true,
        ],
        'status-view' => [
            'routes' => ['statuses.show-web-GET'],
            'description' => 'View Statuses',
            'public' => false,
        ],
        'status-delete' => [
            'routes' => ['statuses.destroy-web-DELETE'],
            'description' => 'Delete Statuses',
            'public' => true,
        ],
    #TASK PRIORITIES
        'task_priorities-list' => [
            'routes' => ['task_priorities.index-web-GET'],
            'description' => 'Task Priorities Table View',
            'public' => true,
        ],
        'task_priorities-create' => [
            'routes' => ['task_priorities.create-web-GET', 'task_priorities.store-web-POST'],
            'description' => 'Create Task Priorities',
            'public' => true,
        ],
        'task_priorities-edit' => [
            'routes' => ['task_priorities.edit-web-GET','task_priorities.update-web-PUT'],
            'description' => 'Edit Task Priorities',
            'public' => true,
        ],
        'task_priorities-view' => [
            'routes' => ['task_priorities.show-web-GET'],
            'description' => 'View Task Priorities',
            'public' => false,
        ],
        'task_priorities-delete' => [
            'routes' => ['task_priorities.destroy-web-DELETE'],
            'description' => 'Delete Task Priorities',
            'public' => true,
        ],
    #TASK TYPES
        'task-type-list' => [
            'routes' => ['task_types.index-web-GET'],
            'description' => 'Task Type Table View',
            'public' => true,
        ],
        'task-type-create' => [
            'routes' => ['task_types.create-web-GET', 'task_types.store-web-POST'],
            'description' => 'Create Task Types',
            'public' => true,
        ],
        'task-type-edit' => [
            'routes' => ['task_types.edit-web-GET','task_types.update-web-PUT','task_types.save_task_type_custom_fields-web-POST'],
            'description' => 'Edit Task Types',
            'public' => true,
        ],
        'task-type-view' => [
            'routes' => ['task_types.show-web-GET'],
            'description' => 'View Task Types',
            'public' => true,
        ],
        'task-type-delete' => [
            'routes' => ['task_types.destroy-web-DELETE'],
            'description' => 'Delete Task Types',
            'public' => true,
        ],
    #WORKFLOWS
        'workflow-list' => [
            'routes' => ['workflows.index-web-GET'],
            'description' => 'Workflow Table View',
            'public' => true,
        ],
        'workflow-create' => [
            'routes' => ['workflows.create-web-GET', 'workflows.store-web-POST'],
            'description' => 'Create Workflows',
            'public' => true,
        ],
        'workflow-edit' => [
            'routes' => ['workflows.edit-web-GET','workflows.update-web-PUT','workflows.save_workflow_statuses-web-POST'],
            'description' => 'Edit Workflows',
            'public' => true,
        ],
        'workflow-view' => [
            'routes' => ['workflows.show-web-GET'],
            'description' => 'View Workflows',
            'public' => true,
        ],
        'workflow-delete' => [
            'routes' => ['workflows.destroy-web-DELETE'],
            'description' => 'Delete Workflows',
            'public' => true,
        ],
    #PROJECTS
        'project-list' => [
            'routes' => ['projects.index-web-GET'],
            'description' => 'Projects Table View',
            'public' => true,
        ],
        'project-create' => [
            'routes' => ['projects.create-web-GET', 'projects.store-web-POST'],
            'description' => 'Create Projects',
            'public' => true,
        ],
        'project-edit' => [
            'routes' => ['projects.edit-web-GET','projects.update-web-PUT'],
            'description' => 'Edit Projects',
            'public' => true,
        ],
        'project-add-groups' => [
            'routes' => ['projects.add_groups-web-POST', 'groups.search-web-POST'],
            'description' => 'Add Groups to Projects',
            'public' => true,
        ],
        'project-remove-groups' => [
            'routes' => ['projects.remove_group-web-DELETE'],
            'description' => 'Remove Groups from Projects',
            'public' => true,
        ],
        'project-add-users' => [
            'routes' => ['projects.add_users-web-POST', 'projects.add_users_bulk-web-POST', 'users.search-web-POST'],
            'description' => 'Add Users to Projects',
            'public' => true,
        ],
        'project-remove-users' => [
            'routes' => ['projects.remove_user-web-DELETE'],
            'description' => 'Remove Users from Projects',
            'public' => true,
        ],
        'project-view' => [
            'routes' => ['projects.show-web-GET'],
            'description' => 'View Projects',
            'public' => true,
        ],
        'project-delete' => [
            'routes' => ['projects.destroy-web-DELETE'],
            'description' => 'Delete Projects',
            'public' => true,
        ],
    #CUSTOM FIELDS
        'custom_field-list' => [
            'routes' => ['custom_fields.index-web-GET'],
            'description' => 'Custom Fields Table View',
            'public' => true,
        ],
        'custom_field-create' => [
            'routes' => ['custom_fields.create-web-GET', 'custom_fields.store-web-POST'],
            'description' => 'Create Custom Fields',
            'public' => true,
        ],
        'custom_field-edit' => [
            'routes' => ['custom_fields.edit-web-GET','custom_fields.update-web-PUT'],
            'description' => 'Edit Custom Fields',
            'public' => true,
        ],
        'custom_field-view' => [
            'routes' => ['custom_fields.show-web-GET'],
            'description' => 'View Custom Fields',
            'public' => true,
        ],
        'custom_field-delete' => [
            'routes' => ['custom_fields.destroy-web-DELETE'],
            'description' => 'Delete Custom Fields',
            'public' => true,
        ],
    #TASKS
        'task-list' => [
            'routes' => ['tasks.index-web-GET', 'tasks.get_task_data-web-POST'],
            'description' => 'Tasks Table View',
            'public' => true,
        ],
        'task-create' => [
            'routes' => ['tasks.create-web-GET', 'tasks.store-web-POST', /*'task_types.get_fields-web-POST',*/ 'tasks.get_fields-web-POST'],
            'description' => 'Create Tasks',
            'public' => true,
        ],
        'task-edit' => [
            'routes' => ['tasks.edit-web-GET','tasks.update-web-PUT', /*'task_types.get_fields-web-POST', */'task_types.get_fields-web-POST'],
            'description' => 'Edit Tasks',
            'public' => true,
        ],
        'task-view' => [
            'routes' => ['tasks.show-web-GET'],
            'description' => 'View Tasks',
            'public' => true,
        ],
        'task-download-file' => [
            'routes' => ['tasks.download_file-web-GET'],
            'description' => 'Download Task Files',
            'public' => true,
        ],
        'task-delete' => [
            'routes' => ['tasks.destroy-web-DELETE'],
            'description' => 'Delete Tasks',
            'public' => true,
        ],
        'task-rm-file' => [
            'routes' => ['tasks.rm_file-web-DELETE'],
            'description' => 'Delete Task Files',
            'public' => true,
        ],
    #SLA
        'sla-list' => [
            'routes' => ['sla_rules.index-web-GET'],
            'description' => 'SLA Rules Table View',
            'public' => true,
        ],
        'sla-create' => [
            'routes' => ['sla_rules.create-web-GET', 'sla_rules.store-web-POST', 'users.search-web-POST'],
            'description' => 'Create SLA Rules',
            'public' => true,
        ],
        'sla-edit' => [
            'routes' => ['sla_rules.edit-web-GET','sla_rules.update-web-PUT', 'users.search-web-POST'],
            'description' => 'Edit SLA Rules',
            'public' => true,
        ],
        'sla-view' => [
            'routes' => ['sla_rules.show-web-GET'],
            'description' => 'View SLA Rules',
            'public' => true,
        ],
        'sla-delete' => [
            'routes' => ['sla_rules.destroy-web-DELETE'],
            'description' => 'Delete SLA Rules',
            'public' => true,
        ],
];