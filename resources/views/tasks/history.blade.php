<div class="activity-stream">
	@foreach ($task_logs as $log)
		@if ($log['field_type'] == 1)
			@if (isset($fileds_to_make_history[$log['field_name']]))
				@php
					$old_val = $log['old_value'];
					$new_val = $log['new_value'];
					if( $log['field_name'] == 'project_id' ) {
						if(isset($project_lkp[$old_val])) {
							$old_val = $project_lkp[$old_val];
						}
						if(isset($project_lkp[$new_val])) {
							$new_val = $project_lkp[$new_val];
						}
					}
					if( $log['field_name'] == 'task_type_id' ) {
						if(isset($task_type_lkp[$old_val])) {
							$old_val = $task_type_lkp[$old_val];
						}
						if(isset($task_type_lkp[$new_val])) {
							$new_val = $task_type_lkp[$new_val];
						}
					}
					if( $log['field_name'] == 'priority_id' ) {
						if(isset($priority_lkp[$old_val])) {
							$old_val = $priority_lkp[$old_val];
						}
						if(isset($priority_lkp[$new_val])) {
							$new_val = $priority_lkp[$new_val];
						}
					}
					if( $log['field_name'] == 'status_id' ) {
						if(isset($status_lkp[$old_val])) {
							$old_val = $status_lkp[$old_val];
						}
						if(isset($status_lkp[$new_val])) {
							$new_val = $status_lkp[$new_val];
						}
					}
				@endphp
				<div class="stream">
					<div class="stream-badge">
						<i class="fa fa-circle"></i>
					</div>
					<div class="stream-panel">
						<div class="stream-info">
							<a>
								<!-- <img src="img/a5.jpg" /> -->
								<span>{{$log['creator']['name'] ?? ''}}</span>
								<!-- <span class="date">Today at 01:32:40 am</span> -->
                                <i class="fa fa-envelope-o"> </i> <a href="mailto:{{ $log['creator']['email'] }} }}?subject=&body="> {{ $log['creator']['email'] }} </a>
                                @if($log['creator']['phone'])
                                    <i class="fa fa-phone"> </i> <a> {{ $log['creator']['phone'] }} </a>
                                @endif
								<span class="date">{{ date('Y-M-d H:i:s', strtotime($log['created_at']))}}</span>
							</a>
						</div>
						@if (isset($log['old_value']))
							Changed {{$fileds_to_make_history[$log['field_name'] ]}} from <label class="label">{{ $old_val }}</label> to <label class="label">{{ $new_val }}</label>
						@else
							Added {{$fileds_to_make_history[$log['field_name'] ]}} value <label class="label">{{ $new_val }}</label>
						@endif
					</div>
				</div>
			@endif
		@elseif ($log['field_type'] == 2)
			@if (isset($custom_field_id_lkp[$log['field_name']]))
				<div class="stream">
					<div class="stream-badge">
						<i class="fa fa-circle"></i>
					</div>
					<div class="stream-panel">
						<div class="stream-info">
							<a>
								<!-- <img src="img/a5.jpg" /> -->
								<span>{{$log['creator']['name'] ?? ''}}</span>
								<!-- <span class="date">Today at 01:32:40 am</span> -->
                                <i class="fa fa-envelope-o"> </i> <a href="mailto:{{ $log['creator']['email'] }} }}?subject=&body="> {{ $log['creator']['email'] }} </a>
                                @if($log['creator']['phone'])
                                    <i class="fa fa-phone"> </i> <a> {{ $log['creator']['phone'] }} </a>
                                @endif
								<span class="date">{{ date('Y-M-d H:i:s', strtotime($log['created_at']))}}</span>
							</a>
						</div>
						@if (isset($log['old_value']))
							Changed {{ $custom_field_id_lkp[$log['field_name']] }} from <label class="label">{{$log['old_value']}}</label> to <label class="label">{{$log['new_value']}}</label>
						@else
							Added {{ $custom_field_id_lkp[$log['field_name']] }} value <label class="label">{{$log['new_value']}}</label>
						@endif
					</div>
				</div>
			@endif
		@endif
	@endforeach

	<div class="stream">
		<div class="stream-badge">
			<i class="fa fa-circle"></i>
		</div>
		<div class="stream-panel">
			<div class="stream-info">
				<a>
					<!-- <img src="img/a5.jpg" /> -->
					<span>{{$task_info['creator']['name'] ?? ''}}</span>
					<!-- <span class="date">Today at 01:32:40 am</span> -->
                    <i class="fa fa-envelope-o"> </i> <a href="mailto:{{ $task_info['creator']['email'] }} }}?subject=&body="> {{ $task_info['creator']['email'] }} </a>
                    @if($task_info['creator']['phone'])
                        <i class="fa fa-phone"> </i> <a> {{ $task_info['creator']['phone'] }} </a>
                    @endif
					<span class="date">{{ date('Y-M-d H:i:s', strtotime($task_info['created_at']))}}</span>
				</a>
			</div>
			Created
		</div>
	</div>
</div>