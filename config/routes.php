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
            'routes' => ['users.show-web-GET', 'user_role_routes-web-POST'],
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
    #SERVICE PRIORITIES
        'service_priorities-list' => [
            'routes' => ['service_priorities.index-web-GET'],
            'description' => 'Service Priorities Table View',
            'public' => true,
        ],
        'service_priorities-create' => [
            'routes' => ['service_priorities.create-web-GET', 'service_priorities.store-web-POST'],
            'description' => 'Create Service Priorities',
            'public' => true,
        ],
        'service_priorities-edit' => [
            'routes' => ['service_priorities.edit-web-GET','service_priorities.update-web-PUT'],
            'description' => 'Edit Service Priorities',
            'public' => true,
        ],
        'service_priorities-view' => [
            'routes' => ['service_priorities.show-web-GET'],
            'description' => 'View Service Priorities',
            'public' => false,
        ],
        'service_priorities-delete' => [
            'routes' => ['service_priorities.destroy-web-DELETE'],
            'description' => 'Delete Service Priorities',
            'public' => true,
        ],
    #SERVICES
        'service-list' => [
            'routes' => ['services.index-web-GET'],
            'description' => 'Service Catelog Table View',
            'public' => true,
        ],
        'service-create' => [
            'routes' => ['services.create-web-GET', 'services.store-web-POST'],
            'description' => 'Create Services',
            'public' => true,
        ],
        'service-edit' => [
            'routes' => ['services.edit-web-GET','services.update-web-PUT','services.save_service_custom_fields-web-POST'],
            'description' => 'Edit Services',
            'public' => true,
        ],
        'service-view' => [
            'routes' => ['services.show-web-GET'],
            'description' => 'View Services',
            'public' => true,
        ],
        'service-delete' => [
            'routes' => ['services.destroy-web-DELETE'],
            'description' => 'Delete Services',
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
    #SERVICE DOMAIN
        'service_domain-list' => [
            'routes' => ['service_domains.index-web-GET'],
            'description' => 'Service Domains Table View',
            'public' => true,
        ],
        'service_domain-create' => [
            'routes' => ['service_domains.create-web-GET', 'service_domains.store-web-POST'],
            'description' => 'Create Service Domains',
            'public' => true,
        ],
        'service_domain-edit' => [
            'routes' => ['service_domains.edit-web-GET','service_domains.update-web-PUT'],
            'description' => 'Edit Service Domains',
            'public' => true,
        ],
        'service_domain-add-groups' => [
            'routes' => ['service_domains.add_groups-web-POST', 'groups.search-web-POST'],
            'description' => 'Add Groups to Service Domains',
            'public' => true,
        ],
        'service_domain-remove-groups' => [
            'routes' => ['service_domains.remove_group-web-DELETE'],
            'description' => 'Remove Groups from Service Domains',
            'public' => true,
        ],
        'service_domain-add-users' => [
            'routes' => ['service_domains.add_users-web-POST', 'service_domains.add_users_bulk-web-POST', 'users.search-web-POST'],
            'description' => 'Add Users to Service Domains',
            'public' => true,
        ],
        'service_domain-remove-users' => [
            'routes' => ['service_domains.remove_user-web-DELETE'],
            'description' => 'Remove Users from Service Domains',
            'public' => true,
        ],
        'service_domain-view' => [
            'routes' => ['service_domains.show-web-GET'],
            'description' => 'View Service Domains',
            'public' => true,
        ],
        'service_domain-delete' => [
            'routes' => ['service_domains.destroy-web-DELETE'],
            'description' => 'Delete Service Domains',
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
    #SERVICE REQUESTS
        'service_request-list' => [
            'routes' => ['service_requests.index-web-GET', 'service_requests.get_service_request_data-web-POST'],
            'description' => 'Service Requests Table View',
            'public' => true,
        ],
        'service_request-create' => [
            'routes' => ['service_requests.create-web-GET', 'service_requests.store-web-POST', /*'services.get_fields-web-POST',*/ 'service_requests.get_fields-web-POST','service_requests.search_service_domain_groups-web-POST','service_requests.search_group_users-web-POST',],
            'description' => 'Create Service Requests',
            'public' => true,
        ],
        'service_request-edit' => [
            'routes' => ['service_requests.edit-web-GET','service_requests.update-web-PUT', /*'services.get_fields-web-POST', */'services.get_fields-web-POST','service_requests.search_service_domain_groups-web-POST','service_requests.search_group_users-web-POST',],
            'description' => 'Edit Service Requests',
            'public' => true,
        ],
        'service_request-comment' => [
            'routes' => ['service_requests.add_comment-web-POST'],
            'description' => 'Comment on Service Requests',
            'public' => true,
        ],
        'service_request-view' => [
            'routes' => ['service_requests.show-web-GET'],
            'description' => 'View Service Requests',
            'public' => true,
        ],
        'service_request-download' => [
            'routes' => ['service_requests.download-web-POST'],
            'description' => 'Download Service Requests',
            'public' => true,
        ],
        'service_request-download-file' => [
            'routes' => ['service_requests.download_file-web-GET'],
            'description' => 'Download Service Request Files',
            'public' => true,
        ],
        'service_request-delete' => [
            'routes' => ['service_requests.destroy-web-DELETE'],
            'description' => 'Delete Service Requests',
            'public' => true,
        ],
        'service_request-rm-file' => [
            'routes' => ['service_requests.rm_file-web-DELETE'],
            'description' => 'Delete Service Request Files',
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