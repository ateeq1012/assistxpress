<?php
    use App\Helpers\GeneralHelper;
?>
@extends('layouts.app')

@section('content')
<style type="text/css">
    th, td {
        padding: 3px 8px !important;
    }
</style>
<div class="ibox pt-2 col-lg-6 container">
    <div class="ibox-title">
        <h5>Task Type Details</h5>
        <div class="ibox-tools">
            <a href="{{ route('task_types.index') }}" class="btn btn-primary btn-xs">Manage Task Types</a>
            <a href="{{ route('task_types.edit', $task_type->id) }}" class="btn btn-primary btn-xs">Edit Task Type</a>
        </div>
    </div>
    <div class="ibox-content">
        @if (session('error'))
            <div class="alert alert-danger alert-dismissable">
                <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
                {{ session('error') }}
            </div>
        @endif
        <table class="table table-bordered table-striped">
            <tr>
                <th>Name</th>
                <td><div class="label" style="font-size:unset; background-color:{{ $task_type->color }}; color:{{ GeneralHelper::invert_color($task_type->color) }}">{{ $task_type->name }}</div></td>
            </tr>
            <tr>
                <th>Description</th>
                <td>{{ $task_type->description }}</td>
            </tr>
            <tr>
                <th>Workflow</th>
                <td>{{ $task_type->workflow->name }}</td>
            </tr>
            <tr>
                <th>Enabled</th>
                <td>{{ $task_type->enabled ? 'Enabled' : 'Disabled' }}</td>
            </tr>
            <tr>
                <th>Created By</th>
                <td>
                    <badge class="badge badge-info">{{ $task_type->creator->name }}</badge>
                    <i class="fa fa-envelope-o"> </i>
                    <a href="mailto:{{ $task_type->creator->email }}?subject=&body="> {{ $task_type->creator->email }} </a>
                    @if($task_type->creator->phone)
                        <i class="fa fa-phone"> </i> <a> {{ $task_type->creator->phone }} </a>
                    @endif
                </td>
            </tr>
            <tr>
                <th>Created On</th>
                <td>{{ $task_type->created_at }}</td>
            </tr>
            <tr>
                <th>Updated By</th>
                @if(isset($task_type->updater->name))
                    <td><badge class="badge badge-info">{{ $task_type->updater->name }}</badge>
                        <i class="fa fa-envelope-o"> </i> <a href="mailto:{{ $task_type->updater->email }}?subject=&body="> {{ $task_type->updater->email}} </a>
                        @if($task_type->updater->phone)
                            <i class="fa fa-phone"> </i> <a> {{ $task_type->updater->phone }} </a>
                        @endif
                    </td>
                @else
                <td>-</td>
                @endif
            </tr>
        </table>
    </div>
</div>
@endsection
