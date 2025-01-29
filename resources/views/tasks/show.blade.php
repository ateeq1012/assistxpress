<?php
	use App\Helpers\GeneralHelper;
?>
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
		<h5>View Tassk:{{$task->id}}</h5>
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

		<table class="table table-striped table-bordered">
			<thead>
				<tr>
					<th>Field</th>
					<th>Value</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<th>Task Type</th>
					<td>{{$task->tasktype->name}}</td>
				</tr>
				<tr>
					<th>Subject</th>
					<td>{{$task->subject}}</td>
				</tr>
				<tr>
					<th>Description</th>
					<td>{{$task->description}}</td>
				</tr>
				<tr>
					<th>Status</th>
					<td style="font-size:unset; background-color:{{ $task->status->color }}; color:{{ GeneralHelper::invert_color($task->status->color) }}">{{$task->status->name}}</td>
				</tr>
				<tr>
					<th>Priority</th>
					@if (isset($task->priority))
						<td style="font-size:unset; background-color:{{ $task->priority->color }}; color:{{ GeneralHelper::invert_color($task->priority->color) }}">{{$task->priority->name}}</td>
					@else
						<td>{{$task->priority->name}}</td>
					@endif
				</tr>
				<tr>
					<th>SLA Rule Name</th>
					<td>{{$task->sla->name ?? ''}}</td>
				</tr>


				@foreach ($task->taskCustomField as $tcfk => $field)
					<tr>
						<th>{{$custom_fields_lkp[$field->field_id]['name']}}</th>
						<td>{{$field->value}}</td>
					</tr>
				@endforeach


				<tr>
					<th>Creator</th>
					<td>{{$task->creator->name ?? ''}}</td>
				</tr>
				<tr>
					<th>Creator Group</th>
					<td>{{$task->creatorGroup->name ?? ''}}</td>
				</tr>
				<tr>
					<th>Assignee</th>
					<td>{{$task->executor->name ?? ''}}</td>
				</tr>
				<tr>
					<th>Assignee Group</th>
					<td>{{$task->executorGroup->name ?? ''}}</td>
				</tr>
				<tr>
					<th>Last Updated By</th>
					<td>{{$task->updater->name ?? ''}}</td>
				</tr>
				<tr>
					<th>Last Updated On</th>
					<td>{{$task->updated_on ?? ''}}</td>
				</tr>
			</tbody>
		</table>
	</div>
</div>

@endsection
