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
			<th>Service Domain</th>
			<td>{{$service_request_info->serviceDomain->name}}</td>
		</tr>
		<tr>
			<th>Service</th>
			<td>{{$service_request_info->service->name}}</td>
		</tr>
		<tr>
			<th>Subject</th>
			<td>{{$service_request_info->subject}}</td>
		</tr>
		<tr>
			<th>Description</th>
			<td>{{$service_request_info->description}}</td>
		</tr>
		<tr>
			<th>Status</th>
			<td style="font-size:unset; background-color:{{ $service_request_info->status->color }}; color:{{ GeneralHelper::invert_color($service_request_info->status->color) }}">{{$service_request_info->status->name}}</td>
		</tr>
		<tr>
			<th>Priority</th>
			@if (isset($service_request_info->priority))
				<td style="font-size:unset; background-color:{{ $service_request_info->priority->color }}; color:{{ GeneralHelper::invert_color($service_request_info->priority->color) }}">{{$service_request_info->priority->name}}</td>
			@else
				<td>{{$service_request_info->priority->name}}</td>
			@endif
		</tr>
		<tr>
			<th>SLA Rule Name</th>
			<td>{{$service_request_info->sla->name ?? ''}}</td>
		</tr>


		@foreach ($service_request_info->serviceRequestCustomField as $tcfk => $field)
			<tr>
				<th>{{$custom_fields_lkp[$field->field_id]['name']}}</th>

				<td>
					@if($custom_fields_lkp[$field->field_id]['field_type'] == 'Datetime Picker')
						{{ date('Y-m-d H:i:s', strtotime($field->value))}}
					@else
						{{$field->value}}
					@endif
				</td>
			</tr>
		@endforeach


		<tr>
			<th>Creator</th>
			<td>{{$service_request_info->creator->name ?? ''}}</td>
		</tr>
		<tr>
			<th>Created At</th>
			<td>{{$service_request_info->created_at ?? ''}}</td>
		</tr>
		<tr>
			<th>Creator Group</th>
			<td>{{$service_request_info->creatorGroup->name ?? ''}}</td>
		</tr>
		<tr>
			<th>Assignee</th>
			<td>{{$service_request_info->executor->name ?? ''}}</td>
		</tr>
		<tr>
			<th>Assignee Group</th>
			<td>{{$service_request_info->executorGroup->name ?? ''}}</td>
		</tr>
		<tr>
			<th>Last Updated By</th>
			<td>{{$service_request_info->updater->name ?? ''}}</td>
		</tr>
		<tr>
			<th>Last Updated At</th>
			<td>{{$service_request_info->updated_at ?? ''}}</td>
		</tr>
	</tbody>
</table>