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
<div class="ibox pt-2">
    <div class="ibox-title">
        <h5>Edit Task Priority</h5>
        <div class="ibox-tools">
            <a href="{{ route('statuses.index') }}" class="btn btn-primary btn-xs">Manage Task Priorities</a>
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

        <form method="POST" action="{{ route('task_priorities.store') }}">
            @csrf
            <div class="form-group">
                <label for="name">Task Priority Name</label>
                <input type="text" name="name" class="form-control" value="" required>
            </div>

            <?php
                $status_type = GeneralHelper::statusTypeName();
            ?>

            <div class="form-group">
                <label for="color">Color :</label>
                <input type="color" class="form-control p-0" name='color' id="color" value="#d1dade"  title="Choose your color">
            </div>
            
            <button type="submit" class="btn btn-primary">Create Status</button>
        </form>
    </div>
</div>
@endsection