<?php
use Illuminate\Http\Request;
?>
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<title>{{ config('app.name', 'Laravel') }}</title>

	<!-- Styles -->
	<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
	<link href="{{ asset('css/style.css') }}" rel="stylesheet">
	<link href="{{ asset('font-awesome/css/font-awesome.css') }}" rel="stylesheet">

	<!-- Fonts -->
	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito">
	<link href="{{ asset('css/plugins/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet">
</head>
<!-- Scripts -->
<!-- <script src="{{ asset('js/jquery-3.6.0.min.js') }}"></script> -->
<script src="{{ asset('js/jquery-3.7.1.min.js') }}"></script>
<script src="{{ asset('js/plugins/jquery-ui/jquery-ui.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.js') }}"></script>
<script src="{{ asset('js/plugins/metisMenu/jquery.metisMenu.js') }}"></script>
<script src="{{ asset('js/plugins/slimscroll/jquery.slimscroll.min.js') }}"></script>
<script src="{{ asset('js/inspinia.js') }}"></script>
<script src="{{ asset('js/plugins/pace/pace.min.js') }}"></script>
<script src="{{ asset('js/plugins/flot/jquery.flot.js') }}"></script>
<script src="{{ asset('js/plugins/flot/jquery.flot.tooltip.min.js') }}"></script>
<script src="{{ asset('js/plugins/flot/jquery.flot.resize.js') }}"></script>
<script src="{{ asset('js/plugins/sweetalert2/sweetalert2.all.min.js') }}"></script>

<!-- Delete Confirmation Button on All Index Pagess -->
<script src="{{ asset('js/common-js/deleteConfirmation.js') }}"></script>

<body class="canvas-menu">
	<div id="wrapper">
		<nav class="navbar-default navbar-static-side" role="navigation">
			<div class="sidebar-collapse">
				<a class="close-canvas-menu"><i class="fa fa-times"></i></a>
				<ul class="nav metismenu" id="side-menu">
					<li class="nav-header">
						<div class="dropdown profile-element">
							<img alt="image" class="rounded-circle" src="/img/profile_small.jpg"/>
							<a data-toggle="dropdown" class="dropdown-toggle" href="#">
								<span class="block m-t-xs font-bold">{{ Auth::user()->name }}</span>
								<span class="text-muted text-xs block">{{ session('user_role_name') }} <b class="caret"></b></span>
							</a>
							<ul class="dropdown-menu animated fadeInRight m-t-xs">
								<li><a class="dropdown-item" href="profile.html">Profile</a></li>
								<li class="dropdown-divider"></li>
								<li><a class="dropdown-item" href="login">Logout</a></li>
							</ul>
						</div>
						<div class="logo-element">
							IN+
						</div>
					</li>

					@if(session('user_routes')['tasks.index'] ?? false)
						<li @if(request()->route()->getName() == 'tasks.index') class="active" @endif>
							<a href="{{ route('tasks.index') }}"><i class="fa fa-list"></i> Tasks</a>
						</li>
					@endif

					@if(session('user_routes')['projects.index'] ?? false)
						<li @if(request()->route()->getName() == 'projects.index') class="active" @endif>
							<a href="{{ route('projects.index') }}"><i class="fa fa-cubes"></i> Projects</a>
						</li>
					@endif
					
					@if(session('user_routes')['sla_rules.index'] ?? false)
						<li @if(request()->route()->getName() == 'sla_rules.index') class="active" @endif>
							<a href="{{ route('sla_rules.index') }}"><i class="fa fa-clock-o"></i> SLAs</a>
						</li>
					@endif

					<li @if(in_array(request()->route()->getName(), ['workflows.index', 'task_types.index', 'statuses.index', 'task_priorities.index'])) class="active" @endif>
						<a href=""><i class="fa fa-gears"></i> Workflow Engine</span> <span class="fa arrow"></a>
						<ul class="nav nav-second-level collapse">
							@if(session('user_routes')['workflows.index'] ?? false)
								<li @if(request()->route()->getName() == 'workflows.index') class="active" @endif>
									<a href="{{ route('workflows.index') }}">Status Transitions</a>
								</li>
							@endif
							@if(session('user_routes')['task_types.index'] ?? false)
								<li @if(request()->route()->getName() == 'task_types.index') class="active" @endif>
									<a href="{{ route('task_types.index') }}">Task Types</a>
								</li>
							@endif

							@if(session('user_routes')['custom_fields.index'] ?? false)
								<li @if(request()->route()->getName() == 'custom_fields.index') class="active" @endif>
									<a href="{{ route('custom_fields.index') }}">Custom Fields</a>
								</li>
							@endif

							@if(session('user_routes')['statuses.index'] ?? false)
								<li @if(request()->route()->getName() == 'statuses.index') class="active" @endif>
									<a href="{{ route('statuses.index') }}">Statuses</a>
								</li>
							@endif

							@if(session('user_routes')['task_priorities.index'] ?? false)
								<li @if(request()->route()->getName() == 'task_priorities.index') class="active" @endif>
									<a href="{{ route('task_priorities.index') }}">Task Priorities</a>
								</li>
							@endif
						</ul>
					</li>
					<li @if(in_array(request()->route()->getName(), ['roles.index', 'users.index', 'groups.index'])) class="active" @endif>
						<a href=""><i class="fa fa-gear"></i> User Management</span> <span class="fa arrow"></a>
						<ul class="nav nav-second-level collapse">
							
							@if(session('user_routes')['roles.index'] ?? false)
								<li @if(request()->route()->getName() == 'roles.index') class="active" @endif>
									<a href="{{ route('roles.index') }}"><i class="fa fa-address-card-o"></i> Roles</a>
								</li>
							@endif
							
							@if(session('user_routes')['users.index'] ?? false)
								<li @if(request()->route()->getName() == 'users.index') class="active" @endif>
									<a href="{{ route('users.index') }}"><i class="fa fa-address-book-o"></i> Users</a>
								</li>
							@endif

							@if(session('user_routes')['groups.index'] ?? false)
								<li @if(request()->route()->getName() == 'groups.index') class="active" @endif>
									<a href="{{ route('groups.index') }}"><i class="fa fa-users"></i> Groups</a>
								</li>
							@endif
						</ul>
					</li>
				</ul>
			</div>
		</nav>

		<div id="page-wrapper" class="gray-bg">
			<div class="row border-bottom">
			<nav class="navbar navbar-static-top  " role="navigation" style="margin-bottom: 0; background: #2f4050;">
				<div class="navbar-header">
					<a class="navbar-minimalize minimalize-styl-2 btn btn-primary " href="#"><i class="fa fa-bars pt-1 pb-1"></i> </a>
					<img alt="image" src="/img/logo/inx-logo-grey.png" style="height: 37px; margin: 0px 20px 3px 5px;">
					<img alt="image" src="/img/logo/an10_logo_mini_grey.png" style="height: 55px; margin: 2px 20px 0px 5px">
				</div>
				<ul class="nav navbar-top-links navbar-right">
					<li>
						<a class="pt-2 pb-2">
							<form action="{{ route('logout') }}" method="POST" style="display: inline;">
								@csrf
								<button type="submit" class="btn btn-link">
									<i class="fa fa-sign-out"></i> Log out
								</button>
							</form>
						</a>
					</li>
				</ul>

			</nav>
			</div>
			@yield('content-heading')
			@yield('content')
			@yield('footer')

		</div>
	</div>
</body>

</html>
<script>
	$('body.canvas-menu .sidebar-collapse').slimScroll({
		height: '100%',
		railOpacity: 0.9
	});

	(function() {
		// Store the original fetch method
		const originalFetch = window.fetch;

		// Override the global fetch function
		window.fetch = async function (url, options = {}) {
			try {
				const response = await originalFetch(url, options);

				// Check for 413 status code globally
				if (response.status === 413) {
					// If file is too large, show a SweetAlert error
					Swal.fire({
						title: 'File Too Large',
						text: 'The file you are trying to upload exceeds the maximum size limit. Please try uploading a smaller file.',
						icon: 'error',
					});
					return; // Don't process further
				}

				return response;
			} catch (error) {
				console.error('Fetch error: ', error);
				// Optional: You can add additional error handling here
			}
		};
	})();

</script>