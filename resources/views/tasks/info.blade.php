<?php
	use App\Helpers\GeneralHelper;
?>
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
			<td>{{$task_info->tasktype->name}}</td>
		</tr>
		<tr>
			<th>Subject</th>
			<td>{{$task_info->subject}}</td>
		</tr>
		<tr>
			<th>Description</th>
			<td>{{$task_info->description}}</td>
		</tr>
		<tr>
			<th>Status</th>
			<td style="font-size:unset; background-color:{{ $task_info->status->color }}; color:{{ GeneralHelper::invert_color($task_info->status->color) }}">{{$task_info->status->name}}</td>
		</tr>
		<tr>
			<th>Priority</th>
			@if (isset($task_info->priority))
				<td style="font-size:unset; background-color:{{ $task_info->priority->color }}; color:{{ GeneralHelper::invert_color($task_info->priority->color) }}">{{$task_info->priority->name}}</td>
			@else
				<td>{{$task_info->priority->name}}</td>
			@endif
		</tr>
		<tr>
			<th>SLA Rule Name</th>
			<td>{{$task_info->sla->name ?? ''}}</td>
		</tr>


		@foreach ($task_info->taskCustomField as $tcfk => $field)
			<tr>
				<th>{{$custom_fields_lkp[$field->field_id]['name']}}</th>
				<td>{{$field->value}}</td>
			</tr>
		@endforeach


		<tr>
			<th>Creator</th>
			<td>{{$task_info->creator->name ?? ''}}</td>
		</tr>
		<tr>
			<th>Creator Group</th>
			<td>{{$task_info->creatorGroup->name ?? ''}}</td>
		</tr>
		<tr>
			<th>Assignee</th>
			<td>{{$task_info->executor->name ?? ''}}</td>
		</tr>
		<tr>
			<th>Assignee Group</th>
			<td>{{$task_info->executorGroup->name ?? ''}}</td>
		</tr>
		<tr>
			<th>Last Updated By</th>
			<td>{{$task_info->updater->name ?? ''}}</td>
		</tr>
		<tr>
			<th>Last Updated On</th>
			<td>{{$task_info->updated_on ?? ''}}</td>
		</tr>
	</tbody>
</table>