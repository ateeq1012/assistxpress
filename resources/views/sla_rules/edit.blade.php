<?php
    use App\Helpers\GeneralHelper;
?>

@extends('layouts.app')

@section('content')

<link href="{{ asset('css/plugins/select2/select2.min.css') }}" rel="stylesheet">
<script src="{{ asset('js/plugins/select2/select2.full.min.js') }}"></script>

<link href="{{ asset('js/plugins/queryBuilder/dist/css/query-builder.default.css') }}" rel="stylesheet">
<script src="{{ asset('js/plugins/queryBuilder/dist/js/query-builder.standalone.js') }}"></script>

<link href="{{ asset('css/plugins/chosen/chosen.css') }}" rel="stylesheet">
<script src="{{ asset('js/plugins/chosen/chosen.jquery.js') }}"></script>

<style type="text/css">
    .rule-header { display: inline; }
    .error-container { display: inline; }
    .rule-filter-container { display: inline; }
    .rule-operator-container { display: inline; }
    .rule-value-container { display: inline; }
    .rules-group-container { width: 100%; }
    .query-builder .btn-group.pull-right.group-actions {float: right; }
    .rule-value-container { width: 40%; }
    #def_user {color: green; }
    .group-conditions .btn-primary {color: #17a084; background: #faf2dc; border-color: #18a689; }
    .blk_sel {margin-top: 5px; }
    .errorMessage {color: #cc5965; display: inline-block; margin-left: 5px; background-color: #f8d7da; padding: 5px; border-radius: 4px;}
    .invalid-feedback {display:block; }
    textarea {resize: both; width: 100% !important; }
    .rule-value-container .form-control{min-width: 200px; }
    .container {min-width: 1400px; }
    tr label {margin-bottom: 0px; }
    .rule-value-container textarea {border-radius: 5px; }
    .select2-results__option[aria-selected=true] {display: none; }
    #escalation-table td {padding: 3px 8px !important; }
    #reminder-table td {padding: 3px 8px !important; }
    .checkbox-cell {min-width: 55px !important; }
</style>
<div class="wrapper wrapper-content animated fadeInRight">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="ibox-title">
                    <h5>Edit SLA Rule</h5>
                    <div class="ibox-tools">
                        <a href="{{ route('sla_rules.index') }}" class="btn btn-success btn-xs" style="color:white !important;">Manage SLA Rules</a>
                        <button id='submit-button' type="submit" class="btn btn-primary btn-xs">Update SLA Rule</button>
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

                    <div id='ajax-errors'></div>
                    
                    <form id="sla_form" method="POST" action="{{ route('sla_rules.update', $sla_rule->id) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="tabs-container">
                            <ul class="nav nav-tabs" role="tablist">
                                <li><a class="nav-link active" data-toggle="tab" href="#tab-1">SLA General Settings</a></li>
                                <li><a class="nav-link" data-toggle="tab" href="#tab-2">Conditions Applied</a></li>
                                <li><a class="nav-link" data-toggle="tab" href="#tab-3">Reminders</a></li>
                                <li><a class="nav-link" data-toggle="tab" href="#tab-4">Escalations</a></li>
                            </ul>
                            <div class="tab-content">
                                <div role="tabpanel" id="tab-1" class="tab-pane active">
                                    <div class="panel-body">
                                        <div class="form-group">
                                            <label for="name">SLA Rule Name</label>
                                            <input type="text" name="name" class="form-control" value="{{ $sla_rule->name ?? '' }}">
                                        </div>

                                        <div class="form-group">
                                            <label for="description">Description</label>
                                            <textarea name="description" class="form-control" id="description">{{ $sla_rule->description ?? '' }}</textarea>
                                        </div>

                                        <div class="form-group">
                                            <label for="color">Color</label>
                                            <input type="color" class="form-control p-0" name='color' id="color" value="{{ $sla_rule->color ?? '#d1dade' }}"  title="Choose your color">
                                        </div>
                                        <div class="panel panel-primary">
                                            <div class="panel-heading"><strong>Execution SLA: </strong></div>
                                            <div class="panel-body">
                                                <div class="row">
                                                    <div class="col-lg-6 pr-0">
                                                        <div class="form-group <?php echo ($errors->has('response_time')) ? 'has-error' : ''; ?>">
                                                            <label for="response_time">Time to Own (TTO) SLA:<small>(hh:mm)</small></label>
                                                            <input type="text" name="response_time" class="form-control" placeholder="hh:mm" value="{{ $sla_settings['response_time'] ?? '' }}">
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6">
                                                        <div class="form-group <?php echo ($errors->has('resolution_time')) ? 'has-error' : ''; ?>">
                                                            <label for="resolution_time">Time to Resolve (TTR) SLA:<small>(hh:mm)</small></label>
                                                            <input type="text" name="resolution_time" class="form-control" placeholder="hh:mm" value="{{ $sla_settings['resolution_time'] ?? '' }}">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-lg-8 pr-0"> <div class="alert alert-info">Keep service days empty for all days.</div> </div>
                                                    <div class="col-lg-4 pr-0"> <div class="alert alert-info">Keep service windows options empty for 24 hrs.</div> </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-lg-8 pr-0">
                                                        <div class="form-group <?php echo ($errors->has('run_on_days')) ? 'has-error' : ''; ?>">
                                                            @php
                                                                $selectedDays = old('run_on_days', $sla_settings['run_on_days'] ?? []);
                                                            @endphp
                                                            <label for="run_on_days">Execution Service Days:</label>
                                                            <select data-placeholder="Select Service Days for Execution" id="run_on_days" class="form-control select2-field" multiple name="run_on_days[]" tabindex="4">
                                                                <option value="Monday" @selected(in_array('Monday', $selectedDays))>Monday</option>
                                                                <option value="Tuesday" @selected(in_array('Tuesday', $selectedDays))>Tuesday</option>
                                                                <option value="Wednesday" @selected(in_array('Wednesday', $selectedDays))>Wednesday</option>
                                                                <option value="Thursday" @selected(in_array('Thursday', $selectedDays))>Thursday</option>
                                                                <option value="Friday" @selected(in_array('Friday', $selectedDays))>Friday</option>
                                                                <option value="Saturday" @selected(in_array('Saturday', $selectedDays))>Saturday</option>
                                                                <option value="Sunday" @selected(in_array('Sunday', $selectedDays))>Sunday</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-2">
                                                        <div class="form-group <?php echo ($errors->has('start_time')) ? 'has-error' : ''; ?>">
                                                            <label for="start_time">Day Start: <small>(hh:mm)</small></label>
                                                            <input type="text" name="start_time" class="form-control" placeholder="hh:mm" value="{{ $sla_settings['start_time'] ?? '' }}">
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-2">
                                                        <div class="form-group <?php echo ($errors->has('end_time')) ? 'has-error' : ''; ?>">
                                                            <label for="end_time">Day End: <small>(hh:mm)</small></label>
                                                            <input type="text" name="end_time" class="form-control" placeholder="hh:mm" value="{{ $sla_settings['end_time'] ?? '' }}">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-lg-12">
                                                        <div class="form-group <?php echo ($errors->has('sla_statuses')) ? 'has-error' : ''; ?>">
                                                            @php
                                                                $selectedStatuses = old('sla_statuses', $sla_settings['sla_statuses'] ?? []);
                                                            @endphp
                                                            <label for="sla_statuses">SLA Statuses:</label>
                                                            <select data-placeholder="Select SLA Statuses" id="sla_statuses" class="form-control select2-field" multiple name="sla_statuses[]" tabindex="4">
                                                                @foreach ($statuses as $sid => $sval)
                                                                    <option value="{{$sid}}" @selected(in_array($sid, $selectedStatuses))>{{$sval}}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div role="tabpanel" id="tab-2" class="tab-pane">
                                    <div class="panel-body">
                                        <div class="form-group">
                                            <div id="builder-basic"></div>
                                            <input type="hidden" name="qb_rules" id="qb_rules">
                                        </div>
                                    </div>
                                </div>
                                <div role="tabpanel" id="tab-3" class="tab-pane">
                                    <div class="panel-body">
                                        <table id="reminder-table" class="table table-striped table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Reminder</th>
                                                    <th>Notify</th>
                                                    @foreach ($sla_reminders_setup['percentage_arr'] as $percentage => $label)
                                                        <th>{{$label}}</th>
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($sla_reminders_setup['reminder_roles'] as $esc_key => $reminders_setup)
                                                    @php
                                                        $outerLoop = $loop;
                                                    @endphp
                                                    <tr>
                                                        <td rowspan="{{ count($reminders_setup['notify']) }}" style="vertical-align: middle;"><strong>{{ $reminders_setup['label'] }}</strong></td>
                                                        @foreach ($reminders_setup['notify'] as $notif_key => $notif_label)
                                                            @php
                                                                $middleLoop = $loop;
                                                            @endphp
                                                                @if (!$middleLoop->first)
                                                                    <td style="display: none;"></td> <!-- to handle replicate down conflict-->
                                                                @endif
                                                                <td>{{$notif_label}}</td>
                                                                @foreach ($sla_reminders_setup['percentage_arr'] as $percentage => $label)
                                                                    <td class="checkbox-cell">
                                                                        <div class="btn-group">
                                                                            <input
                                                                                type="checkbox"
                                                                                id="{{ $esc_key }}_{{ $notif_key }}_{{ $percentage }}"
                                                                                name="reminder[{{ $esc_key }}][{{ $notif_key }}][{{ $percentage }}]"
                                                                                class="form-control"
                                                                                data-rv="{{ $esc_key }}_{{ $notif_key }}"
                                                                                data-cv="c_{{ $percentage }}"
                                                                                value="{{ $percentage }}"
                                                                                {{ ($sla_settings['reminders'][$esc_key][$notif_key][$percentage] ?? '') == $percentage ? 'checked' : '' }}
                                                                            >
                                                                            @if (!$loop->last)
                                                                                <a href="#" class="ml-2 replicate-btn" title="Replicate Settings Right"><i class="fa fa-angle-double-right"></i></a>
                                                                            @endif
                                                                            @if (!$middleLoop->last || (!$outerLoop->last && $middleLoop->last))
                                                                                <a href="#" class="ml-2 replicate-btn" title="Replicate Settings Down"><i class="fa fa-angle-double-down"></i></a>
                                                                            @endif
                                                                        </div>
                                                                    </td>
                                                                @endforeach
                                                            @if (!$middleLoop->last)
                                                                </tr>
                                                            @endif
                                                        @endforeach
                                                        @if ($outerLoop->last)
                                                            </tr>
                                                        @endif
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div role="tabpanel" id="tab-4" class="tab-pane">
                                    <div class="panel-body">
                                        <div class="panel panel-default">
                                            <div class="panel-heading">Issuer Escalation Users</small></div>
                                            <div class="panel-body p-1">
                                                <div class="col-12">
                                                    <div class="row">
                                                        <div class="col-4 pl-1 pr-1">
                                                            <div class="form-group pb-1">
                                                                <label for="issuer_esc_l1">Issuer Escalation L1</label>
                                                                @if(session('user_routes')['users.search'] ?? false)
                                                                    <select data-placeholder="Search Users" id="issuer_esc_l1" name="issuer_esc_l1[]" class="form-control users-search-field" multiple="multiple">
                                                                        @foreach ($users as $id => $name)
                                                                            @if(in_array($id, $sla_settings['escalation_users']['l1']['issuer_esc_l1'] ?? []))
                                                                                <option value="{{ $id }}"  selected>{{ $name }}</option>
                                                                            @endif
                                                                        @endforeach
                                                                    </select>
                                                                @else
                                                                    <div class="alert alert-info mr-3">You don't have permission to search Users.</div>
                                                                @endif
                                                                <textarea name="issuer_esc_l1_emails"
                                                                    class="form-control mt-1"
                                                                    placeholder="Add emails for people that are not in the system"
                                                                    id="issuer_esc_l1_emails"
                                                                    rows="2">{{$sla_settings['escalation_users']['l1']['issuer_esc_l1_emails'] ?? ''}}</textarea>
                                                            </div>
                                                        </div>
                                                        <div class="col-4 pl-1 pr-1">
                                                            <div class="form-group pb-1">
                                                                <label for="issuer_esc_l2">Issuer Escalation L2</label>
                                                                @if(session('user_routes')['users.search'] ?? false)
                                                                    <select data-placeholder="Search Users" id="issuer_esc_l2" name="issuer_esc_l2[]" class="form-control users-search-field" multiple="multiple">
                                                                        @foreach ($users as $id => $name)
                                                                            @if(in_array($id, $sla_settings['escalation_users']['l2']['issuer_esc_l2'] ?? []))
                                                                                <option value="{{ $id }}"  selected>{{ $name }}</option>
                                                                            @endif
                                                                        @endforeach
                                                                    </select>
                                                                @else
                                                                    <div class="alert alert-info mr-3">You don't have permission to search Users.</div>
                                                                @endif
                                                                <textarea name="issuer_esc_l2_emails"
                                                                    class="form-control mt-1"
                                                                    placeholder="Add emails for people that are not in the system"
                                                                    id="issuer_esc_l2_emails"
                                                                    rows="2">{{$sla_settings['escalation_users']['l2']['issuer_esc_l2_emails'] ?? ''}}</textarea>
                                                            </div>
                                                        </div>
                                                        <div class="col-4 pl-1 pr-1">
                                                            <div class="form-group pb-1">
                                                                <label for="issuer_esc_l3">Issuer Escalation L3</label>
                                                                @if(session('user_routes')['users.search'] ?? false)
                                                                    <select data-placeholder="Search Users" id="issuer_esc_l3" name="issuer_esc_l3[]" class="form-control users-search-field" multiple="multiple">
                                                                        @foreach ($users as $id => $name)
                                                                            @if(in_array($id, $sla_settings['escalation_users']['l3']['issuer_esc_l3'] ?? []))
                                                                                <option value="{{ $id }}"  selected>{{ $name }}</option>
                                                                            @endif
                                                                        @endforeach
                                                                    </select>
                                                                @else
                                                                    <div class="alert alert-info mr-3">You don't have permission to search Users.</div>
                                                                @endif
                                                                <textarea name="issuer_esc_l3_emails"
                                                                    class="form-control mt-1"
                                                                    placeholder="Add emails for people that are not in the system"
                                                                    id="issuer_esc_l3_emails"
                                                                    rows="2">{{$sla_settings['escalation_users']['l3']['issuer_esc_l3_emails'] ?? '' }}</textarea>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="panel panel-default">
                                            <div class="panel-heading">Executor Escalation Users</div>
                                            <div class="panel-body p-1">
                                                <div class="col-12">
                                                    <div class="row">
                                                        <div class="col-4 pl-1 pr-1">
                                                            <div class="form-group pb-1">
                                                                <label for="executor_esc_l1">Executor Escalation L1</label>
                                                                @if(session('user_routes')['users.search'] ?? false)
                                                                    <select data-placeholder="Search Users" id="executor_esc_l1" name="executor_esc_l1[]" class="form-control users-search-field" multiple="multiple">
                                                                        @foreach ($users as $id => $name)
                                                                            @if(in_array($id, $sla_settings['escalation_users']['l1']['executor_esc_l1'] ?? []))
                                                                                <option value="{{ $id }}"  selected>{{ $name }}</option>
                                                                            @endif
                                                                        @endforeach
                                                                    </select>
                                                                @else
                                                                    <div class="alert alert-info mr-3">You don't have permission to search Users.</div>
                                                                @endif
                                                                <textarea name="executor_esc_l1_emails"
                                                                    class="form-control mt-1"
                                                                    placeholder="Add emails for people that are not in the system"
                                                                    id="executor_esc_l1_emails"
                                                                    rows="2">{{$sla_settings['escalation_users']['l1']['executor_esc_l1_emails'] ?? ''}}</textarea>
                                                            </div>
                                                        </div>
                                                        <div class="col-4 pl-1 pr-1">
                                                            <div class="form-group pb-1">
                                                                <label for="executor_esc_l2">Executor Escalation L2</label>
                                                                @if(session('user_routes')['users.search'] ?? false)
                                                                    <select data-placeholder="Search Users" id="executor_esc_l2" name="executor_esc_l2[]" class="form-control users-search-field" multiple="multiple">
                                                                        @foreach ($users as $id => $name)
                                                                            @if(in_array($id, $sla_settings['escalation_users']['l2']['executor_esc_l2'] ?? []))
                                                                                <option value="{{ $id }}"  selected>{{ $name }}</option>
                                                                            @endif
                                                                        @endforeach
                                                                    </select>
                                                                @else
                                                                    <div class="alert alert-info mr-3">You don't have permission to search Users.</div>
                                                                @endif
                                                                <textarea name="executor_esc_l2_emails"
                                                                    class="form-control mt-1"
                                                                    placeholder="Add emails for people that are not in the system"
                                                                    id="executor_esc_l2_emails"
                                                                    rows="2">{{$sla_settings['escalation_users']['l2']['executor_esc_l2_emails'] ?? ''}}</textarea>
                                                            </div>
                                                        </div>
                                                        <div class="col-4 pl-1 pr-1">
                                                            <div class="form-group pb-1">
                                                                <label for="executor_esc_l3">Executor Escalation L3</label>
                                                                @if(session('user_routes')['users.search'] ?? false)
                                                                    <select data-placeholder="Search Users" id="executor_esc_l3" name="executor_esc_l3[]" class="form-control users-search-field" multiple="multiple">
                                                                        @foreach ($users as $id => $name)
                                                                            @if(in_array($id, $sla_settings['escalation_users']['l3']['executor_esc_l3'] ?? []))
                                                                                <option value="{{ $id }}"  selected>{{ $name }}</option>
                                                                            @endif
                                                                        @endforeach
                                                                    </select>
                                                                @else
                                                                    <div class="alert alert-info mr-3">You don't have permission to search Users.</div>
                                                                @endif
                                                                <textarea name="executor_esc_l3_emails"
                                                                    class="form-control mt-1"
                                                                    placeholder="Add emails for people that are not in the system"
                                                                    id="executor_esc_l3_emails"
                                                                    rows="2">{{$sla_settings['escalation_users']['l3']['executor_esc_l3_emails'] ?? ''}}</textarea>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <table id="escalation-table" class="table table-striped table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Escalation</th>
                                                    <th>Notify</th>
                                                    @foreach ($sla_escalations_setup['percentage_arr'] as $percentage => $label)
                                                        <th>{{$label}}</th>
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php

                                                @endphp
                                                @foreach ($sla_escalations_setup['escalation_roles'] as $esc_key => $escalations_setup)
                                                    @php
                                                        $outerLoop = $loop;
                                                    @endphp
                                                    <tr>
                                                        <td rowspan="{{ count($escalations_setup['notify']) }}" style="vertical-align: middle;"><strong>{{ $escalations_setup['label'] }}</strong></td>
                                                        @foreach ($escalations_setup['notify'] as $notif_key => $notif_label)
                                                            @php
                                                                $middleLoop = $loop;
                                                            @endphp
                                                                @if (!$middleLoop->first)
                                                                    <td style="display: none;"></td> <!-- to handle replicate down conflict-->
                                                                @endif
                                                                <td>{{$notif_label}}</td>
                                                                @foreach ($sla_escalations_setup['percentage_arr'] as $percentage => $label)
                                                                    <td class="checkbox-cell">
                                                                        <div class="btn-group">
                                                                            <input
                                                                                type="checkbox"
                                                                                id="{{$esc_key}}_{{$notif_key}}_{{ $percentage }}"
                                                                                name="escalation[{{$esc_key}}][{{$notif_key}}][{{$percentage}}]"
                                                                                class="form-control"
                                                                                data-rv="{{$esc_key}}_{{$notif_key}}"
                                                                                data-cv="c_{{ $percentage }}"
                                                                                value="{{$percentage}}"
                                                                                {{ ($sla_settings['escalations'][$esc_key][$notif_key][$percentage] ?? '') == $percentage ? 'checked' : '' }}
                                                                            >
                                                                            @if (!$loop->last)
                                                                                <a href="#" class="ml-2 replicate-btn" title="Replicate Settings Right"><i class="fa fa-angle-double-right"></i></a>
                                                                            @endif
                                                                            @if (!$middleLoop->last || (!$outerLoop->last && $middleLoop->last))
                                                                                <a href="#" class="ml-2 replicate-btn" title="Replicate Settings Down"><i class="fa fa-angle-double-down"></i></a>
                                                                            @endif
                                                                        </div>
                                                                    </td>
                                                                @endforeach
                                                            @if (!$middleLoop->last)
                                                                </tr>
                                                            @endif
                                                        @endforeach
                                                        @if ($outerLoop->last)
                                                            </tr>
                                                        @endif
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">

    document.getElementById('submit-button').addEventListener('click', function () {
        const form = document.getElementById('sla_form');
        if (form.checkValidity()) {

            var errorr_title = "";
            var errorr_text = "";

            if ($('[name="name"]').val() == '') {
                Swal.fire({
                    title: 'Input Error',
                    text: 'SLA Rule Name not given!',
                    icon: 'warning',
                });
                e.preventDefault(e);
                return false;
            }
            if ($('[name="response_time"]').val() + $('[name="resolution_time"]').val() == '') {
                Swal.fire({
                    title: 'No SLA Timers Given',
                    text: 'Atleast one SLA Timers must be provided!',
                    icon: 'warning',
                });
                e.preventDefault(e);
                return false;
            }

            var query_result = $('#builder-basic').queryBuilder('getRules');

            if (!$.isEmptyObject(query_result)) {
                $("#qb_rules").val(JSON.stringify(query_result));
            } else {
                Swal.fire({
                    title: 'Warning.',
                    text: 'There is an Error in Condition Applied Rules.',
                    icon: 'warning',
                });
                e.preventDefault(e);
                return false;
            }

            const formData = new FormData(form);

            Swal.fire({
                title: "Saving...",
                text: "Please wait",
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: formData,
            })
                .then(async (response) => {
                    Swal.close();

                    if (response.status === 422) {
                        // Validation error handling
                        const errorData = await response.json();
                        const errorMessages = Object.values(errorData.errors)
                            .map(errorArray => errorArray.join(' '))
                            .join('</li>\n<li>');

                        $('#ajax-errors').html(`
                            <div class="alert alert-danger alert-dismissable">
                                <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
                                <ul><li>${errorMessages}</li></ul>
                            </div>
                        `);

                        $('#form-wrapper').animate({ scrollTop: 0 }, 'fast');

                        Swal.fire({
                            title: 'Errors were found, in submitted data.',
                            icon: 'warning',
                        });

                    } else {
                        Swal.fire({
                            title: 'Error Saving Task.',
                            icon: 'warning',
                        });

                    }

                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Success!',
                            text: data.message || 'Task Created Successfully.',
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 1000,
                            timerProgressBar: true
                        }).then(() => {
                            window.location.href = '{{ route('sla_rules.index') }}';
                        });

                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message || 'Something went wrong.',
                            icon: 'error',
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'An error occurred while submitting the form. Please try again.',
                        icon: 'error',
                    });
                });
        } else {
            form.reportValidity();
        }
    });

    document.addEventListener("DOMContentLoaded", function () {
        // Replicate settings down the column
        document.querySelectorAll('a[title="Replicate Settings Down"]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();

                // Get the checkbox in the current cell
                const parentCheckbox = this.parentElement.querySelector('input[type="checkbox"]');
                const isChecked = parentCheckbox.checked; // Checkbox state
                const columnIndex = this.parentElement.parentElement.cellIndex; // Get column index

                // Get all rows in the table body
                const rows = this.closest("table").querySelectorAll("tbody tr");

                // Loop through rows starting from the current row
                let startReplication = false;
                rows.forEach(row => {
                    console.log(row);
                    const cell = row.cells[columnIndex];
                    if (cell === this.parentElement.parentElement) {
                        startReplication = true; // Start replicating from the current cell
                    }
                    if (startReplication) {
                        const checkbox = cell.querySelector('input[type="checkbox"]');
                        if (checkbox && !checkbox.disabled) checkbox.checked = isChecked; // Apply the state
                    }
                });
            });
        });

        // Replicate settings right across the row
        document.querySelectorAll('a[title="Replicate Settings Right"]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();

                // Get the checkbox in the current cell
                const parentCheckbox = this.parentElement.querySelector('input[type="checkbox"]');
                const isChecked = parentCheckbox.checked; // Checkbox state

                // Get all cells in the current row
                const cells = this.parentElement.parentElement.parentElement.children;
                let startReplication = false;

                // Loop through cells in the row
                Array.from(cells).forEach(cell => {
                    if (cell === this.parentElement.parentElement) {
                        startReplication = true; // Start replicating from the current cell
                    }
                    if (startReplication) {
                        const checkbox = cell.querySelector('input[type="checkbox"]');
                        if (checkbox && !checkbox.disabled) checkbox.checked = isChecked; // Apply the state
                    }
                });
            });
        });
    });

    const value_sla_setter = function(rule, value) {
        var input = rule.$el.find('.rule-value-container [name*=_value_]')[0];

        if ( input.nodeName == "SELECT" ) {
            if ( value instanceof Array && value.length > 0 ) {
                $(input).val(value);
                $(input).trigger('chosen:updated');

            } else if ( typeof value != 'undefined' && value != null && value != "" ) {
                $(input).val(value);
                $(input).trigger('chosen:updated');
            }
        } else {
            $(input).val(value);
            $(input).trigger('chosen:updated');
        }
    }

    const value_1setter = function (rule, value) {
        var change_rule = rule.$el.find('.rule-operator-container [name*=_rule_]')[0];
        var input = rule.$el.find('.rule-value-container [name*=_value_]')[0];
        
        if('is_one_of'==$(change_rule).val()) {
            $(input).prop('multiple', true);
        } else {
            $(input).prop('multiple', false);
        }

        $(input).val(null);
        $(input).val(value);

        $(input).trigger('chosen:updated');
    }

    const value_2setter = function (rule, value) {
        var change_rule = rule.$el.find('.rule-operator-container [name*=_rule_]')[0];
        var input = rule.$el.find('.rule-value-container [name*=_value_]')[0];

        $(input).val(null);
        $(input).val(value);    
    }

    const value_validation = function (value, rule) {
        if (value instanceof Array) {
            if( value.length > 0 ) {
                for (var j = 0; j < value.length; j++) {
                    if( typeof value[j] == 'undefined' || value[j] == null || value[j].trim() == "") {
                        return ['{0} cannot be empty.', "Value"];
                    }
                }
            } else {
                return ['{0} cannot be empty.', "Value"];
            }

        } else if ( typeof value == 'undefined' || value == null || value == "" ) {
            return ['{0} cannot be empty.', "Value"];
        }
        return true;
    }

    const validate_string = function (value, rule) {
        // validate slogans here
        if (value instanceof Array) {
            if( value.length > 0 ) {
                for (var j = 0; j < value.length; j++) {
                    if( typeof value[j] == 'undefined' || value[j] == null || value[j].trim() == "") {
                        return ['{0} cannot be empty.', "Value"];
                    }
                }
            } else {
                return ['{0} cannot be empty.', "Value"];
            }
        } else if ( typeof value == 'undefined' || value == null || value == "" ) {
            return ['{0} cannot be empty.', "Value"];
        }
        return true;
    }

    function value_field_maker (e, rule) {
        var ruleValueContainer = rule.$el.find('.rule-value-container');
        var input = rule.$el.find('.rule-value-container [name*=_value_]')[0];
        var input_name = $(input).attr("name");
        var attr_lookup = [];

        if(rule.__.filter.id == "t.project_id") attr_lookup = <?php echo json_encode($projects); ?>;
        else if(rule.__.filter.id == "t.task_type_id") attr_lookup = <?php echo json_encode($task_types); ?>;
        else if(rule.__.filter.id == "t.status_id") attr_lookup = <?php echo json_encode($statuses); ?>;
        else if(rule.__.filter.id == "t.priority_id") attr_lookup = <?php echo json_encode($priorities); ?>;

        if( rule.operator.type == "equal" && rule.__.filter.id != "t.subject" ) {
            var sel = $("<select name='"+input_name+"'>");
            $.each(attr_lookup, function(k, v) {
                sel.append($("<option>").attr('value',k).text(v));
            });

            $.each($._data(input, "events"), function() {
                $.each(this, function() {
                    $(sel).bind(this.type, this.handler);
                });
            });

            $(input).remove();
            $(ruleValueContainer).html('');
            sel.appendTo(ruleValueContainer);

            $(sel).chosen({width: "100%",no_results_text: "No result found.",search_contains: true});
        } else if( rule.operator.type == "is_one_of" && rule.__.filter.id != "t.subject" ) {
            var sel = $("<select name='"+input_name+"' multiple class=\"form-control\">");
            $.each(attr_lookup, function(k, v) {
                sel.append($("<option>").attr('value',k).text(v));
            });

            $.each($._data(input, "events"), function() {
                $.each(this, function() {
                    $(sel).bind(this.type, this.handler);
                });
            });

            $(input).remove();
            $(ruleValueContainer).html('');
            sel.appendTo(ruleValueContainer);

            $(sel).chosen({width: "100%",no_results_text: "No result found.",search_contains: true});
        } else if (rule.__.filter.id == "t.subject") {
            var sel = $("<textarea name='"+input_name+"'class='form-control' rows='1' ></textarea>");
        } else {
            var sel = $("<input type='text' name='"+input_name+"' value='' class='form-control form-control-sm' style='width: 100%;' >");
            $.each($._data(input, "events"), function() {
                $.each(this, function() {
                    $(sel).bind(this.type, this.handler);
                });
            });

            $(input).remove();
            $(ruleValueContainer).html('');
            sel.appendTo(ruleValueContainer);
        }
    }

    $(document).ready(function () {
        $('.select2-field').select2({
            // placeholder: "Select",
            allowClear: true,
            width: "100%",
            language: {
                noResults: function () {
                    return "No options found";
                }
            }
        });

        var rules_basic = <?php echo (( old('qb_rules') !== null ) ? old('qb_rules') : (( $sla_rule->qb_rules !== null ) ? $sla_rule->qb_rules : 'null')); ?>;

        $('#builder-basic').on('afterCreateRuleInput.queryBuilder', function(e, rule)
        {
            value_field_maker(e, rule);
        });

        $("#builder-basic").on('afterUpdateRuleOperator.queryBuilder', function(e, rule)
        {
            value_field_maker(e, rule);
        });

        $('#builder-basic').queryBuilder({
            operators: $.fn.queryBuilder.constructor.DEFAULTS.operators.concat([
                { type: 'equal_to',  nb_inputs: 1, multiple: false, apply_to: ['string'] },
                { type: 'is_one_of',  nb_inputs: 1, multiple: true, apply_to: ['string'] },
                { type: 'is_contains',  nb_inputs: 1, multiple: false, apply_to: ['string'] },
                { type: 'is_not_contains',  nb_inputs: 1, multiple: false, apply_to: ['string'] },
            ]),
            lang: {
                operators:
                {
                    equal_to: 'Equal to',
                    is_one_of: 'Is one of',
                    is_contains: 'Contains',
                    is_not_contains: 'Does not contain',
                }
            },
            filters: [
                {
                    id:'t.subject',
                    label:'Subject',
                    type:'string',
                    input:'textarea',
                    placeholder:'Enter one or more WO Subjects seperated by new line',
                    valueSetter: value_2setter, validation: { callback: validate_string, },
                    operators:['equal', 'is_one_of','is_contains','is_not_contains']
                },
                {
                    id:'t.project_id',
                    label:'Project',
                    values:<?php echo json_encode($projects); ?>,
                    valueSetter: value_sla_setter,
                    operators:['equal','is_one_of'],
                    validation: {
                        callback: value_validation,
                    },
                },
                {
                    id:'t.task_type_id',
                    label:'Task Type',
                    values:<?php echo json_encode($task_types); ?>,
                    valueSetter: value_sla_setter,
                    operators:['equal','is_one_of'],
                    validation: {
                        callback: value_validation,
                    },
                },
                {
                    id:'t.status_id',
                    label:'Status',
                    values:<?php echo json_encode($statuses); ?>,
                    valueSetter: value_sla_setter,
                    operators:['equal','is_one_of'],
                    validation: {
                        callback: value_validation,
                    },
                },
                {
                    id:'t.priority_id',
                    label:'Priority',
                    values:<?php echo json_encode($priorities); ?>,
                    valueSetter: value_sla_setter,
                    operators:['equal','is_one_of'],
                    validation: {
                        callback: value_validation,
                    },
                },
            ],
            rules: rules_basic
        });

        $('.chosen-select').chosen({width: "100%", search_contains: true});

        $('#sla_form').submit(function(e) {
            var errorr_title = "";
            var errorr_text = "";

            if ($('[name="name"]').val() == '') {
                Swal.fire({
                    title: 'Input Error',
                    text: 'SLA Rule Name not given!',
                    icon: 'warning',
                });
                e.preventDefault(e);
                return false;
            }
            if ($('[name="response_time"]').val() + $('[name="resolution_time"]').val() == '') {
                Swal.fire({
                    title: 'No SLA Timers Given',
                    text: 'Atleast one SLA Timers must be provided!',
                    icon: 'warning',
                });
                e.preventDefault(e);
                return false;
            }

            var query_result = $('#builder-basic').queryBuilder('getRules');

            if (!$.isEmptyObject(query_result)) {
                $("#qb_rules").val(JSON.stringify(query_result));
            } else {
                Swal.fire({
                    title: 'Warning.',
                    text: 'There is an Error in Condition Applied Rules.',
                    icon: 'warning',
                });
                e.preventDefault(e);
                return false;
            }

            // console.log(query_result);
            // console.log($('#sla_form').serializeArray());
            // e.preventDefault(e);
            // return false;

            // Remove all QueryBuilder's generated input fields before submit
            // $('div[id^="builder-basic_"]').find('input, select, textarea').each(function() {
            //     $(this).remove();
            // });
        });

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $('.users-search-field').select2({
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
                        project_id: null
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
    });
</script>
@endsection