<style type="text/css">
    .comment-box {
        white-space: pre-wrap;
        word-wrap: break-word;
    }
</style>
@if (count($service_request_comments) > 0)
	<div class="inspinia-timeline">
		@foreach ($service_request_comments as $comment)
		    <div class="timeline-item">
		        <div class="row">
		            <div class="col-1 date">
		                <i class="fa fa-comment"></i>
		                <!-- 7:00 am -->
		                <!-- <br> -->
		                <!-- <small class="text-navy">3 hour ago</small> -->
		            </div>
		            <div class="col content">
		                <p class="m-b-xs">
		                	<strong>{{$comment['creator']['name'] ?? ''}}</strong>
							<i class="fa fa-envelope-o"> </i> <a href="mailto:{{ $comment['creator']['email'] }} }}?subject=&body="> {{ $comment['creator']['email'] }} </a>
	                        @if($comment['creator']['phone'])
	                            <i class="fa fa-phone"> </i> <a> {{ $comment['creator']['phone'] }} </a>
	                        @endif
		                </p>
		                <p><span class="date">{{ date('Y-M-d H:i:s', strtotime($comment['created_at']))}}</span></p>
		                <p class="comment-box">{{ $comment['text'] }}</p>
		            </div>
		        </div>
		    </div>
		@endforeach
	</div>
@endif


