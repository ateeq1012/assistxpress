@extends('layouts.app')

@section('content')
<style type="text/css">
    th, td {
        padding: 3px 8px !important;
    }
</style>
<div class="ibox pt-2">
    <div class="ibox-title">
        <h5>User Details</h5>
        <div class="ibox-tools">
            <a href="{{ route('users.index') }}" class="btn btn-primary btn-xs">Manage Users</a>
            <a href="{{ route('users.edit', $user->id) }}" class="btn btn-primary btn-xs">Edit User</a>
        </div>
    </div>
    <div class="ibox-content">
        @if (session('error'))
            <div class="alert alert-danger alert-dismissable">
                <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
                {{ session('error') }}
            </div>
        @endif
        <div class="row">
            <div class="col-6">
                <div class="panel panel-primary">
                    <div class="panel-heading"> User Info </div>
                    <div class="panel-body" style="max-height: 400px; overflow: auto;">
                        <table class="table table-bordered table-striped">
                            <tr>
                                <th>Name</th>
                                <td>{{ $user->name }}</td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td>{{ $user->email }}</td>
                            </tr>
                            <tr>
                                <th>Phone Number</th>
                                <td>
                                    @if($user->phone)
                                        <i class="fa fa-phone"> </i> <a> {{ $user->phone }} </a>
                                    @else
                                        <small> {not set} </small>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Role</th>
                                <td>{{ $user->role->name }}</td>
                            </tr> 
                            <tr>
                                <th>Member of Group</th>
                                @if(isset($user->groups) && count($user->groups) > 0)
                                    <td><badge class="badge badge-info">{{ $user->groups->pluck('name')->implode('</badge>,<badge class="badge badge-info">') }}</badge></td>
                                @else
                                    <td><small>No groups assigned</small></badge></td>
                                @endif
                            </tr>            
                            <tr>
                                <th>Status</th>
                                <td>{{ $user->enabled ? 'Enabled' : 'Disabled' }}</td>
                            </tr>
                            <tr>
                                <th>Created By</th>
                                <td>
                                    <badge class="badge badge-info">{{ $user->creator->name }}</badge>
                                    <i class="fa fa-envelope-o">
                                    </i> <a href="mailto:{{ $user->creator->email }}?subject=&body="> {{ $user->creator->email }} </a>
                                    @if($user->creator->phone)
                                        <i class="fa fa-phone"> </i> <a> {{ $user->creator->phone }} </a>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Created On</th>
                                <td>{{ $user->created_at }}</td>
                            </tr>
                            <tr>
                                <th>Updated By</th>
                                @if(isset($user->updater->name))
                                    <td><badge class="badge badge-info">{{ $user->updater->name }}</badge>
                                        <i class="fa fa-envelope-o"> </i> <a href="mailto:{{ $user->updater->email }}?subject=&body="> {{ $user->updater->email}} </a>
                                        @if($user->updater->phone)
                                            <i class="fa fa-phone"> </i> <a> {{ $user->updater->phone }} </a>
                                        @endif
                                    </td>
                                @else
                                <td>-</td>
                                @endif
                            </tr>
                            <tr>
                                <th>Updated On</th>
                                <td>{{ $user->updated_at }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="panel panel-success">
                    <div class="panel-heading">
                        Access Control Settings
                    </div>
                    <div class="panel-body" style="max-height: 750px;overflow: auto;">
                        <table id="acl-assignment" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Action</th>
                                    <th>User Specific Assignment</th>
                                    <th>Role Based Assignment</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    $(document).ready(function() {
        let userId = {{ $user->id }}; // You might want to dynamically set this value
        let roleId = {{ $user->role_id }}; // You might want to dynamically set this value

        $.ajax({
            url: '{{ route("user_role_routes") }}', // Ensure this is the correct route
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}', // CSRF token for security
                user_id: userId,
                role_id: roleId,
            },
            success: function(response) {
                if (response.success) {

                    $('#acl-assignment tbody').empty();
                    $.each(response.data, function(entity, routes) {
                        $('#acl-assignment tbody').append(`<tr class="bg-muted"> <td colspan="3"><strong><p class="m-0">${entity}</p></strong></td> </tr>`);
                        $(routes).each(function() {
                            var assigned_to_role_checkbox = this.assigned_to_role ? '<badge class="badge badge-info">Assigned</badge>' : '<badge class="badge">Not Assigned</badge>';
                            var assigned_to_role_checkbox = (this.assigned_to_user === 'forbidden' && this.assigned_to_role) ? '<badge class="badge badge-warning">Assigned to role but forbidden for this User</badge>' : assigned_to_role_checkbox;

                            var assigned_to_user_checkbox = this.assigned_to_user === 'allowed' ? 'Allowed' : 'Not Assigned';
                            var className = this.assigned_to_user === 'allowed' ? 'badge-primary' : 'badge';

                            var assigned_to_user_checkbox = this.assigned_to_user === 'forbidden' ? 'Forbidden' : assigned_to_user_checkbox;
                            var className = this.assigned_to_user === 'forbidden' ? 'badge-warning' : className;
                            
                            var assigned_to_user_radios = `<badge class="badge ${className}">${assigned_to_user_checkbox}</badge>`;
                            var html = `<tr>
                                <td><p class="m-0">${this.description}</p></td>
                                <td>${assigned_to_user_radios}</td>
                                <td>${assigned_to_role_checkbox}</td>
                            </tr>`;

                            $('#acl-assignment tbody').append(html);
                        });
                    });
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                alert('AJAX Error: ' + error);
            }
        });
    });
</script>
@endsection
