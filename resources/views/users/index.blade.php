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
        <h5>Users</h5>
        <div class="ibox-tools">
            <div class="row m-0 p-0">
                @if(session('user_routes')['users.create'] ?? false)
                    <a href="{{ route('users.create') }}" class="btn btn-primary btn-xs mr-1">Create User</a>
                @endif
                @if(session('user_routes')['users.download'] ?? false)
                    <form action="{{ route('users.download') }}" method="POST" target="_blank">
                        @csrf
                        <button type="submit" class="btn btn-success btn-xs">Download Users</button>
                    </form>
                @endif
            </div>
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
        <form method="GET" action="{{ route('users.index') }}" class="form-inline mb-2">
            <div class="form-group mr-2">
                <label for="page_size" class="pr-2">Records per page:</label>
                <select name="page_size" id="page_size" class="form-control pt-0 pb-0" onchange="this.form.submit()">
                    <option value="10" {{ request('page_size') == 10 ? 'selected' : '' }}>10</option>
                    <option value="25" {{ request('page_size') == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ request('page_size') == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ request('page_size') == 100 ? 'selected' : '' }}>100</option>
                </select>
            </div>
            <div class="form-group mr-2 float-right">
                <input type="text" name="search" class="form-control" placeholder="Search by name or email" value="{{ request('search') }}" style="padding: 3px 12px; min-width: 300px;">
            </div>
        </form>

        <!-- Display the users -->
        <table class="table table-striped table-bordered mt-3">
            <thead>
                <tr>
                    <th><a href="{{ route('users.index', array_merge(request()->all(), ['sort' => 'id', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">ID</a></th>
                    <th><a href="{{ route('users.index', array_merge(request()->all(), ['sort' => 'name', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">Name</a></th>
                    <th><a href="{{ route('users.index', array_merge(request()->all(), ['sort' => 'email', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">Email</a></th>
                    <th><a href="{{ route('users.index', array_merge(request()->all(), ['sort' => 'phone', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">Phone</a></th>
                    <th>Role</th>
                    <th>Groups</th>
                    <th><a href="{{ route('users.index', array_merge(request()->all(), ['sort' => 'created_by', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">Created by</a></th>
                    <th><a href="{{ route('users.index', array_merge(request()->all(), ['sort' => 'updated_by', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">Updated by</a></th>
                    <th><a href="{{ route('users.index', array_merge(request()->all(), ['sort' => 'created_at', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">Created on</a></th>
                    <th><a href="{{ route('users.index', array_merge(request()->all(), ['sort' => 'updated_at', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">Updated on</a></th>
                    <th><a href="{{ route('users.index', array_merge(request()->all(), ['sort' => 'enabled', 'direction' => $sortDirection === 'asc' ? 'desc' : 'asc'])) }}">Status</a></th>
                    <th style="width: 130px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->phone ?? '' }}</td>
                        <td>{{ $user->role->name ?? '' }}</td>
                        <td>{{ $user->groups->pluck('name')->implode(', ') }}</td>
                        <td>{{ $user->creator->name }}</td>
                        <td>{{ $user->updater->name ?? '' }}</td>
                        <td>{{ $user->created_at }}</td>
                        <td>{{ $user->updated_at ?? ''}}</td>
                        <td>{!! ($user->enabled == 1 ? '<badge class="badge badge-primary">Enabled</badge>' : '<badge class="badge badge-warning">Disabled</badge>') !!}</td>
                        <td>
                            @if(session('user_routes')['groups.show'] ?? false)
                                <a href="{{ route('users.show', $user->id) }}" class="btn btn-info btn-xs">View</a>
                            @else
                                <a href="#" class="btn btn-info btn-xs disabled">View</a>
                            @endif
                            @if(session('user_routes')['groups.edit'] ?? false)
                                <a href="{{ route('users.edit', $user->id) }}" class="btn btn-primary btn-xs">Edit</a>
                            @else
                                <a href="#" class="btn btn-primary btn-xs disabled">Edit</a>
                            @endif
                            @if(session('user_routes')['groups.destroy'] ?? false)
                                <form action="{{ route('users.destroy', $user->id) }}" method="POST" style="display: inline-block;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn btn-danger btn-xs delete-button" data-id="{{ $user->id }}">Delete</button>
                                </form>
                            @else
                                <a href="#" class="btn btn-danger btn-xs disabled">Delete</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        showing {{ $users->count() }} of {{ $users->total() }} records {{ $users->appends(['search' => request('search'), 'page_size' => $pageSize, 'sort' => $sortColumn, 'direction' => $sortDirection])->links('pagination::bootstrap-4') }}

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