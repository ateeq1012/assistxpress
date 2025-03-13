@extends('layouts.app')

@section('content')
<style type="text/css">
    th, td {
        padding: 3px 8px !important;
    }
</style>
<div class="ibox pt-2">
    <div class="ibox-title">
        <h5>Edit User</h5>
        <div class="ibox-tools">
            <a href="{{ route('users.index') }}" class="btn btn-primary btn-xs">Manage users</a>
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
        <form action="{{ route('users.update', $user->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="tabs-container mb-2">
                <ul class="nav nav-tabs" role="tablist">
                    <li><a class="nav-link active" data-toggle="tab" href="#tab-1"> User Info</a></li>
                    <li><a class="nav-link" data-toggle="tab" href="#tab-2">Access Control</a></li>
                </ul>
                <div class="tab-content">
                    <div role="tabpanel" id="tab-1" class="tab-pane active">
                        <div class="panel-body" style="max-height: 750px;overflow: auto;">
                            <div class="form-group">
                                <label for="name">Name</label>
                                <input type="text" name="name" class="form-control" value="{{ $user->name }}" required>
                            </div>

                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" name="email" class="form-control" value="{{ $user->email }}" autocomplete="off" required>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="phone" name="phone" class="form-control" value="{{ $user->phone }}" autocomplete="off">
                            </div>

                            <div class="form-group">
                                <label for="role_id">Role</label>
                                <select name="role_id" class="form-control" required>
                                    <option value="">Select Role</option>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->id }}" {{ $user->role_id == $role->id ? 'selected' : '' }}>
                                            {{ $role->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="enabled">Enabled</label>
                                <select name="enabled" class="form-control" id="enabled">
                                    <option value="">Select an option</option>
                                    <option value="1" {{ $user->enabled ? 'selected' : '' }}>Yes</option>
                                    <option value="0" {{ $user->enabled ? '' : 'selected' }}>No</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div role="tabpanel" id="tab-2" class="tab-pane">
                        <div class="panel-body" style="max-height: 750px;overflow: auto;">
                            <div class="alert alert-info alert-dismissable">
                                <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
                                <strong>Notes:</strong>
                                <ul class="mb-0">
                                    <li>Assign additional rights on top of Role Based Acess.</li>
                                    <li>If Access is forbidden, this user will not be able to perform the action even if the action is assigned under the role.</li>
                                </ul>
                            </div>
                            <table id="acl-assignment" class="table table-striped table-bordered mb-0">
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
            <button type="submit" class="btn btn-primary">Update User</button>
        </form>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        let userId = {{ $user->id }};
        let roleId = {{ $user->role_id }};

        $.ajax({
            url: '{{ route("user_role_routes") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                user_id: userId,
                role_id: roleId,
            },
            success: function(response) {
                if (response.success) {
                    $('#acl-assignment tbody').empty();

                    $(response.data).each(function() {
                        var assigned_to_role_checkbox = this.assigned_to_role ? '<badge class="badge badge-info">Assigned</badge>' : '<badge class="badge">Not Assigned</badge>';
                        assigned_to_role_checkbox = (this.assigned_to_user === 'forbidden' && this.assigned_to_role) ? '<badge class="badge badge-warning">Assigned to role but forbidden for this User</badge>' : assigned_to_role_checkbox;

                        var assigned_to_user_allowed = this.assigned_to_user === 'allowed' ? 'checked' : '';
                        var assigned_to_user_forbidden = this.assigned_to_user === 'forbidden' ? 'checked' : '';
                        var assigned_to_user_not_assigned = this.assigned_to_user === 'not-assigned' ? 'checked' : '';

                        var assigned_to_user_radios = `
                            <div class="row p-0 m-0">
                                <div class="radio radio-info badge badge-success pt-1">
                                    <input type="radio" id="${this.key}-allowed" value="allowed" name="acl[${this.key}]" ${assigned_to_user_allowed}>
                                    <label for="${this.key}-allowed" class="pl-1 mb-0"> Allowed </label>
                                </div>
                                <div class="radio badge badge-warning ml-2 pt-1">
                                    <input type="radio" id="${this.key}-forbidden" value="forbidden" name="acl[${this.key}]" ${assigned_to_user_forbidden}>
                                    <label for="${this.key}-forbidden" class="pl-1 mb-0"> Forbidden </label>
                                </div>
                                <div class="radio badge ml-2 pt-1">
                                    <input type="radio" id="${this.key}-not-assigned" value="not-assigned" name="acl[${this.key}]" ${assigned_to_user_not_assigned}>
                                    <label for="${this.key}-not-assigned" class="pl-1 mb-0"> Not Assigned </label>
                                </div>
                                <div class="ml-2 pt-1">
                                    <a href="#" class="replicate-btn" data-row-key="${this.key}" title="Replicate Settings"><i class="fa fa-angle-double-down"></i></a>
                                </div>
                            </div>`;

                        var html = `<tr>
                            <td>${this.description}</td>
                            <td>${assigned_to_user_radios}</td>
                            <td>${assigned_to_role_checkbox}</td>
                        </tr>`;

                        $('#acl-assignment tbody').append(html);
                    });
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                alert('AJAX Error: ' + error);
            }
        });

        $(document).on('click', '.replicate-btn', function(e) {
            e.preventDefault();

            let rowKey = $(this).data('row-key');
            let selectedRadios = $(`input[name="acl[${rowKey}]"]:checked`).map(function() {
                return $(this).val();
            }).get();

            let currentRow = $(this).closest('tr');
            let rowsBelow = currentRow.nextAll('tr');

            rowsBelow.each(function() {
                let currentRowKey = $(this).find('a.replicate-btn').data('row-key');
                $(this).find(`input[name="acl[${currentRowKey}]"][value="allowed"]`).prop('checked', selectedRadios.includes('allowed'));
                $(this).find(`input[name="acl[${currentRowKey}]"][value="forbidden"]`).prop('checked', selectedRadios.includes('forbidden'));
                $(this).find(`input[name="acl[${currentRowKey}]"][value="not-assigned"]`).prop('checked', selectedRadios.includes('not-assigned'));
            });
        });
    });
</script>
@endsection
