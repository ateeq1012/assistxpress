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
        <h5>Status Details</h5>
        <div class="ibox-tools">
            <a href="{{ route('statuses.index') }}" class="btn btn-primary btn-xs">Manage Statuses</a>
            <a href="{{ route('statuses.edit', $status->id) }}" class="btn btn-primary btn-xs">Edit Status</a>
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
                <td><div class="label" style="font-size:unset; background-color:{{ $status->color }}; color:{{ GeneralHelper::invert_color($status->color) }}">{{ $status->name }}</div></td>
            </tr>
            <tr>
                <th>Description</th>
                <td>{{ $status->description }}</td>
            </tr>
            <tr>
                <th>Stage</th>
                <td>{{ GeneralHelper::statusTypeName($status->type) }}</td>
            </tr>
            <tr>
                <th>Status</th>
                <td>{{ $status->enabled ? 'Enabled' : 'Disabled' }}</td>
            </tr>
        </table>
    </div>
</div>
@endsection
