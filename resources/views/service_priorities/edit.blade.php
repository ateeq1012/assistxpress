@extends('layouts.app')

@section('content')
<style type="text/css">
    td {
        padding: 3px 8px !important;
    }
    #color {
        border-radius: 3px;
    }
</style>
<div class="ibox pt-2 col-lg-6 container">
    <div class="ibox-title">
        <h5>Edit Service Priority</h5>
        <div class="ibox-tools">
            <a href="{{ route('service_priorities.index') }}" class="btn btn-primary btn-xs">Manage Service Priorities</a>
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


        <form action="{{ route('service_priorities.update', $service_priority->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label for="name">Service Priority Name</label>
                <input type="text" name="name" class="form-control" value="{{ $service_priority->name }}" required>
            </div>

            <div class="form-group">
                <label for="color">Color :</label>
                <input type="color" class="form-control p-0" name='color' id="color" value="{{ $service_priority->color }}"  title="Choose your color">
            </div>
            
            <button type="submit" class="btn btn-primary">Update Status</button>
        </form>
    </div>
</div>
@endsection