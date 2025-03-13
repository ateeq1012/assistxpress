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
        <h5>Service Details</h5>
        <div class="ibox-tools">
            <a href="{{ route('services.index') }}" class="btn btn-primary btn-xs">Go to Service Catelog</a>
            <a href="{{ route('services.edit', $service->id) }}" class="btn btn-primary btn-xs">Edit This Service</a>
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
                <td><div class="label" style="font-size:unset; background-color:{{ $service->color }}; color:{{ GeneralHelper::invert_color($service->color) }}">{{ $service->name }}</div></td>
            </tr>
            <tr>
                <th>Description</th>
                <td>{{ $service->description }}</td>
            </tr>
            <tr>
                <th>Workflow</th>
                <td>{{ $service->workflow->name }}</td>
            </tr>
            <tr>
                <th>Enabled</th>
                <td>{{ $service->enabled ? 'Enabled' : 'Disabled' }}</td>
            </tr>
            <tr>
                <th>Created By</th>
                <td>
                    <badge class="badge badge-info">{{ $service->creator->name }}</badge>
                    <i class="fa fa-envelope-o"> </i>
                    <a href="mailto:{{ $service->creator->email }}?subject=&body="> {{ $service->creator->email }} </a>
                    @if($service->creator->phone)
                        <i class="fa fa-phone"> </i> <a> {{ $service->creator->phone }} </a>
                    @endif
                </td>
            </tr>
            <tr>
                <th>Created On</th>
                <td>{{ $service->created_at }}</td>
            </tr>
            <tr>
                <th>Updated By</th>
                @if(isset($service->updater->name))
                    <td><badge class="badge badge-info">{{ $service->updater->name }}</badge>
                        <i class="fa fa-envelope-o"> </i> <a href="mailto:{{ $service->updater->email }}?subject=&body="> {{ $service->updater->email}} </a>
                        @if($service->updater->phone)
                            <i class="fa fa-phone"> </i> <a> {{ $service->updater->phone }} </a>
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
