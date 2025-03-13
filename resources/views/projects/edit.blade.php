@extends('layouts.app')

@section('content')
<style type="text/css">
    td {
        padding: 3px 8px !important;
    }
    .select2-container--default .select2-results__option[aria-selected=true] {
        display: none !important;
    }
    #color {
        border-radius: 3px;
    }
    input[type="checkbox"] {
        transform: scale(1.2);
    }
    .form-check {
        padding: 0px 34px;
    }
</style>
<link href="{{ asset('css/plugins/select2/select2.min.css') }}" rel="stylesheet">
<script src="{{ asset('js/plugins/select2/select2.full.min.js') }}"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<div class="ibox pt-2">
    <div class="ibox-title">
        <h5>Edit Project</h5>
        <div class="ibox-tools">
            <a href="{{ route('projects.index') }}" class="btn btn-primary btn-xs"> Manage Projects </a>
            @if(session('user_routes')['projects.add_users_bulk'] ?? false)
                <a class="btn btn-primary btn-xs" data-toggle="modal" data-target="#bulk-upload-users"> Upload file to add users </a>
            @endif
        </div>
    </div>
    <div class="ibox-content">
        @if (session('success'))
            <div class="alert alert-success alert-dismissable">
                <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissable">
                <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
                {{ session('error') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="row">
            <div class="col-6">
                <div class="panel panel-primary">
                    <div class="panel-heading">Project Info</div>
                    <div class="panel-body" style="max-height: 750px; overflow: auto;">
                        <form action="{{ route('projects.update', $project->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="form-group">
                                <label for="name">Project Name</label>
                                <input type="text" name="name" class="form-control" value="{{ $project->name }}" required>
                            </div>

                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea name="description" class="form-control" id="description">{{ old('description') }}</textarea>
                            </div>

                            <div class="form-group">
                                <label for="enabled">Enabled</label>
                                <select name="enabled" class="form-control" id="enabled">
                                    <option value="">Select an option</option>
                                    <option value="1" {{ $project->enabled ? 'selected' : '' }}>Yes</option>
                                    <option value="0" {{ $project->enabled ? '' : 'selected' }}>No</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="closed">Project Closed</label>
                                <select name="closed" class="form-control" id="closed">
                                    <option value="">Select an option</option>
                                    <option value="1" {{ $project->closed ? 'selected' : '' }}>Yes</option>
                                    <option value="0" {{ $project->closed ? '' : 'selected' }}>No</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="color">Color</label>
                                <input type="color" class="form-control p-0" name='color' id="color" value="{{ $project->color }}"  title="Choose your color">
                            </div>
                            <div class="form-group">
                                <label>Task Types <span class="text-danger">*</span></label>
                                @foreach ($task_types as $task_type)
                                    <div class="form-check">
                                        <input 
                                            type="checkbox" 
                                            name="task_types[{{$task_type['id']}}]" 
                                            value="{{$task_type['id']}}" 
                                            class="form-check-input" 
                                            id="task_type-{{$task_type['id']}}" 
                                            {{ !empty(old()) ? (old('task_types') !== null && array_key_exists($task_type['id'], old('task_types')) ? 'checked' : '') : ($task_type['checked'] ? 'checked' : '') }}>
                                        <label class="form-check-label" for="task_type-{{$task_type['id']}}">{{$task_type['name']}}</label>
                                    </div>
                                @endforeach

                            </div>
                            
                            <button type="submit" class="btn btn-primary">Update Project</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="panel panel-info" id="group-members-box">
                    <div class="panel-heading"> Project Groups </div>
                    <div class="panel-body" style="max-height: 750px; overflow: auto;">
                        @if(session('user_routes')['projects.add_groups'] ?? false)
                        <form action="{{ route('projects.add_groups', $project->id) }}" method="POST">
                            <div id="add-user-to-group-form">
                                @csrf
                                <div class="form-group">
                                    <select id="groups-search-field" name="groups[]" class="form-control" multiple="multiple"></select>
                                    <button type="submit" class="btn btn-primary btn-xs mt-2 mb-2">Add Groups</button>
                                </div>
                            </div>
                        </form>
                        @endif

                        @if(isset($project->groups) && count($project->groups) > 0)
                        <p>
                            @foreach($project->groups as $gr)
                                <div class="d-inline-block mr-2">
                                    @if(session('user_routes')['groups.show'] ?? false)
                                        <a href="{{ route('groups.show', $gr->id) }}" target="_blank" class="btn btn-default">
                                            <i class="fa fa-group"></i>&nbsp;&nbsp;<strong> {{ $gr->name }}</strong> &nbsp; ({{ $gr->members_count }} users)&nbsp;&nbsp;
                                            @if(session('user_routes')['projects.remove_group'] ?? false)
                                                <form action="{{ route('projects.remove_group', [$project->id, $gr->id]) }}" method="POST" style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="delete-button btn btn-danger btn-xs"><i class="fa fa-times"></i></button>
                                                </form>
                                            @endif
                                        </a>
                                    @else
                                        <span class="btn btn-default">
                                            <i class="fa fa-group"></i>&nbsp;&nbsp;<strong> {{ $gr->name }}</strong> &nbsp; ({{ $gr->members_count }} users)
                                            @if(session('user_routes')['projects.remove_group'] ?? false)
                                                <form action="{{ route('projects.remove_group', [$project->id, $gr->id]) }}" method="POST" style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="delete-button btn btn-danger btn-xs"><i class="fa fa-times"></i></button>
                                                </form>
                                            @endif
                                        </span>
                                    @endif

                                </div>
                            @endforeach
                        </p>
                        @else
                            <div class="alert alert-info alert-dismissable">
                                No Groups Assigned to Project.
                            </div>
                        @endif
                    </div>
                </div>
                <div class="panel panel-success" id="group-members-box">
                    <div class="panel-heading">
                        Project Members
                    </div>
                    <div class="panel-body" style="max-height: 750px; overflow: auto;">

                        @if(session('user_routes')['projects.add_users'] ?? false)
                        <form action="{{ route('projects.add_users', $project->id) }}" method="POST">
                            <div id="add-user-to-group-form">
                                @csrf
                                <div class="form-group">
                                    <select id="users-search-field" name="users[]" class="form-control" multiple="multiple"></select>
                                    <button type="submit" class="btn btn-primary btn-xs mt-2 mb-2">Add Users</button>
                                </div>
                            </div>
                        </form>
                        @endif

                        @if(isset($project->members) && count($project->members) > 0)
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th> Name </th>
                                        <th> Email </th>
                                        <th> Phone Number </th>
                                        <th> Role </th>
                                        @if(session('user_routes')['projects.remove_user'] ?? false)
                                        <th> Actions </th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($project->members as $row)
                                    <tr>
                                        <td> {{ $row->name }} </td>
                                        <td> {{ $row->email }} </td>
                                        <td> {{ $row->phone }} </td>
                                        <td> {{ $row->role->name ?? '' }} </td>
                                        @if(session('user_routes')['projects.remove_user'] ?? false)
                                        <td>
                                            <form action="{{ route('projects.remove_user', [$project->id, $row->id]) }}" method="POST" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-xs delete-button">Remove</button>
                                            </form>
                                        </td>
                                        @endif
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="alert alert-info alert-dismissable">
                                No Users Assigned to Project.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@if(session('user_routes')['projects.add_users_bulk'] ?? false)
<div class="modal inmodal fade" id="bulk-upload-users" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                <h4 class="modal-title">Bulk Add Users to Project</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-info alert-dismissable">
                    <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
                    Notes:
                    <ul>
                        <li>Users emails are used as user unique identifiers.</li>
                        <li>Paste a list of user emails separated by new line in the text field below to include in bulk.</li>
                        <li>Email will be <strong>skipped</strong> if it does not exist in the system.</li>
                        <li>Invalid emails will be <strong>skipped</strong>.</li>
                        @if(session('user_routes')['users.download'] ?? false)
                        <li>
                            <form action="{{ route('users.download') }}" method="POST" target="_blank">
                                @csrf
                                <button type="submit" class="btn btn-info btn-xs">Download Users</button>
                            </form>
                        </li>
                        @endif
                    </ul>
                </div>
                <form action="{{ route('projects.add_users_bulk', $project->id) }}" method="POST">
                    @csrf
                    <textarea name="emails" class="form-control" rows="10" placeholder="email1&#10;email2&#10;.&#10;.&#10;."></textarea>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-white" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Users to Project</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endif
<script>
    $(document).ready(function() {
        // Set up global AJAX settings
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Initialize Select2 for user search field
        $('#users-search-field').select2({
            width: '100%', // Ensure the width is 100%
            allowClear: true,
            placeholder: 'Search for users',
            delay: 1000, // Delay in ms before starting the search
            minimumInputLength: 2, // Minimum characters to trigger search
            ajax: {
                url: '{{ route("users.search") }}', // Route for searching users
                dataType: 'json',
                type: 'POST',
                delay: 250, // Delay in ms to prevent flooding requests
                data: function(params) {
                    return {
                        q: params.term, // The search term
                        enabled_only:true,
                        project_id: {{$project->id}}
                    };
                },
                processResults: function(data) {
                    return {
                        results: $.map(data, function(item) {
                            // Format the label for display
                            let label = item.name + ' (' + item.email + ')';
                            if (typeof item.phone !== 'undefined' && item.phone !== null) {
                                label = item.name + ' (' + item.email + ' : ' + item.phone + ')';
                            }
                            return {
                                id: item.id,
                                text: label
                            };
                        })
                    };
                },
                cache: true
            }
        });

        // Initialize Select2 for group search field
        $('#groups-search-field').select2({
            width: '100%', // Ensure the width is 100%
            allowClear: true,
            placeholder: 'Search for groups',
            delay: 1000, // Delay in ms before starting the search
            minimumInputLength: 2, // Minimum characters to trigger search
            ajax: {
                url: '{{ route("groups.search") }}', // Route for searching groups
                dataType: 'json',
                type: 'POST',
                delay: 250, // Delay in ms to prevent flooding requests
                data: function(params) {
                    return {
                        q: params.term, // The search term
                        enabled_only: true,
                        project_id: {{$project->id}}
                    };
                },
                processResults: function(data) {
                    return {
                        results: $.map(data, function(item) {
                            // Format the label for display
                            return {
                                id: item.id,
                                text: item.name // Only display the group's name
                            };
                        })
                    };
                },
                cache: true
            }
        });


        function selectAll() {
            var checkboxes = document.querySelectorAll('.panel-body input[type="checkbox"]');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = true;
            });
        }

        function unselectAll() {
            var checkboxes = document.querySelectorAll('.panel-body input[type="checkbox"]');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = false;
            });
        }
    });

    $(document).on('click', '.delete-button', function (event) {
        event.preventDefault();

        const form = $(this).closest('form');
        if (!form.length) {
            console.error('Form not found!');
            return;
        }

        Swal.fire({
            title: 'Are you sure?',
            text: 'You won\'t be able to revert this!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: "Deleting...",
                    text: "Please wait",
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                form.submit();
            }
        });
    });
</script>
@endsection