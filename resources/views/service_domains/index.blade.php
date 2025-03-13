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
        <h5>Service Domains</h5>
        <div class="ibox-tools">
            <a href="{{ route('service_domains.create') }}" class="btn btn-primary btn-xs">Create Service Domain</a>
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

        <form method="GET" action="{{ route('service_domains.index') }}" class="form-inline mb-2">
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
                    <th><a href="{{ route('service_domains.index', array_merge(request()->all(), ['sort' => 'id', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">ID</a></th>
                    <th><a href="{{ route('service_domains.index', array_merge(request()->all(), ['sort' => 'name', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">Name</a></th>
                    <th><a href="{{ route('service_domains.index', array_merge(request()->all(), ['sort' => 'description', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">Description</a></th>
                    <th><a href="{{ route('service_domains.index', array_merge(request()->all(), ['sort' => 'enabled', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">Enabled</a></th>
                    <th><a href="{{ route('service_domains.index', array_merge(request()->all(), ['sort' => 'closed', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">Closed</a></th>
                    <th><a href="{{ route('service_domains.index', array_merge(request()->all(), ['sort' => 'created_by', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">Created by</a></th>
                    <th><a href="{{ route('service_domains.index', array_merge(request()->all(), ['sort' => 'created_at', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">Created on</a></th>
                    <th><a href="{{ route('service_domains.index', array_merge(request()->all(), ['sort' => 'updated_by', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">Updated by</a></th>
                    <th><a href="{{ route('service_domains.index', array_merge(request()->all(), ['sort' => 'updated_at', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">Updated on</a></th>
                    <th style="width: 130px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($service_domains as $service_domain)
                    <tr>
                        <td>{{ $service_domain->id }}</td>
                        <td><div class="label" style="font-size:unset; background-color:{{ $service_domain->color }}; color:{{ GeneralHelper::invert_color($service_domain->color) }}">{{ $service_domain->name }}</div></td>
                        <td>{{ $service_domain->description }}</td>
                        <td>{!! ($service_domain->enabled == 1 ? '<badge class="badge badge-primary">Enabled</badge>' : '<badge class="badge badge-warning">Disabled</badge>') !!}</td>
                        <td>{!! ($service_domain->closed == 1 ? '<badge class="badge badge-warning">Closed</badge>' : '<badge class="badge badge-info">Active</badge>') !!}</td>
                        <td>{{ $service_domain->creator->name }}</td>
                        <td><small>{{ $service_domain->created_at }}</small></td>
                        <td>{{ $service_domain->updater->name ?? ''}}</td>
                        <td><small>{{ $service_domain->updated_at }}</small></td>
                        <td>
                            @if(session('user_routes')['service_domains.show'] ?? false)
                                <a href="{{ route('service_domains.show', $service_domain->id) }}" class="btn btn-info btn-xs">View</a>
                            @else
                                <a href="#" class="btn btn-info btn-xs disabled">View</a>
                            @endif
                            @if(session('user_routes')['service_domains.edit'] ?? false)
                                <a href="{{ route('service_domains.edit', $service_domain->id) }}" class="btn btn-primary btn-xs">Edit</a>
                            @else
                                <a href="#" class="btn btn-primary btn-xs disabled">Edit</a>
                            @endif
                            @if(session('user_routes')['service_domains.destroy'] ?? false)
                                <form action="{{ route('service_domains.destroy', $service_domain->id) }}" method="POST" style="display: inline-block;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn btn-danger btn-xs delete-button" data-id="{{ $service_domain->id }}">Delete</button>
                                </form>
                            @else
                                <a href="#" class="btn btn-danger btn-xs disabled">Delete</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        showing {{ $service_domains->count() }} of {{ $service_domains->total() }} records {{ $service_domains->appends(['search' => request('search'), 'page_size' => $pageSize, 'sort' => $sortColumn, 'direction' => $sortDirection])->links('pagination::bootstrap-4') }}
    </div>
</div>

@endsection