@extends('layouts.app')

@section('content')

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

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
        <h5>Groups</h5>
        <div class="ibox-tools">
            @if(session('user_routes')['groups.create'] ?? false)
                <a href="{{ route('groups.create') }}" class="btn btn-primary btn-xs">Create Group</a>
            @endif
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

        <form method="GET" action="{{ route('groups.index') }}" class="form-inline mb-2">
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
                    <th><a href="{{ route('groups.index', array_merge(request()->all(), ['sort' => 'id', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">ID</a></th>
                    <th><a href="{{ route('groups.index', array_merge(request()->all(), ['sort' => 'name', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">Name</a></th>
                    <th><a href="{{ route('groups.index', array_merge(request()->all(), ['sort' => 'description', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">Description</a></th>
                    <th>Parent Group</th>
                    <th>Child Groups</th>
                    <th><a href="{{ route('groups.index', array_merge(request()->all(), ['sort' => 'created_by', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">Created by</a></th>
                    <th><a href="{{ route('groups.index', array_merge(request()->all(), ['sort' => 'created_at', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">Created on</a></th>
                    <th><a href="{{ route('groups.index', array_merge(request()->all(), ['sort' => 'updated_by', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">Updated by</a></th>
                    <th><a href="{{ route('groups.index', array_merge(request()->all(), ['sort' => 'updated_at', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">Updated on</a></th>
                    <th><a href="{{ route('groups.index', array_merge(request()->all(), ['sort' => 'enabled', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">Status</a></th>
                    <th style="width: 130px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($groups as $group)
                    @php
                        $child_names = [];
                        if(count($group->children)) {
                            foreach ($group->children as $child) {
                                $child_names[] = $child->name;
                            }
                        }
                        $child_name_labels = '';
                        if(count($child_names)) {
                            $child_name_labels = '<badge class="badge badge-info">'.implode('</badge> <badge class="badge badge-info">', $child_names).'</badge>';
                        }
                    @endphp
                    <tr>
                        <td>{{ $group->id }}</td>
                        <td>{{ $group->name }}</td>
                        <td>{{ $group->description }}</td>
                        <td>{{ $group->parent->name ?? '' }}</td>
                        <td>{!! $child_name_labels !!}</td>
                        <td>{{ $group->creator->name }}</td>
                        <td>{{ $group->created_at }}</td>
                        <td>{{ $group->updater->name ?? '' }}</td>
                        <td>{{ $group->updated_at ?? '' }}</td>
                        <td>{!! ($group->enabled == 1 ? '<badge class="badge badge-primary">Enabled</badge>' : '<badge class="badge badge-warning">Disabled</badge>') !!}</td>
                        <td>
                            @if(session('user_routes')['groups.show'] ?? false)
                                <a href="{{ route('groups.show', $group->id) }}" class="btn btn-info btn-xs">View</a>
                            @else
                                <a href="#" class="btn btn-info btn-xs disabled">View</a>
                            @endif
                            @if(session('user_routes')['groups.edit'] ?? false)
                                <a href="{{ route('groups.edit', $group->id) }}" class="btn btn-primary btn-xs">Edit</a>
                            @else
                                <a href="#" class="btn btn-primary btn-xs disabled">Edit</a>
                            @endif
                            @if(session('user_routes')['groups.destroy'] ?? false)
                                <form action="{{ route('groups.destroy', $group->id) }}" method="POST" style="display: inline-block;" class="delete-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn btn-danger btn-xs delete-button" data-id="{{ $group->id }}">Delete</button>
                                </form>
                            @else
                                <a href="#" class="btn btn-danger btn-xs disabled">Delete</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        showing {{ $groups->count() }} of {{ $groups->total() }} records
        {{ $groups->appends(['search' => request('search'), 'page_size' => $pageSize, 'sort' => $sortColumn, 'direction' => $sortDirection])->links('pagination::bootstrap-4') }}

    </div>
</div>

@endsection
