<?php
    use App\Helpers\GeneralHelper;
?>

@extends('layouts.app')

@section('content')

<style type="text/css">
    td {
        padding: 3px 8px !important;
    }
    #color {
        border-radius: 3px;
    }
</style>
<div class="ibox pt-2">
    <div class="ibox-title">
        <h5>Edit Task Type</h5>
        <div class="ibox-tools">
            <a href="{{ route('task_types.index') }}" class="btn btn-primary btn-xs">Manage Task Types</a>
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
        <div class="tabs-container">
            <ul class="nav nav-tabs" role="tablist">
                <li><a class="nav-link active" data-toggle="tab" href="#basic-info">Basic Info</a></li>
                <li><a class="nav-link" data-toggle="tab" href="#fields">Fields</a></li>
                <!-- <li><a class="nav-link" data-toggle="tab" href="#statuses">Statuses</a></li> -->
                <li><a class="nav-link" data-toggle="tab" href="#interactions">Interactions</a></li>
            </ul>
            <div class="tab-content">
                <div role="tabpanel" id="basic-info" class="tab-pane active">
                    <div class="panel-body">
                        <form action="{{ route('task_types.update', $task_type->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="form-group">
                                <label for="name">Name</label>
                                <input type="text" name="name" class="form-control" value="{{ $task_type->name }}" required>
                            </div>

                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea name="description" class="form-control" id="description">{{ $task_type->description }}</textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="workflow_id">Workflow</label>
                                <select name="workflow_id" class="form-control" id="workflow_id">
                                    <option value="">Select Workflow</option>
                                    @foreach($workflows as $workflow)
                                        <option value="{{ $workflow->id }}" {{ $task_type->workflow_id == $workflow->id ? 'selected' : '' }}>{{$workflow->name}}</option>

                                    @endforeach
                                </select>
                            </div>

                            <!-- <div class="form-group">
                                <label for="is_planned">Planned Task</label>
                                <select name="is_planned" class="form-control" id="is_planned">
                                    <option value="">Select an Option</option>
                                    <option value="1" {{ $task_type->is_planned == 1 ? 'selected' : '' }} >Yes</option>
                                    <option value="0" {{ $task_type->is_planned == 0 ? 'selected' : '' }} >No</option>
                                </select>
                            </div> -->

                            <div class="form-group">
                                <label for="color">Color :</label>
                                <input type="color" class="form-control p-0" name='color' id="color" value="{{ $task_type->color }}"  title="Choose your color">
                            </div>

                            <div class="form-group">
                                <label for="enabled">Enabled</label>
                                <select name="enabled" class="form-control" id="enabled">
                                    <option value="">Select an option</option>
                                    <option value="1" {{ $task_type->enabled ? 'selected' : '' }}>Yes</option>
                                    <option value="0" {{ $task_type->enabled ? '' : 'selected' }}>No</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Update Status</button>
                        </form>
                    </div>
                </div>
                <div role="tabpanel" id="fields" class="tab-pane">
                    <div class="panel-body" style="background-color:#f3f3f4;">
                        @if((isset($custom_fields) && count($custom_fields) > 0) || (isset($selected_custom_fields) && count($selected_custom_fields) > 0))
                            <div class="row">
                                <div class="col-lg-4">
                                    <div class="ibox">
                                        <div class="ibox-content">
                                            <h3>Selected Fields</h3>
                                            <div class="ibox-tools mr-2">
                                                <button class="btn btn-xs btn-primary" onclick="save_fields()">Save Fields</button>
                                            </div>
                                            <ul class="sortable-list connectList agile-list" id="selected_fields">
                                                @foreach($system_fields as $sk => $sys_field_desc)
                                                    <li class="info-element mb-1 p-1 pl-2 pr-2 sys_field" id="{{$sk}}" style="border-radius: 4px;" disabled>
                                                        <label class="mb-0"> {{ $sys_field_desc }}</label>
                                                        <a href="#" class="float-right badge badge-success">System Field</a>
                                                    </li>
                                                @endforeach

                                                @foreach($selected_custom_fields as $sel_custom_field)
                                                    <li class="success-element mb-1 p-1 pl-2 pr-2 draggable-item" id="{{ $sel_custom_field['id'] }}" style="border-radius: 4px;">
                                                        <label class="mb-0"> {{ $sel_custom_field['name'] }} <small>({{$sel_custom_field['field_type']}})</small></label>
                                                        @if(isset($sel_custom_field['description']))
                                                            <p class="small m-0">{{$sel_custom_field['description']}}</p>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="ibox">
                                        <div class="ibox-content">
                                            <h3>All Fields</h3>
                                            <ul class="sortable-list connectList agile-list" id="all_fields">
                                                @foreach($custom_fields as $custom_field)
                                                    <li class="success-element mb-1 p-1 pl-2 pr-2 draggable-item" id="{{ $custom_field['id'] }}" style="border-radius: 4px;">
                                                        <label class="mb-0"> {{ $custom_field['name'] }} <small>({{$custom_field['field_type']}})</small></label>
                                                        @if(isset($custom_field['description']))
                                                            <p class="small m-0">{{$custom_field['description']}}</p>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <p>No Custom Fields Defined. Please define <a href="{{ route('custom_fields.create') }}">custom fields</a> first.</p>
                        @endif
                    </div>
                </div>
                <div role="tabpanel" id="interactions" class="tab-pane">
                    <div class="panel-body">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    var selected_fields = [];
    $(document).ready(function() {
        $("#selected_fields, #all_fields").sortable({
            connectWith: ".connectList",
            items: "li:not(.sys_field)",
            update: function( event, ui ) {
                selected_fields = $( "#selected_fields" ).sortable( "toArray" );
            }
        }).disableSelection();

        $(document).on('click', '.draggable-item', function () {
            const parentList = $(this).parent().attr('id');

            if (parentList === 'selected_fields') {
                // Move to 'All Fields'
                $('#all_fields').append($(this));
            } else if (parentList === 'all_fields') {
                // Move to 'Selected Fields'
                $('#selected_fields').append($(this));
            }
            selected_fields = $( "#selected_fields" ).sortable( "toArray" );
        });
    });

    function save_fields() {

        if (!selected_fields || selected_fields.length === null) {
            Swal.fire({
                title: "Unable to detect changes",
                text: "Please select at least one field",
                icon: "info"
            });
            return;
        }

        // Show confirmation dialog
        Swal.fire({
            title: "Confirm Changes",
            html: "You are about to make changes to custom fields. If you remove any fields, existing data will be removed against those fields.<br><br>Do you want to proceed?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, proceed",
            cancelButtonText: "No, cancel"
        }).then((result) => {
            if (result.isConfirmed) {
                // Show the "Saving..." loading message
                Swal.fire({
                    title: "Saving...",
                    text: "Please wait while we save your changes",
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading(); // Show the loading spinner
                    }
                });

                // AJAX call to save fields
                $.ajax({
                    url: '{{ route("task_types.save_task_type_custom_fields") }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        id: @json($task_type->id),
                        selected_fields: JSON.stringify(selected_fields)
                    },
                    success: function(response) {
                        Swal.fire({
                            title: "Custom fields saved successfully",
                            icon: "success"
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        let errorMessage = "An error occurred";

                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }

                        Swal.fire({
                            title: "Error saving custom fields",
                            text: errorMessage,
                            icon: "error"
                        });
                    }
                });
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                // If user cancels, show info dialog
                Swal.fire({
                    title: "Cancelled",
                    text: "No changes were made to custom fields.",
                    icon: "info"
                });
            }
        });
    }

</script>
@endsection