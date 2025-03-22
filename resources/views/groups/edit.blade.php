<!DOCTYPE html>
@extends('layouts.app')

@section('content')
<style type="text/css">
    td {
        padding: 3px 8px !important;
    }
    .select2-container--default .select2-results__option[aria-selected=true] {
        display: none !important;
    }
</style>
<link href="{{ asset('css/plugins/select2/select2.min.css') }}" rel="stylesheet">
<script src="{{ asset('js/plugins/select2/select2.full.min.js') }}"></script>

<div class="ibox pt-2">
    <div class="ibox-title">
        <h5>Edit Group</h5>
        <div class="ibox-tools">
            @if(session('user_routes')['groups.index'] ?? false)
                <a href="{{ route('groups.index') }}" class="btn btn-primary btn-xs">Manage Groups</a>
            @endif
        </div>
    </div>
    <div class="ibox-content">
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
                    <div class="panel-heading"> User Info </div>
                    <div class="panel-body" style="max-height: 750px; overflow: auto;">
                        <form action="{{ route('groups.update', $group->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="form-group">
                                <label for="name">Group Name</label>
                                <input type="text" name="name" class="form-control" value="{{ $group->name }}" required>
                            </div>


                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea name="description" class="form-control">{{ $group->description }}</textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="enabled">Enabled</label>
                                <select name="enabled" class="form-control" id="enabled">
                                    <option value="">Select an option</option>
                                    <option value="1" {{ ($group->enabled) ? 'selected' : '' }}>Yes</option>
                                    <option value="0" {{ ($group->enabled) ? '' : 'selected' }}>No</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="parent_id">Parent</label>
                                <select name="parent_id" class="form-control">
                                    <option value="">Select Parent Group</option>
                                    @foreach ($parent_candidates as $candidate)
                                        <option value="{{ $candidate['id'] }}" {{ ($candidate['id'] == $group->parent_id) ? 'selected' : '' }}>
                                            {{ $candidate['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @if(session('user_routes')['groups.update'] ?? false)
                                <button type="submit" class="btn btn-primary">Update Group</button>
                            @else
                                <div class="alert alert-danger"> You do not have permission to save this form </div>
                            @endif
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="panel panel-success" id="group-members-box">
                    <div class="panel-heading">
                        Group Members

                        @if(session('user_routes')['groups.add_users_bulk'] ?? false)
                            <div class="ibox-tools mr-2" style="top:10px;">
                                <button type="button" class="btn btn-info btn-xs" data-toggle="modal" data-target="#bulk-upload-users"> Upload file to add users </button>
                            </div>
                        @endif

                    </div>
                    <div class="panel-body" style="max-height: 750px; overflow: auto;">
                        
                        @if (session('success'))
                            <div class="alert alert-success alert-dismissable">
                                <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
                                {{ session('success') }}
                            </div>
                        @endif
                        
                        @if(session('user_routes')['groups.add_users'] ?? false)
                        <form action="{{ route('groups.add_users', $group->id) }}" method="POST">
                            <div id="add-user-to-group-form">
                                @csrf
                                <div class="form-group">
                                    <select id="users-search-field" name="users[]" class="form-control" multiple="multiple"></select>
                                    <button type="submit" class="btn btn-primary btn-xs mt-2 mb-2">Add Users</button>
                                </div>
                            </div>
                        </form>
                        @else 
                            <div class="alert alert-danger"> You do not have permission to add users </div>
                        @endif

                        @if(isset($group->members) && count($group->members) > 0)
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th> Name </th>
                                        <th> Email </th>
                                        <th> Phone Number </th>
                                        <th> Role </th>
                                        @if(session('user_routes')['groups.remove_user'] ?? false)
                                        <th> Actions </th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($group->members as $row)
                                    <tr>
                                        <td> {{ $row->name }} </td>
                                        <td> {{ $row->email }} </td>
                                        <td> {{ $row->phone }} </td>
                                        <td> {{ $row->role->name ?? '' }} </td>
                                        @if(session('user_routes')['groups.remove_user'] ?? false)
                                        <td>
                                            <form action="{{ route('groups.remove_user', [$group->id, $row->id]) }}" method="POST" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-xs">Remove</button>
                                            </form>
                                        </td>
                                        @endif
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="alert alert-info alert-dismissable">
                                No Users Assigned to Group.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@if(session('user_routes')['groups.add_users_bulk'] ?? false)
    <div class="modal inmodal fade" id="bulk-upload-users" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                    <h4 class="modal-title">Bulk Add Users to Group</h4>
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
                    <form action="{{ route('groups.add_users_bulk', $group->id) }}" method="POST">
                        @csrf
                        <textarea name="emails" class="form-control" rows="10" placeholder="email1&#10;email2&#10;.&#10;.&#10;."></textarea>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-white" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Add Users to Group</button>
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
                        group_id: {{ $group->id }} // The search term
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
</script>
@endsection