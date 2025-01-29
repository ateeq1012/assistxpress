@extends('layouts.app')

@section('content')
<style type="text/css">
    td {
        padding: 3px 8px !important;
    }
</style>
<div class="ibox pt-2">
    <div class="ibox-title">
        <h5>Edit Role</h5>
        <div class="ibox-tools">
            <a href="{{ route('roles.index') }}" class="btn btn-primary btn-xs">Manage Roles</a>
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


        <form action="{{ route('roles.update', $role->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label for="name">Role Name</label>
                        <input type="text" name="name" class="form-control" value="{{ $role->name }}" required>
                    </div>

                    <div class="form-group">
                        <label for="enabled">Enabled</label>
                        <select name="enabled" class="form-control" id="enabled">
                            <option value="">Select an option</option>
                            <option value="1" {{ $role->enabled ? 'selected' : '' }}>Yes</option>
                            <option value="0" {{ $role->enabled ? '' : 'selected' }}>No</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" class="form-control">{{ $role->description }}</textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Update Role</button>

                </div>

                <div class="col-6">
                    <div class="panel panel-success">
                        <div class="panel-heading">
                            Allowed Actions
                            <div class="ibox-tools mr-2" style="top:7px">
                                <button type="button" class="btn btn-xs btn-primary" onclick="selectAll()">Select All</button>
                                <button type="button" class="btn btn-xs btn-warning" onclick="unselectAll()">Unselect All</button>
                            </div>
                        </div>
                        <div class="panel-body" style="max-height: 750px;overflow: auto;">
                            <table class="table table-striped table-bordered">
                                @foreach($route_cfg_resp as $row)
                                    <tr>
                                        <td style="width:50px;">
                                            <div class="row m-0 pl-0 pr-0">
                                                <input type="checkbox" id="{{ $row['key'] }}" name="allowed_actions[]" value="{{ $row['key'] }}" {{ $row['selected'] ? 'checked' : '' }}>
                                                <a href="#" class="ml-2 replicate-btn" data-row-key="{{ $row['key'] }}" title="Replicate Settings"><i class="fa fa-angle-double-down"></i></a>
                                            </div>
                                        </td>
                                        <td>
                                            <label class="mb-0" for="{{ $row['key'] }}"> <small>{{ $row['description'] }}</small> </label>
                                        </td>
                                    </tr>
                                @endforeach
                                
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
    function selectAll() {
        // Get all checkboxes within the panel body
        var checkboxes = document.querySelectorAll('.panel-body input[type="checkbox"]');
        checkboxes.forEach(function(checkbox) {
            checkbox.checked = true; // Check all checkboxes
        });
    }

    function unselectAll() {
        // Get all checkboxes within the panel body
        var checkboxes = document.querySelectorAll('.panel-body input[type="checkbox"]');
        checkboxes.forEach(function(checkbox) {
            checkbox.checked = false; // Uncheck all checkboxes
        });
    }
    document.querySelectorAll('.replicate-btn').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const rowKey = this.dataset.rowKey;
            const startReplicating = false;

            // Find the checkbox that was clicked
            const clickedCheckbox = document.querySelector(`#${rowKey}`);

            if (!clickedCheckbox) return; // If no checkbox is found, stop

            // Get its checked state
            const isChecked = clickedCheckbox.checked;

            // Get all checkboxes
            const allCheckboxes = document.querySelectorAll('.panel-body input[type="checkbox"]');

            // Start replicating from the clicked checkbox onwards
            let replicate = false;
            allCheckboxes.forEach(function(checkbox) {
                if (checkbox.id === rowKey) {
                    replicate = true; // Start replicating after we find the clicked checkbox
                }

                if (replicate) {
                    checkbox.checked = isChecked; // Replicate the checked state to rows below
                }
            });
        });
    });
    window.onload = function() {
        document.querySelectorAll('input[type="checkbox"]').forEach(function(checkbox) {
            // Reset the checkbox based on server-side rendered state
            checkbox.checked = checkbox.defaultChecked;
        });
    };
</script>
@endsection