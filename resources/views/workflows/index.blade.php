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
        <h5>Workflows</h5>
        <div class="ibox-tools">
            <a href="{{ route('workflows.create') }}" class="btn btn-primary btn-xs">Create Workflow</a>
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

        <form method="GET" action="{{ route('workflows.index') }}" class="form-inline mb-2">
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
                    <th><a href="{{ route('workflows.index', array_merge(request()->all(), ['sort' => 'id', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">ID</a></th>
                    <th><a href="{{ route('workflows.index', array_merge(request()->all(), ['sort' => 'name', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">Name</a></th>
                    <th><a href="{{ route('workflows.index', array_merge(request()->all(), ['sort' => 'description', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">Description</a></th>
                    <th><a href="{{ route('workflows.index', array_merge(request()->all(), ['sort' => 'created_by', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">Created by</a></th>
                    <th><a href="{{ route('workflows.index', array_merge(request()->all(), ['sort' => 'created_at', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">Created on</a></th>
                    <th><a href="{{ route('workflows.index', array_merge(request()->all(), ['sort' => 'updated_by', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">Updated by</a></th>
                    <th><a href="{{ route('workflows.index', array_merge(request()->all(), ['sort' => 'updated_at', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">Updated on</a></th>
                    <th style="width: 130px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($workflows as $task_type)
                    <tr>
                        <td>{{ $task_type->id }}</td>
                        <td>{{ $task_type->name }}</td>
                        <td>{{ $task_type->description }}</td>
                        <td>{{ $task_type->creator->name }}</td>
                        <td><small>{{ $task_type->created_at }}</small></td>
                        <td>{{ $task_type->updater->name ?? ''}}</td>
                        <td><small>{{ $task_type->updated_at }}</small></td>
                        <td>
                            @if(session('user_routes')['workflows.show'] ?? false)
                                <a href="{{ route('workflows.show', $task_type->id) }}" class="btn btn-info btn-xs">View</a>
                            @else
                                <a href="#" class="btn btn-info btn-xs disabled">View</a>
                            @endif
                            @if(session('user_routes')['workflows.edit'] ?? false)
                                <a href="{{ route('workflows.edit', $task_type->id) }}" class="btn btn-primary btn-xs">Edit</a>
                            @else
                                <a href="#" class="btn btn-primary btn-xs disabled">Edit</a>
                            @endif
                            @if(session('user_routes')['workflows.destroy'] ?? false)
                                <form action="{{ route('workflows.destroy', $task_type->id) }}" method="POST" style="display: inline-block;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn btn-danger btn-xs delete-button" data-id="{{ $task_type->id }}">Delete</button>
                                </form>
                            @else
                                <a href="#" class="btn btn-danger btn-xs disabled">Delete</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        showing {{ $workflows->count() }} of {{ $workflows->total() }} records {{ $workflows->appends(['search' => request('search'), 'page_size' => $pageSize, 'sort' => $sortColumn, 'direction' => $sortDirection])->links('pagination::bootstrap-4') }}
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.delete-button').forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault(); // Prevent default form submission

            // Get form element
            const form = this.closest('form');

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
                    // Submit the form if confirmed
                    form.submit();
                }
            });
        });
    });
});
</script>

@endsection