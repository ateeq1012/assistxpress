<?php
    use App\Helpers\GeneralHelper;
?>

@extends('layouts.app')

@section('content')

<link href="{{ asset('css/plugins/select2/select2.min.css') }}" rel="stylesheet">
<script src="{{ asset('js/plugins/select2/select2.full.min.js') }}"></script>

<style type="text/css">
    td {
        padding: 3px 8px !important;
    }
    #color {
        border-radius: 3px;
    }
    .checkbox-cell {
        min-width: 55px !important;
    }
    .select2-results__option[aria-selected=true] {
        display: none;
    }
</style>
<div class="ibox pt-2">
    <div class="ibox-title">
        <h5>Edit Workflow</h5>
        <div class="ibox-tools">
            <a href="{{ route('workflows.index') }}" class="btn btn-primary btn-xs">Manage Workflows</a>
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
                <li><a class="nav-link" data-toggle="tab" href="#fields">Status Transitions</a></li>
                <li><a class="nav-link" data-toggle="tab" href="#interactions">Field Settings</a></li>
            </ul>
            <div class="tab-content">
                <div role="tabpanel" id="basic-info" class="tab-pane active">
                    <div class="panel-body">
                        <form action="{{ route('workflows.update', $workflow->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="form-group">
                                <label for="name">Name</label>
                                <input type="text" name="name" class="form-control" value="{{ $workflow->name }}" required>
                            </div>

                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea name="description" class="form-control" id="description">{{ $workflow->description }}</textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Update Status</button>
                        </form>
                    </div>
                </div>
                <div role="tabpanel" id="fields" class="tab-pane">
                    <div class="panel-body" style="background-color:#f3f3f4;">
                        <div class="col-12 p-0 m-0">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    Allowed Statuses for new service requests
                                </div>
                                <div class="panel-body p-1">
                                    <table id="new_service_request_transitions" class="table table-striped table-bordered mb-0">
                                        <thead>
                                            <tr>
                                                <th class="checkbox-cell"></th>
                                                @foreach($statuses as $st_col)
                                                    <th class="checkbox-cell" style="padding:2px; font-size:unset; background-color:{{ $st_col->color }}; color:{{ GeneralHelper::invert_color($st_col->color) }}">
                                                        {{ $st_col->name }}
                                                    </th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr><td><i>New Service Request</i></td>
                                                @foreach($statuses as $st_col)
                                                    <td class="checkbox-cell">
                                                        <div class="row m-0 pl-0 pr-0">
                                                            <input type="checkbox" id="transition_0_{{ $st_col->id }}" name="wf_statuses[][]" data-transition-type="new" data-rv="0" data-cv = "{{ $st_col->id }}"
                                                                {{ isset($from_to['new'][0]) && isset($from_to['new'][0][$st_col->id] ) ? 'checked' : '' }}
                                                            >
                                                            <a href="#" class="ml-2 replicate-btn" title="Replicate Settings Right"><i class="fa fa-angle-double-right"></i></a>
                                                            <a href="#" class="ml-2 replicate-btn" title="Replicate Settings Down"><i class="fa fa-angle-double-down"></i></a>
                                                        </div>
                                                    </td>
                                                @endforeach
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 p-0 m-0">
                            <div class="panel panel-primary">
                                <div class="panel-heading">
                                    Transitions for Creator
                                </div>
                                <div class="panel-body p-1">
                                    <table id="creator_transitions" class="table table-striped table-bordered mb-0">
                                        <thead>
                                            <tr><th>From Status</th><th colspan="{{ count($statuses) }}" style="text-align:center;">To Statuses</th></tr>
                                            <tr>
                                                <th class="checkbox-cell"></th>
                                                @foreach($statuses as $st_col)
                                                    <th class="checkbox-cell" style="padding:2px; font-size:unset; background-color:{{ $st_col->color }}; color:{{ GeneralHelper::invert_color($st_col->color) }}">
                                                        {{ $st_col->name }}
                                                    </th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($statuses as $st_row)
                                                <tr>
                                                    <td class="checkbox-cell" style="font-size:unset; background-color:{{ $st_row->color }}; color:{{ GeneralHelper::invert_color($st_row->color) }}">
                                                        {{ $st_row->name }}
                                                        <!-- <badge class="label" >{{ $st_row->name }}</badge> -->
                                                    </td>
                                                    @foreach($statuses as $st_col)
                                                        @if($st_row->id == $st_col->id)
                                                            <td class="checkbox-cell" style="font-size:unset; background-color:{{ $st_row->color }}; color:{{ GeneralHelper::invert_color($st_row->color) }}; opacity: 0.8;">
                                                                <input type="checkbox" id="transition_{{ $st_row->id }}_{{ $st_col->id }}" name="wf_statuses[][]" data-transition-type="creator" data-rv="{{ $st_row->id }}" data-cv = "{{ $st_col->id }}" value="{{ $st_row->id }}_{{ $st_col->id }}" checked disabled title="Transition to same status"> <small>Always</small>
                                                            </td>
                                                        @else
                                                            <td class="checkbox-cell">
                                                                <div class="row m-0 pl-0 pr-0">
                                                                    <input type="checkbox" id="transition_{{ $st_row->id }}_{{ $st_col->id }}" name="wf_statuses[][]" data-transition-type="creator" data-rv="{{ $st_row->id }}" data-cv = "{{ $st_col->id }}" value="{{ $st_row->id }}_{{ $st_col->id }}"
                                                                        {{ isset($from_to['creator'][$st_row->id]) && isset($from_to['creator'][$st_row->id][$st_col->id] ) ? 'checked' : '' }}
                                                                    >
                                                                    <a href="#" class="ml-2 replicate-btn" title="Replicate Settings Right"><i class="fa fa-angle-double-right"></i></a>
                                                                    <a href="#" class="ml-2 replicate-btn" title="Replicate Settings Down"><i class="fa fa-angle-double-down"></i></a>
                                                                </div>
                                                            </td>
                                                        @endif
                                                        <!-- <td>{{ $st_col->name }}</td> -->
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 p-0 m-0">
                            <div class="panel panel-primary">
                                <div class="panel-heading">
                                    Transitions for Other Members from Creator's Group
                                </div>
                                <div class="panel-body p-1">
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-group">
                                                <select id="creator-member-roles" name="creator_member_roles[]" class="form-control select2-multi" multiple="multiple" data-placeholder="Select Roles" >
                                                    @foreach ($roles as $role)
                                                        <option value="{{ $role->id }}" {{ isset($creator_member_roles[$role->id]) ? 'selected=""' : '' }} > {{ $role->name }} </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <table id="creators_group_members_transitions" class="table table-striped table-bordered mb-0">
                                        <thead>
                                            <tr><th>From Status</th><th colspan="{{ count($statuses) }}" style="text-align:center;">To Statuses</th></tr>
                                            <tr>
                                                <th class="checkbox-cell"></th>
                                                @foreach($statuses as $st_col)
                                                    <th class="checkbox-cell" style="padding:2px; font-size:unset; background-color:{{ $st_col->color }}; color:{{ GeneralHelper::invert_color($st_col->color) }}">
                                                        {{ $st_col->name }}
                                                    </th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($statuses as $st_row)
                                                <tr>
                                                    <td class="checkbox-cell" style="font-size:unset; background-color:{{ $st_row->color }}; color:{{ GeneralHelper::invert_color($st_row->color) }}">
                                                        {{ $st_row->name }}
                                                        <!-- <badge class="label" >{{ $st_row->name }}</badge> -->
                                                    </td>
                                                    @foreach($statuses as $st_col)
                                                        @if($st_row->id == $st_col->id)
                                                            <td class="checkbox-cell" style="font-size:unset; background-color:{{ $st_row->color }}; color:{{ GeneralHelper::invert_color($st_row->color) }}; opacity: 0.8;">
                                                                <input type="checkbox" id="transition_{{ $st_row->id }}_{{ $st_col->id }}" name="wf_statuses[][]" data-transition-type="creators_group_members" data-rv="{{ $st_row->id }}" data-cv = "{{ $st_col->id }}" value="{{ $st_row->id }}_{{ $st_col->id }}" checked disabled title="Transition to same status"> <small>Always</small>
                                                            </td>
                                                        @else
                                                            <td class="checkbox-cell">
                                                                <div class="row m-0 pl-0 pr-0">
                                                                    <input type="checkbox" id="transition_{{ $st_row->id }}_{{ $st_col->id }}" name="wf_statuses[][]" data-transition-type="creators_group_members" data-rv="{{ $st_row->id }}" data-cv = "{{ $st_col->id }}" value="{{ $st_row->id }}_{{ $st_col->id }}"
                                                                        {{ isset($from_to['creators_group_members'][$st_row->id]) && isset($from_to['creators_group_members'][$st_row->id][$st_col->id] ) ? 'checked' : '' }}
                                                                    >
                                                                    <a href="#" class="ml-2 replicate-btn" title="Replicate Settings Right"><i class="fa fa-angle-double-right"></i></a>
                                                                    <a href="#" class="ml-2 replicate-btn" title="Replicate Settings Down"><i class="fa fa-angle-double-down"></i></a>
                                                                </div>
                                                            </td>
                                                        @endif
                                                        <!-- <td>{{ $st_col->name }}</td> -->
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 p-0 m-0">
                            <div class="panel panel-success">
                                <div class="panel-heading">
                                    Transitions for Executor
                                </div>
                                <div class="panel-body p-1">
                                    <table id="executor_transitions" class="table table-striped table-bordered mb-0">
                                        <thead>
                                            <tr><th>From Status</th><th colspan="{{ count($statuses) }}" style="text-align:center;">To Statuses</th></tr>
                                            <tr>
                                                <th class="checkbox-cell"></th>
                                                @foreach($statuses as $st_col)
                                                    <th class="checkbox-cell" style="padding:2px; font-size:unset; background-color:{{ $st_col->color }}; color:{{ GeneralHelper::invert_color($st_col->color) }}">
                                                        {{ $st_col->name }}
                                                    </th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($statuses as $st_row)
                                                <tr>
                                                    <td class="checkbox-cell" style="font-size:unset; background-color:{{ $st_row->color }}; color:{{ GeneralHelper::invert_color($st_row->color) }}">
                                                        {{ $st_row->name }}
                                                        <!-- <badge class="label" >{{ $st_row->name }}</badge> -->
                                                    </td>
                                                    @foreach($statuses as $st_col)
                                                        @if($st_row->id == $st_col->id)
                                                            <td class="checkbox-cell" style="font-size:unset; background-color:{{ $st_row->color }}; color:{{ GeneralHelper::invert_color($st_row->color) }}; opacity: 0.8;">
                                                                <input type="checkbox" id="transition_{{ $st_row->id }}_{{ $st_col->id }}" name="wf_statuses[][]" data-transition-type="executor" data-rv="{{ $st_row->id }}" data-cv = "{{ $st_col->id }}" value="{{ $st_row->id }}_{{ $st_col->id }}" checked disabled title="Transition to same status"> <small>Always</small>
                                                            </td>
                                                        @else
                                                            <td class="checkbox-cell">
                                                                <div class="row m-0 pl-0 pr-0">
                                                                    <input type="checkbox" id="transition_{{ $st_row->id }}_{{ $st_col->id }}" name="wf_statuses[][]" data-transition-type="executor" data-rv="{{ $st_row->id }}" data-cv = "{{ $st_col->id }}" value="{{ $st_row->id }}_{{ $st_col->id }}"
                                                                        {{ isset($from_to['executor'][$st_row->id]) && isset($from_to['executor'][$st_row->id][$st_col->id] ) ? 'checked' : '' }}
                                                                    >
                                                                    <a href="#" class="ml-2 replicate-btn" title="Replicate Settings Right"><i class="fa fa-angle-double-right"></i></a>
                                                                    <a href="#" class="ml-2 replicate-btn" title="Replicate Settings Down"><i class="fa fa-angle-double-down"></i></a>
                                                                </div>
                                                            </td>
                                                        @endif
                                                        <!-- <td>{{ $st_col->name }}</td> -->
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 p-0 m-0">
                            <div class="panel panel-success">
                                <div class="panel-heading">
                                    Transitions for Other Members from Executor's Group
                                </div>
                                <div class="panel-body p-1">
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-group">
                                                <select id="executors-member-roles" name="executors_member_roles[]" class="form-control select2-multi" multiple="multiple" data-placeholder="Select Roles" >
                                                    @foreach ($roles as $role)
                                                        <option value="{{ $role->id }}" {{ isset($executors_member_roles[$role->id]) ? 'selected=""' : '' }} > {{ $role->name }} </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <table id="executors_group_members_transitions" class="table table-striped table-bordered mb-0">
                                        <thead>
                                            <tr><th>From Status</th><th colspan="{{ count($statuses) }}" style="text-align:center;">To Statuses</th></tr>
                                            <tr>
                                                <th class="checkbox-cell"></th>
                                                @foreach($statuses as $st_col)
                                                    <th class="checkbox-cell" style="padding:2px; font-size:unset; background-color:{{ $st_col->color }}; color:{{ GeneralHelper::invert_color($st_col->color) }}">
                                                        {{ $st_col->name }}
                                                    </th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($statuses as $st_row)
                                                <tr>
                                                    <td class="checkbox-cell" style="font-size:unset; background-color:{{ $st_row->color }}; color:{{ GeneralHelper::invert_color($st_row->color) }}">
                                                        {{ $st_row->name }}
                                                        <!-- <badge class="label" >{{ $st_row->name }}</badge> -->
                                                    </td>
                                                    @foreach($statuses as $st_col)
                                                        @if($st_row->id == $st_col->id)
                                                            <td class="checkbox-cell" style="font-size:unset; background-color:{{ $st_row->color }}; color:{{ GeneralHelper::invert_color($st_row->color) }}; opacity: 0.8;">
                                                                <input type="checkbox" id="transition_{{ $st_row->id }}_{{ $st_col->id }}" name="wf_statuses[][]" data-transition-type="executors_group_members" data-rv="{{ $st_row->id }}" data-cv = "{{ $st_col->id }}" value="{{ $st_row->id }}_{{ $st_col->id }}" checked disabled title="Transition to same status"> <small>Always</small>
                                                            </td>
                                                        @else
                                                            <td class="checkbox-cell">
                                                                <div class="row m-0 pl-0 pr-0">
                                                                    <input type="checkbox" id="transition_{{ $st_row->id }}_{{ $st_col->id }}" name="wf_statuses[][]" data-transition-type="executors_group_members" data-rv="{{ $st_row->id }}" data-cv = "{{ $st_col->id }}" value="{{ $st_row->id }}_{{ $st_col->id }}"
                                                                        {{ isset($from_to['executors_group_members'][$st_row->id]) && isset($from_to['executors_group_members'][$st_row->id][$st_col->id] ) ? 'checked' : '' }}
                                                                    >
                                                                    <a href="#" class="ml-2 replicate-btn" title="Replicate Settings Right"><i class="fa fa-angle-double-right"></i></a>
                                                                    <a href="#" class="ml-2 replicate-btn" title="Replicate Settings Down"><i class="fa fa-angle-double-down"></i></a>
                                                                </div>
                                                            </td>
                                                        @endif
                                                        <!-- <td>{{ $st_col->name }}</td> -->
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 p-0 m-0">
                            <div class="panel panel-info">
                                <div class="panel-heading">
                                    Transitions for all users by Role
                                </div>
                                <div class="panel-body p-1">
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-group">
                                                <select id="general-users-by-role" name="general_users_by_role[]" class="form-control select2-multi" multiple="multiple" data-placeholder="Select Roles" >
                                                    @foreach ($roles as $role)
                                                        <option value="{{ $role->id }}" {{ isset($general_users_by_role[$role->id]) ? 'selected=""' : '' }} > {{ $role->name }} </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <table id="general_transitions_by_role" class="table table-striped table-bordered mb-0">
                                        <thead>
                                            <tr><th>From Status</th><th colspan="{{ count($statuses) }}" style="text-align:center;">To Statuses</th></tr>
                                            <tr>
                                                <th class="checkbox-cell"></th>
                                                @foreach($statuses as $st_col)
                                                    <th class="checkbox-cell" style="padding:2px; font-size:unset; background-color:{{ $st_col->color }}; color:{{ GeneralHelper::invert_color($st_col->color) }}">
                                                        {{ $st_col->name }}
                                                    </th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($statuses as $st_row)
                                                <tr>
                                                    <td class="checkbox-cell" style="font-size:unset; background-color:{{ $st_row->color }}; color:{{ GeneralHelper::invert_color($st_row->color) }}">
                                                        {{ $st_row->name }}
                                                        <!-- <badge class="label" >{{ $st_row->name }}</badge> -->
                                                    </td>
                                                    @foreach($statuses as $st_col)
                                                        @if($st_row->id == $st_col->id)
                                                            <td class="checkbox-cell" style="font-size:unset; background-color:{{ $st_row->color }}; color:{{ GeneralHelper::invert_color($st_row->color) }}; opacity: 0.8;">
                                                                <input type="checkbox" id="transition_{{ $st_row->id }}_{{ $st_col->id }}" name="wf_statuses[][]" data-transition-type="general_by_role" data-rv="{{ $st_row->id }}" data-cv = "{{ $st_col->id }}" value="{{ $st_row->id }}_{{ $st_col->id }}" checked disabled title="Transition to same status"> <small>Always</small>
                                                            </td>
                                                        @else
                                                            <td class="checkbox-cell">
                                                                <div class="row m-0 pl-0 pr-0">
                                                                    <input type="checkbox" id="transition_{{ $st_row->id }}_{{ $st_col->id }}" name="wf_statuses[][]" data-transition-type="general_by_role" data-rv="{{ $st_row->id }}" data-cv = "{{ $st_col->id }}" value="{{ $st_row->id }}_{{ $st_col->id }}"
                                                                        {{ isset($from_to['general_by_role'][$st_row->id]) && isset($from_to['general_by_role'][$st_row->id][$st_col->id] ) ? 'checked' : '' }}
                                                                    >
                                                                    <a href="#" class="ml-2 replicate-btn" title="Replicate Settings Right"><i class="fa fa-angle-double-right"></i></a>
                                                                    <a href="#" class="ml-2 replicate-btn" title="Replicate Settings Down"><i class="fa fa-angle-double-down"></i></a>
                                                                </div>
                                                            </td>
                                                        @endif
                                                        <!-- <td>{{ $st_col->name }}</td> -->
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 p-0 m-0">
                            <div class="panel panel-info">
                                <div class="panel-heading">
                                    Transitions for general users who can edit by Group
                                </div>
                                <div class="panel-body p-1">
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-group">
                                                <select id="general-users-by-group" name="general_users_by_group[]" class="form-control select2-multi" multiple="multiple" data-placeholder="Select Groups" >
                                                    @foreach ($groups as $group)
                                                        <option value="{{ $group->id }}" {{ isset($general_users_by_group[$group->id]) ? 'selected=""' : '' }} > {{ $group->name }} </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <table id="general_transitions_by_group" class="table table-striped table-bordered mb-0">
                                        <thead>
                                            <tr><th>From Status</th><th colspan="{{ count($statuses) }}" style="text-align:center;">To Statuses</th></tr>
                                            <tr>
                                                <th class="checkbox-cell"></th>
                                                @foreach($statuses as $st_col)
                                                    <th class="checkbox-cell" style="padding:2px; font-size:unset; background-color:{{ $st_col->color }}; color:{{ GeneralHelper::invert_color($st_col->color) }}">
                                                        {{ $st_col->name }}
                                                    </th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($statuses as $st_row)
                                                <tr>
                                                    <td class="checkbox-cell" style="font-size:unset; background-color:{{ $st_row->color }}; color:{{ GeneralHelper::invert_color($st_row->color) }}">
                                                        {{ $st_row->name }}
                                                        <!-- <badge class="label" >{{ $st_row->name }}</badge> -->
                                                    </td>
                                                    @foreach($statuses as $st_col)
                                                        @if($st_row->id == $st_col->id)
                                                            <td class="checkbox-cell" style="font-size:unset; background-color:{{ $st_row->color }}; color:{{ GeneralHelper::invert_color($st_row->color) }}; opacity: 0.8;">
                                                                <input type="checkbox" id="transition_{{ $st_row->id }}_{{ $st_col->id }}" name="wf_statuses[][]" data-transition-type="general_by_group" data-rv="{{ $st_row->id }}" data-cv = "{{ $st_col->id }}" value="{{ $st_row->id }}_{{ $st_col->id }}" checked disabled title="Transition to same status"> <small>Always</small>
                                                            </td>
                                                        @else
                                                            <td class="checkbox-cell">
                                                                <div class="row m-0 pl-0 pr-0">
                                                                    <input type="checkbox" id="transition_{{ $st_row->id }}_{{ $st_col->id }}" name="wf_statuses[][]" data-transition-type="general_by_group" data-rv="{{ $st_row->id }}" data-cv = "{{ $st_col->id }}" value="{{ $st_row->id }}_{{ $st_col->id }}"
                                                                        {{ isset($from_to['general_by_group'][$st_row->id]) && isset($from_to['general_by_group'][$st_row->id][$st_col->id] ) ? 'checked' : '' }}
                                                                    >
                                                                    <a href="#" class="ml-2 replicate-btn" title="Replicate Settings Right"><i class="fa fa-angle-double-right"></i></a>
                                                                    <a href="#" class="ml-2 replicate-btn" title="Replicate Settings Down"><i class="fa fa-angle-double-down"></i></a>
                                                                </div>
                                                            </td>
                                                        @endif
                                                        <!-- <td>{{ $st_col->name }}</td> -->
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <button class="btn btn-xs btn-primary" onclick="save_transitions()">Save Fields</button>
                    </div>
                </div>
                <div role="tabpanel" id="interactions" class="tab-pane">
                    <div class="panel-body" style="background-color:#f3f3f4;">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        $('.select2-multi').select2({
            placeholder: "Select Roles", // Placeholder text
            allowClear: true, // Allow clearing all selected options
            width: "100%", // Full width of the parent container
        });
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

    function save_transitions() {
        let selectedTransitions = { new: [], creator: [], creators_group_members: [], executor: [], executors_group_members: [], general_by_role: [], general_by_group: [] };

        const creatorMemberRoles = $('#creator-member-roles').val(); // Array of selected values
        const executorsMemberRoles = $('#executors-member-roles').val();
        const generalUsersByRole = $('#general-users-by-role').val();
        const generalUsersByGroup = $('#general-users-by-group').val();

        // Select all checked checkboxes with the name "wf_statuses[][]"
        document.querySelectorAll('input[name="wf_statuses[][]"]:checked').forEach((checkbox) => {
            const transitionType = checkbox.getAttribute("data-transition-type");
            const rowValue = checkbox.getAttribute("data-rv");
            const colValue = checkbox.getAttribute("data-cv");

            if (selectedTransitions[transitionType] && rowValue != colValue) {
                selectedTransitions[transitionType].push([rowValue, colValue]); // Add the row and column values
            }
        });

        const requiredTypes = ["new", "creator", "executor"];
        const allRequiredHaveRecords = requiredTypes.every((type) => selectedTransitions[type].length > 0);

        if(allRequiredHaveRecords) {

            Swal.fire({
                title: "Saving...",
                text: "Please wait while we save your changes",
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading(); // Show the loading spinner
                }
            });

            $.ajax({
                url: '{{ route("workflows.save_workflow_statuses") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: {{ $workflow->id }},
                    selected_transitions: JSON.stringify(selectedTransitions),
                    creator_member_roles: creatorMemberRoles,
                    executors_member_roles: executorsMemberRoles,
                    general_users_by_role: generalUsersByRole,
                    general_users_by_group: generalUsersByGroup
                },
                success: function(response) {
                    if(response.success) {
                        Swal.fire({
                            title: 'Success!',
                            text: 'Workflow Updated.',
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 1000,
                            timerProgressBar: true
                        }).then(() => {
                            window.location.href = '{{ route("workflows.index") }}';
                        });

                    } else {
                        Swal.fire({title: "Unable to save Workflow", text: "Please try again", icon: "error" });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    let errorMessage = "An error occurred";

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }

                    Swal.fire({title: "Error saving custom fields", text: errorMessage, icon: "error" });
                }
            });
        } else {
            if(selectedTransitions["new"].length < 1) {
                Swal.fire({title: "New status required", text: "Please select at least one new status", icon: "info" });
            }
            if(selectedTransitions["creator"].length < 1) {
                Swal.fire({title: "Creator transitions required", text: "Please select at least one transition for creator", icon: "info" });
            }
            if(selectedTransitions["executor"].length < 1) {
                Swal.fire({title: "Executor transitions required", text: "Please select at least one transition for executor", icon: "info" });
            }
            return;
        }
    }

</script>

@endsection