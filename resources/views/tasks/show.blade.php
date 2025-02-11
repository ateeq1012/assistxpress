@extends('layouts.app')

@section('content')

<link href="{{ asset('css/plugins/select2/select2.min.css') }}" rel="stylesheet">
<script src="{{ asset('js/plugins/select2/select2.full.min.js') }}"></script>

<style type="text/css">
	td {
		padding: 3px 8px !important;
	}
	tbody th {
		padding: 3px 8px !important;
	}
	#color {
		border-radius: 3px;
	}
	label {
		margin-bottom: 2px;
	}
</style>
<div class="ibox pt-2">
	<div class="ibox-title">
		<h5>View Tassk:{{$task_info->id}}</h5>
		<div class="ibox-tools">
			<a href="{{ route('tasks.index') }}" class="btn btn-primary btn-xs">Manage Tasks</a>
		</div>
	</div>
	<div class="ibox-content" style="height:calc(100vh - 140px); overflow-y:scroll; /*background: #eceff1;*/">
		@if (session('error'))
			<div class="alert alert-danger alert-dismissable">
				<button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
				{{ session('error') }}
			</div>
		@endif
		@if ($errors->any())
			<div class="alert alert-danger alert-dismissable">
				<button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
				<ul>
					@foreach ($errors->all() as $error)
						<li>{{ $error }}</li>
					@endforeach
				</ul>
			</div>
		@endif
		<div class="tabs-container mb-2">
			<ul class="nav nav-tabs" role="tablist">
				<li><a class="nav-link active" data-toggle="tab" href="#tab-info">Info</a></li>
				<li><a class="nav-link" data-toggle="tab" href="#tab-comments">Comments</a></li>
				<li><a class="nav-link" data-toggle="tab" href="#tab-history">History</a></li>
			</ul>
			<div class="tab-content">
				<div role="tabpanel" id="tab-info" class="tab-pane active">
					<div class="panel-body" style="max-height: 750px;overflow: auto;">
						@include('tasks.info', [
						    'task_info' => $task_info,
						    'custom_fields_lkp' => $custom_fields_lkp,
						])
					</div>
				</div>
				<div role="tabpanel" id="tab-comments" class="tab-pane">
					<div class="panel-body" style="max-height: 750px;overflow: auto;">
						@include('tasks.comments', [
						    'task_comments' => $task_comments,
						])
					</div>
				</div>
				<div role="tabpanel" id="tab-history" class="tab-pane">
					<div class="panel-body" style="max-height: 750px;overflow: auto;">
						@include('tasks.history', [
						    'task_info' => $task_info,
						    'custom_fields_lkp' => $custom_fields_lkp,
						    'fileds_to_make_history' => $fileds_to_make_history,
						    'task_logs' => $task_logs,
						    'custom_field_id_lkp' => $custom_field_id_lkp,
						    'project_lkp' => $project_lkp,
						    'task_type_lkp' => $task_type_lkp,
						    'priority_lkp' => $priority_lkp,
						    'status_lkp' => $status_lkp
						])
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

@endsection
