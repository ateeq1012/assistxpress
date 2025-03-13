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
        <h5>Create Service</h5>
        <div class="ibox-tools">
            <a href="{{ route('services.index') }}" class="btn btn-primary btn-xs">Back to Service Catelog</a>
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
        @if (count($workflows) < 1)
            <div class="alert alert-warning alert-dismissable">
                <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
                Not worflows are defined. Please create a <a href="{{ route('workflows.create') }}">workflow</a> first and try again.
            </div>
        @endif



        <form method="POST" action="{{ route('services.store') }}">
            @csrf
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" name="name" class="form-control" value="" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" class="form-control" id="description"></textarea>
            </div>

            <div class="form-group">
                <label for="workflow_id">Workflow</label>
                <select name="workflow_id" class="form-control" id="workflow_id">
                    <option value="">Select Workflow</option>
                    @foreach($workflows as $workflow)
                        <option value="{{ $workflow->id }}">{{ $workflow->name }}</option>
                    @endforeach
                </select>
            </div>
            <!-- <div class="form-group">
                <label for="is_planned">Planned Service Request</label>
                <select name="is_planned" class="form-control" id="is_planned">
                    <option value="">Select an Option</option>
                    <option value="1">Yes</option>
                    <option value="0">No</option>
                </select>
            </div> -->

            <div class="form-group">
                <label for="color">Status Color</label>
                <input type="color" class="form-control p-0" name='color' id="color" value="#d1dade"  title="Choose your color">
            </div>

            <div class="form-group">
                <label for="enabled">Enabled</label>
                <select name="enabled" class="form-control" id="enabled">
                    <option value="1">Yes</option>
                    <option value="0">No</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">Create</button>
        </form>
    </div>
</div>
@endsection