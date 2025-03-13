<div class="activity-stream">
	@foreach ($service_request_logs as $log)
		@if ($log['field_type'] == 1)
			@if (isset($fileds_to_make_history[$log['field_name']]))
				@php
					$old_val = $log['old_value'];
					$new_val = $log['new_value'];
					if( $log['field_name'] == 'service_domain_id' ) {
						if(isset($service_domain_lkp[$old_val])) {
							$old_val = $service_domain_lkp[$old_val];
						}
						if(isset($service_domain_lkp[$new_val])) {
							$new_val = $service_domain_lkp[$new_val];
						}
					}
					if( $log['field_name'] == 'service_id' ) {
						if(isset($service_lkp[$old_val])) {
							$old_val = $service_lkp[$old_val];
						}
						if(isset($service_lkp[$new_val])) {
							$new_val = $service_lkp[$new_val];
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
					if( $log['field_name'] == 'priority_id' ) {
						if(isset($priority_lkp[$old_val])) {
							$old_val = $priority_lkp[$old_val];
						}
						if(isset($priority_lkp[$new_val])) {
							$new_val = $priority_lkp[$new_val];
						}
					}


					if( $log['field_name'] == 'executor_id' ) {
						if(isset($user_lkp[$old_val])) {
							$old_val = $user_lkp[$old_val];
						}
						if(isset($user_lkp[$new_val])) {
							$new_val = $user_lkp[$new_val];
						}
					}
					if( $log['field_name'] == 'executor_group_id' ) {
						if(isset($executor_group_lkp[$old_val])) {
							$old_val = $executor_group_lkp[$old_val];
						}
						if(isset($executor_group_lkp[$new_val])) {
							$new_val = $executor_group_lkp[$new_val];
						}
					}
					if( $log['field_name'] == 'sla_rule_id' ) {
						if(isset($sla_rule_lkp[$old_val])) {
							$old_val = $sla_rule_lkp[$old_val];
						}
						if(isset($sla_rule_lkp[$new_val])) {
							$new_val = $sla_rule_lkp[$new_val];
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
					<span>{{$service_request_info['creator']['name'] ?? ''}}</span>
					<!-- <span class="date">Today at 01:32:40 am</span> -->
                    <i class="fa fa-envelope-o"> </i> <a href="mailto:{{ $service_request_info['creator']['email'] }} }}?subject=&body="> {{ $service_request_info['creator']['email'] }} </a>
                    @if($service_request_info['creator']['phone'])
                        <i class="fa fa-phone"> </i> <a> {{ $service_request_info['creator']['phone'] }} </a>
                    @endif
					<span class="date">{{ date('Y-M-d H:i:s', strtotime($service_request_info['created_at']))}}</span>
				</a>
			</div>
			Created
		</div>
	</div>
</div>