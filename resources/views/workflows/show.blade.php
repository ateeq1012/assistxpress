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
        <h5>Workflow Details</h5>
        <div class="ibox-tools">
            <a href="{{ route('workflows.index') }}" class="btn btn-primary btn-xs">Manage Workflows</a>
            <a href="{{ route('workflows.edit', $workflow->id) }}" class="btn btn-primary btn-xs">Edit Workflow</a>
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
                <td>{{ $workflow->name }}</td>
            </tr>
            <tr>
                <th>Description</th>
                <td>{{ $workflow->description }}</td>
            </tr>
            <tr>
                <th>Created By</th>
                <td>
                    <badge class="badge badge-info">{{ $workflow->creator->name }}</badge>
                    <i class="fa fa-envelope-o"> </i>
                    <a href="mailto:{{ $workflow->creator->email }}?subject=&body="> {{ $workflow->creator->email }} </a>
                    @if($workflow->creator->phone)
                        <i class="fa fa-phone"> </i> <a> {{ $workflow->creator->phone }} </a>
                    @endif
                </td>
            </tr>
            <tr>
                <th>Created On</th>
                <td>{{ $workflow->created_at }}</td>
            </tr>
            <tr>
                <th>Updated By</th>
                @if(isset($workflow->updater->name))
                    <td><badge class="badge badge-info">{{ $workflow->updater->name }}</badge>
                        <i class="fa fa-envelope-o"> </i> <a href="mailto:{{ $workflow->updater->email }}?subject=&body="> {{ $workflow->updater->email}} </a>
                        @if($workflow->updater->phone)
                            <i class="fa fa-phone"> </i> <a> {{ $workflow->updater->phone }} </a>
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
