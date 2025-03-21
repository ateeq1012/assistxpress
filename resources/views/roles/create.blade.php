@extends('layouts.app')

@section('content')
    <div class="ibox pt-2">
        <div class="ibox-title">
            <h5>Create New Role</h5>
            <div class="ibox-tools">
                @if(session('user_routes')['roles.index'] ?? false)
                    <a href="{{ route('roles.index') }}" class="btn btn-primary btn-xs">Manage Roles</a>
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
            <!-- Display Validation Errors -->
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Role Creation Form -->
            <form method="POST" action="{{ route('roles.store') }}">
                @csrf

                <div class="form-group">
                    <label for="name">Role Name</label>
                    <input type="text" name="name" class="form-control" id="name" value="{{ old('name') }}" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" class="form-control" id="description">{{ old('description') }}</textarea>
                </div>

                <!-- <div class="form-group">
                    <label for="enabled">Enabled</label>
                    <select name="enabled" class="form-control" id="enabled">
                        <option value="1" {{ old('enabled') == '1' ? 'selected' : '' }}>Yes</option>
                        <option value="0" {{ old('enabled') == '0' ? 'selected' : '' }}>No</option>
                    </select>
                </div> -->

                <button type="submit" class="btn btn-primary">Create Role</button>
            </form>
        </div>
    </div>
@endsection