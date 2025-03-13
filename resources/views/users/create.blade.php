@extends('layouts.app')

@section('content')
    <div class="ibox pt-2">
        <div class="ibox-title">
            <h5>Create New User</h5>
            <div class="ibox-tools">
                <a href="{{ route('users.index') }}" class="btn btn-primary btn-xs">Manage User</a>
            </div>
        </div>
        <div class="ibox-content">
            @if (session('error'))
                <div class="alert alert-danger alert-dismissable">
                    <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
                    {{ session('error') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('users.store') }}" method="POST" autocomplete="off">
                @csrf

                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}" autocomplete="off" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="phone" name="phone" class="form-control" value="{{ old('phone') }}" autocomplete="off">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" class="form-control" autocomplete="off" required>
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="form-control" autocomplete="off" required>
                </div>

                <div class="form-group">
                    <label for="role_id">Role</label>
                    <select name="role_id" class="form-control" required>
                        <option value="">Select Role</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="enabled">Enabled</label>
                    <select name="enabled" class="form-control" id="enabled">
                        <option value="1" {{ old('enabled') == '1' ? 'selected' : '' }}>Yes</option>
                        <option value="0" {{ old('enabled') == '0' ? 'selected' : '' }}>No</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Create User</button>
            </form>
        </div>
    </div>
@endsection
