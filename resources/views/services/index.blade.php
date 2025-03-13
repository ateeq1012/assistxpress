<?php
    use App\Helpers\GeneralHelper;
?>

@extends('layouts.app')

@section('content')

<style type="text/css">
    td {
        padding: 3px 8px !important;
    }
    .btn-xs {
      padding: 0.1rem 0.2rem;
    }
</style>
<div class="ibox pt-2">
    <div class="ibox-title">
        <h5>Service Catelog</h5>
        <div class="ibox-tools">
            <a href="{{ route('services.create') }}" class="btn btn-primary btn-xs">Create Service</a>
        </div>
    </div>
    <div class="ibox-content">

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

        <form method="GET" action="{{ route('services.index') }}" class="form-inline mb-2">
            <div class="form-group mr-2">
                <label for="page_size" class="pr-2">Records per page: </label>
                <select name="page_size" id="page_size" class="form-control pt-0 pb-0" onchange="this.form.submit()" style="height:28px">
                    <option value="10" {{ request('page_size') == 10 ? 'selected' : '' }}>10</option>
                    <option value="25" {{ request('page_size') == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ request('page_size') == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ request('page_size') == 100 ? 'selected' : '' }}>100</option>
                </select>
            </div>
            <div class="form-group mr-2 float-right">
                <input type="text" name="search" class="form-control" placeholder="Search by name or description" value="{{ request('search') }}" style="padding: 3px 12px; min-width: 300px;">
            </div>
        </form>

        <table class="table table-striped table-bordered mt-3">
            <thead>
                <tr>
                    <th><a href="{{ route('services.index', array_merge(request()->all(), ['sort' => 'id', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">ID</a></th>
                    <th><a href="{{ route('services.index', array_merge(request()->all(), ['sort' => 'name', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">Service Name</a></th>
                    <th><a href="{{ route('services.index', array_merge(request()->all(), ['sort' => 'description', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">Description</a></th>
                    <th><a href="{{ route('services.index', array_merge(request()->all(), ['sort' => 'workflow_id', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">Workflow</a></th>
                    <!-- <th><a href="{{ route('services.index', array_merge(request()->all(), ['sort' => 'is_planned', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">Planned Service Request</a></th> -->
                    <th><a href="{{ route('services.index', array_merge(request()->all(), ['sort' => 'enabled', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">Enabled</a></th>
                    <th><a href="{{ route('services.index', array_merge(request()->all(), ['sort' => 'created_by', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">Created by</a></th>
                    <th><a href="{{ route('services.index', array_merge(request()->all(), ['sort' => 'created_at', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">Created on</a></th>
                    <th><a href="{{ route('services.index', array_merge(request()->all(), ['sort' => 'updated_by', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">Updated by</a></th>
                    <th><a href="{{ route('services.index', array_merge(request()->all(), ['sort' => 'updated_at', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">Updated on</a></th>
                    <th style="width: 130px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($services as $service)
                    <tr>
                        <td>{{ $service->id }}</td>
                        <td><div class="label" style="font-size:unset; background-color:{{ $service->color }}; color:{{ GeneralHelper::invert_color($service->color) }}">{{ $service->name }}</div></td>
                        <td>{{ $service->description }}</td>
                        <td>{{ $service->workflow->name }}</td>
                        <!-- <td>{!! ($service->is_planned == 1 ? '<badge class="badge badge-primary">Yes</badge>' : '<badge class="badge badge-info">No</badge>') !!}</td> -->
                        <td>{!! ($service->enabled == 1 ? '<badge class="badge badge-primary">Enabled</badge>' : '<badge class="badge badge-warning">Disabled</badge>') !!}</td>
                        <td>{{ $service->creator->name }}</td>
                        <td><small>{{ $service->created_at }}</small></td>
                        <td>{{ $service->updater->name ?? ''}}</td>
                        <td><small>{{ $service->updated_at }}</small></td>
                        <td>
                            @if(session('user_routes')['services.show'] ?? false)
                                <a href="{{ route('services.show', $service->id) }}" class="btn btn-info btn-xs">View</a>
                            @else
                                <a href="#" class="btn btn-info btn-xs disabled">View</a>
                            @endif
                            @if(session('user_routes')['services.edit'] ?? false)
                                <a href="{{ route('services.edit', $service->id) }}" class="btn btn-primary btn-xs">Edit</a>
                            @else
                                <a href="#" class="btn btn-primary btn-xs disabled">Edit</a>
                            @endif
                            @if(session('user_routes')['services.destroy'] ?? false)
                                <form action="{{ route('services.destroy', $service->id) }}" method="POST" style="display: inline-block;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn btn-danger btn-xs delete-button" data-id="{{ $service->id }}">Delete</button>
                                </form>
                            @else
                                <a href="#" class="btn btn-danger btn-xs disabled">Delete</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        showing {{ $services->count() }} of {{ $services->total() }} records {{ $services->appends(['search' => request('search'), 'page_size' => $pageSize, 'sort' => $sortColumn, 'direction' => $sortDirection])->links('pagination::bootstrap-4') }}
    </div>
</div>

@endsection