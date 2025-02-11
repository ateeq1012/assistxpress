<?php
    use App\Helpers\GeneralHelper;
?>

@extends('layouts.app')

@section('content')

<link href="{{ asset('css/plugins/select2/select2.min.css') }}" rel="stylesheet">
<script src="{{ asset('js/plugins/select2/select2.full.min.js') }}"></script>
<link href="{{ asset('css/plugins/dataTables/datatables.min.css') }}" rel="stylesheet">
<script src="{{ asset('js/plugins/dataTables/datatables.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>

<style type="text/css">
    td {
        padding: 3px 8px !important;
        max-width: 150px !important;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .btn-xs {
      padding: 0.1rem 0.2rem;
    }
    .select2-container--default .select2-selection--single {
        border: 1px solid #e5e6e7;
        border-radius: 1px;
        max-width: 200px;
    }
    .form-control, .single-line {
        padding: 2px 8px;
    }
    .form-group {
        margin-bottom: 5px;
    }
    .form-control::placeholder {
        color: #999;
        font-size: 13px;
    }
    .tbl-label {
        padding: 1px 4px;
        font-size: 13px !important;
        font-weight: normal;
    }
    #tsk_tbl_wrapper {
        width: 100% !important;
        overflow: auto;
    }
    .align-right {
        text-align: right;
    }
    select.form-control:not([size]):not([multiple]) {
        height: 28px;
    }
    table.dataTable {
        border-collapse: collapse;
        border-spacing: 0;
    }
    .action-btn .btn {
      padding: 0px 5px;
      margin: -3px 0px !important;
    }
    td:has(.action-btn) {
      padding-top: 2px !important;
    }

</style>
<div class="ibox pt-2" id="main_ibox">
    <div class="ibox-title">
        <h5>Tasks</h5>
        <div class="ibox-tools">
            <a href="{{ route('tasks.create') }}" class="btn btn-primary btn-xs">Create Task</a>
        </div>
    </div>
    <div class="ibox-content">
        <div class="sk-spinner sk-spinner-wave">
            <div class="sk-rect1"></div>
            <div class="sk-rect2"></div>
            <div class="sk-rect3"></div>
            <div class="sk-rect4"></div>
            <div class="sk-rect5"></div>
        </div>
        @if (session('error'))
            <div class="alert alert-danger alert-dismissable">
                <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
                {{ session('error') }}
            </div>
        @endif
        
        @if (session('success'))
            <div class="alert alert-success alert-dismissable">
                <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
                {{ session('success') }}
            </div>
        @endif
        <div class="row mb-3">
            <div class="col-lg-12">
                <div class="tabs-container">
                    <ul class="nav nav-tabs" role="tablist">
                        <li><a class="nav-link active" data-toggle="tab" href="#tab-filters">Filters</a></li>
                        <!-- <li><a class="nav-link" data-toggle="tab" href="#tab-adv-filters">Advanced Filters</a></li>
                        <li><a class="nav-link" data-toggle="tab" href="#tab-tbl-settings">Table Settings</a></li> -->
                    </ul>
                    <div class="tab-content">
                        <div role="tabpanel" id="tab-filters" class="tab-pane active">
                            <div class="panel-body pb-1">
                                <div class="row">
                                    <div class="form-group pl-1 pr-1" style="width: 100px;">
                                        <input type="number" placeholder="Task ID" id="id" class="form-control srch_col">
                                    </div>
                                    <div class="form-group pl-1 pr-1" style="min-width: 200px;">
                                        <input type="text" placeholder="Subject" id="subject" class="form-control srch_col">
                                    </div>
                                    <div class="form-group pl-1 pr-1" style="min-width: 200px;">
                                        <input type="text" placeholder="Description" id="description" class="form-control srch_col">
                                    </div>
                                    <div class="form-group pl-1 pr-1" style="min-width: 200px;">
                                        <select id="project_id" class="form-control srch_col select2-field" data-placeholder="Project">
                                            <option value="">Project</option>
                                            @foreach ($projects as $project)
                                                <option value="{{$project['id']}}">{{$project['name']}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group pl-1 pr-1" style="min-width: 200px;">
                                        <select id="task_type_id" class="form-control srch_col select2-field" data-placeholder="Task Type">
                                            <option value="">Task Type</option>
                                            @foreach ($task_types as $task_type)
                                                <option value="{{$task_type['id']}}">{{$task_type['name']}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group pl-1 pr-1" style="min-width: 200px;">
                                        <select id="status_id" class="form-control srch_col select2-field" data-placeholder="Status">
                                            <option value="">Status</option>
                                            @foreach ($statuses as $status)
                                                <option value="{{$status['id']}}">{{$status['name']}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group pl-1 pr-1" style="min-width: 200px;">
                                        <select id="priority_id" class="form-control srch_col select2-field" data-placeholder="Priority">
                                            <option value="">Priority</option>
                                            @foreach ($priorities as $priority)
                                                <option value="{{$priority['id']}}" >{{$priority['name']}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group pl-1 pr-1" style="min-width: 200px;">
                                        <select id="creator_id" class="form-control srch_col select2-field" data-placeholder="Creator">
                                            <option value="">Creator</option>
                                            @foreach ($users as $user)
                                                <option value="{{$user['id']}}" >{{$user['name']}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group pl-1 pr-1" style="min-width: 200px;">
                                        <select id="creator_group_id" class="form-control srch_col select2-field" data-placeholder="Creator Group">
                                            <option value="">Creator Group</option>
                                            @foreach ($groups as $group)
                                                <option value="{{$group['id']}}" >{{$group['name']}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group pl-1 pr-1" style="min-width: 200px;">
                                        <select id="updated_by" class="form-control srch_col select2-field" data-placeholder="Updater">
                                            <option value="">Updater</option>
                                            @foreach ($users as $user)
                                                <option value="{{$user['id']}}" >{{$user['name']}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group pl-1 pr-1" style="min-width: 200px;">
                                        <select id="executor_group_id" class="form-control srch_col select2-field" data-placeholder="Assignee Group">
                                            <option value="">Assignee Group</option>
                                            @foreach ($groups as $group)
                                                <option value="{{$group['id']}}" >{{$group['name']}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group pl-1 pr-1" style="min-width: 200px;">
                                        <select id="executor_id" class="form-control srch_col select2-field" data-placeholder="Assignee">
                                            <option value="">Assignee</option>
                                            @foreach ($users as $user)
                                                <option value="{{$user['id']}}" >{{$user['name']}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @foreach ($custom_fields as $custom_field)
                                        @if(!in_array($custom_field['field_type'], ['Date', 'Time', 'Datetime Picker', 'File Upload']) && $custom_field['use_as_filter'] === true)
                                            @php
                                                $settings = json_decode($custom_field['settings'], true);
                                            @endphp
                                            @if (isset($settings['options']))
                                                @php
                                                    $options = $settings['options'];
                                                    $make_select_2 = (count($settings['options']) > 10) ? 'select2-field' : 'simple-select';
                                                    $make_select_2 = 'select2-field'; // make all dropdowns select2
                                                    asort($options);
                                                @endphp
                                                <div class="form-group pl-1 pr-1" style="min-width: 200px;">
                                                    <select id="{{$custom_field['field_id']}}" class="form-control srch_col {{$make_select_2}}" data-placeholder="{{$custom_field['name']}}" style="max-width: 200px;">
                                                        <!-- <option value="" disabled selected>Select an option</option> -->
                                                        <option value="">{{$custom_field['name']}}</option>
                                                        @foreach ($options as $option)
                                                            <option value="{{$option}}" >{{$option}}</option>
                                                        @endforeach
                                                        <!-- <option value="_null">{NOT SET}</option> -->
                                                    </select>
                                                </div>
                                            @else
                                                <div class="form-group pl-1 pr-1" style="min-width: 200px;">
                                                    @php
                                                        $srch_col_type = ($custom_field['field_type'] == 'Number') ? 'number' : 'text';
                                                    @endphp
                                                    <input type="{{$srch_col_type}}" placeholder="{{$custom_field['name']}}" id="{{$custom_field['field_id']}}" class="form-control srch_col">
                                                </div>
                                            @endif
                                        @endif
                                    @endforeach
                                </div>
                                <hr class="mt-1 mb-1" style="margin: 5px -11px 4px -11px !important;">
                                <div class="row pl-1">
                                    <div class="form-group mb-0">
                                        <input type="button" value="Apply Filters" class="btn btn-primary btn-sm" onclick="apply_filters();">
                                        <input type="button" value="Reset Table" class="btn btn-danger btn-sm" onclick="reset_page();">
                                        <input type="button" value="Clear Filters" class="btn btn-warning btn-sm" onclick="clear_filters();">
                                        @if(session('user_routes')['tasks.download'] ?? false)
                                            <form action="{{ route('tasks.download') }}" method="POST" style="display: inline-block;" target="_blank" id="download-form">
                                                @csrf
                                                <input type="hidden" name="dn_filters" id="dn_filters" value="">
                                                <button id="download-btn" type="submit" class="btn btn-info btn-sm">Download</button>
                                            </form>
                                        @endif

                                    </div>
                                </div>
                            </div>
                        </div>
                        <div role="tabpanel" id="tab-adv-filters" class="tab-pane">
                            <div class="panel-body">
                                TBD
                            </div>
                        </div>
                        <div role="tabpanel" id="tab-tbl-settings" class="tab-pane">
                            <div class="panel-body">
                                TBD
                                <!-- <div class="row mb-0">
                                    <div class="col-lg-4">
                                        <div class="ibox mb-0">
                                            <div class="ibox-content pb-0">
                                                <h3>Selected Fields</h3>
                                                <div class="ibox-tools mr-2">
                                                    <button class="btn btn-xs btn-primary" onclick="save_fields()">Save Fields</button>
                                                </div>
                                                <ul class="sortable-list connectList agile-list" id="selected_fields" style="max-height: 500px;overflow: auto;">
                                                    @foreach($selected_fields as $field_key => $field_name)
                                                        <li class="info-element mb-1 p-1 pl-2 pr-2 draggable-item" id="{{$field_key}}" style="border-radius: 4px;" disabled>
                                                            <label class="mb-0"> {{ $field_name }}</label>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="ibox">
                                            <div class="ibox-content">
                                                <h3>Fields Not Selected</h3>
                                                <ul class="sortable-list connectList agile-list" id="all_fields" style="max-height: 500px;overflow: auto;">
                                                    @foreach($not_selected_fields as $field_key => $field_name)
                                                        <li class="success-element mb-1 p-1 pl-2 pr-2 draggable-item" id="{{ $field_key }}" style="border-radius: 4px;">
                                                            <label class="mb-0"> {{ $field_name }} </label>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div> -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 p-0 m-0">
            <table id="tsk_tbl" class="table table-hover table-striped table-bordered display nowrap" style="width:100%">
                <thead>
                    <tr>
                        @foreach ($selected_fields as $key => $field_name)
                            <th>{{ $field_name }}</th>
                        @endforeach
                        <th class="actions-col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                    <tr>
                        @foreach ($selected_fields as $key => $field_name)
                            <th>{{ $field_name }}</th>
                        @endforeach
                        <th>Actions</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<script>

    document.getElementById('download-btn').addEventListener('click', function(event) {
        event.preventDefault();

        var col_search_opts = {};

        $(".srch_col").each(function() {
            if ($(this).val().trim() != '') {
                col_search_opts[$(this).attr("id")] = $(this).val().trim();
            }
        });

        $('#dn_filters').val(JSON.stringify(col_search_opts));  // Convert to JSON string

        document.getElementById('download-form').submit();
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

    // Handle permission-denied button
    $(document).on('click', '.permission-denied', function () {
        Swal.fire({
            title: "Permission Denied",
            text: "You are not allowed to perform this action",
            icon: "warning"
        });
    });

    
    var tsk_dt = null;
    var tsk_curr_srch_val = "";
    var tsk_latest_dt_param = {}; // Initialize as an empty object
    $(document).ready(function () {
        $('.select2-field').each(function () {
            var placeholder = $(this).attr('data-placeholder') || $(this).attr('placeholder');
            $(this).select2({
                placeholder: placeholder,
                allowClear: true,
                width: "100%",
            });
        });

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

        tsk_dt = $('#tsk_tbl').DataTable({
            "processing": true,
            "serverSide": true,
            "pageLength": 10,
            "scrollX": true,
            "scrollY": "500px",
            "scrollCollapse": true,
            "ordering": true,
            "autoWidth": false,
            "fixedColumns": true,
            "order": [[0, 'desc']],
            "buttons": [
                // Add export or other buttons here
            ],
            "ajax": function (data, callback, settings) {
                var val_changed = false;
                var search_params = tsk_latest_dt_param; // get params from var
            

                if (tsk_curr_srch_val != data.search.value) {
                    tsk_curr_srch_val = data.search.value;
                    val_changed = true;
                }
                 
                if (val_changed) {
                    tsk_dt.page('first').draw('page');
                } else {
                   
                    $('#main_ibox').children('.ibox-content').addClass('sk-loading');

                    $.ajax({
                        url: "{{ route('tasks.get_task_data') }}",
                        type: 'POST',
                        data: {
                            draw: data.draw,
                            start: data.start,
                            length: data.length,
                            search: data.search,
                            order: data.order,
                            ...search_params, // include filter params
                            _token: "{{ csrf_token() }}"
                        },
                        success: function (resp_data) {
                            $('#main_ibox').children('.ibox-content').removeClass('sk-loading');

                            if (!resp_data.success) {
                                Swal.fire({
                                    title: "Error Getting Data",
                                    text: resp_data.error_message,
                                    icon: "info"
                                });
                                var draw_obj = {
                                    draw: resp_data.draw,
                                    recordsFiltered: 0,
                                    recordsTotal: 0,
                                    data: []
                                };
                                callback(draw_obj);
                            } else {
                                callback(resp_data);
                                $('#tsk_tbl tbody .color-parent-td').each(function () {
                                    var bgColor = $(this).css('background-color');
                                    $(this).closest('td').css('background-color', bgColor);
                                });

                                $('#tsk_tbl tbody td').each(function () {
                                    var $this = $(this);

                                    // Check if the text is overflowing
                                    if (this.scrollWidth > this.offsetWidth) {
                                        // Add a popover with the full content
                                        $this.attr('data-bs-toggle', 'popover')
                                             .attr('data-bs-trigger', 'hover')
                                             .attr('data-bs-content', $this.text())
                                             .attr('data-bs-placement', 'top');
                                    }
                                });

                                // Enable Bootstrap popovers
                                const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
                                const popoverList = [...popoverTriggerList].map(el => new bootstrap.Popover(el));
                            }
                        },
                        error: function () {
                            $('#main_ibox').children('.ibox-content').removeClass('sk-loading');
                            Swal.fire({
                                title: "Error",
                                text: "We encountered an error while fetching data",
                                icon: "error"
                            });
                        }
                    });
                }
            },
            "language": {
                "processing": "",
                "emptyTable": "No records to display",
                "zeroRecords": "No records to display",
            },
            "initComplete": function (settings, json) {
                $('#tsk_tbl_filter input').unbind();
                $('#tsk_tbl_filter input').bind('keyup', function (e) {
                    if (e.keyCode == 13) {
                        tsk_dt.search(this.value).draw();
                    }
                });
                $('#tsk_tbl_filter input').blur(function () {
                    $('#tsk_tbl_filter input').val(tsk_curr_srch_val);
                });
            },
            "columns": [
                @foreach ($selected_fields as $key => $value)
                    { "class": "dt-column", "data": "{{ $key }}", "defaultContent": "" },
                @endforeach
                { "class": "dt-column", "data": null, "defaultContent": "" },
            ],
            "columnDefs": [
                {
                    "targets": -1,
                    "orderable": false
                },
                {
                    "targets": -1,
                    "data": null,
                    "render": function (data, type, row, meta) {
                        return `
                            @if(session('user_routes')['tasks.show'] ?? false)
                                <a href="/tasks/${row.id}" class="btn btn-info btn-xs">View</a>
                            @else
                                <a class="btn btn-default btn-xs permission-denied">View</a>
                            @endif
                            @if(session('user_routes')['tasks.edit'] ?? false)
                                <a href="/tasks/${row.id}/edit" class="btn btn-primary btn-xs">Edit</a>
                            @else
                                <a class="btn btn-default btn-xs permission-denied">Edit</a>
                            @endif
                            @if(session('user_routes')['tasks.destroy'] ?? false)
                                <form action="/tasks/${row.id}" method="POST" style="display: inline-block;">
                                    <input type="hidden" name="_token" value="${$('meta[name="csrf-token"]').attr('content')}">
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="button" class="btn btn-danger btn-xs delete-button" data-id="${row.id}">Delete</button>
                                </form>
                            @else
                                <a class="btn btn-default btn-xs permission-denied" style="background-color: #edeff1; border-color:#ed5565;">Delete</a>
                            @endif
                        `;
                    }
                }
            ],
            "initComplete": function () {
                // Remove DataTables default styling classes and add your own
                $('#tsk_tbl')
                    .addClass('table table-hover table-striped table-bordered display nowrap'); // Adds your classes
            }
        });

    });
    
     function apply_filters() {
        var col_search_opts = {};

        // $(".srch_col").each(function() {
        //     col_search_opts[$(this).attr("id")] = $(this).val();
        // });
        $(".srch_col").each(function() {
            if( $(this).val().trim() != '' ) {
                col_search_opts[$(this).attr("id")] = $(this).val().trim();
            }
        });

        tsk_latest_dt_param = { ...tsk_latest_dt_param, filters: col_search_opts };
    
        tsk_dt.column().search('');
        tsk_dt.ajax.reload(function(json) {
            // console.log(json);
        }, true);
    }

    function clear_filters() {
        $(".srch_col").val("");
        $(".srch_col.select2").val(null).trigger("change");
        $(".srch_col").trigger("change");
    }
    function reset_page() {
        $(".srch_col").val("");
        $(".srch_col.select2").val(null).trigger("change");
        $(".srch_col").trigger("change");
        tsk_latest_dt_param = {}; // Clear filter params
        tsk_dt.search( tsk_curr_srch_val ).page( 'first' ).draw( 'page' );
    }

</script>

@endsection