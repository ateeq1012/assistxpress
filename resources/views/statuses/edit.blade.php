<?php
    use App\Helpers\GeneralHelper;
?>
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
        <h5>Edit Status</h5>
        <div class="ibox-tools">
            <a href="{{ route('statuses.index') }}" class="btn btn-primary btn-xs">Manage Statuses</a>
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


        <form action="{{ route('statuses.update', $status->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label for="name">Status Name</label>
                <input type="text" name="name" class="form-control" value="{{ $status->name }}" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" class="form-control" id="description">{{ old('description') }}</textarea>
            </div>
            <?php
                $status_type = GeneralHelper::statusTypeName();
            ?>

            <div class="form-group">
                <label for="type">Stage</label>
                <select name="type" class="form-control" id="type">
                    <option value="">Select an option</option>
                    @foreach($status_type as $type_id => $label)
                        <option value="{{ $type_id }}" {{ $status->type == $type_id ? 'selected' : '' }}>{{$label}}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="order">Precedence</label>
                <input type="number" name="order" class="form-control" value="{{ $status->order }}" required>
            </div>

            <div class="form-group">
                <label for="color">Color :</label>
                <input type="color" class="form-control p-0" name='color' id="color" value="{{ $status->color }}"  title="Choose your color">
            </div>
            
            <button type="submit" class="btn btn-primary">Update Status</button>
        </form>
    </div>
</div>
@endsection