<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Scripts -->
        <script src="{{ asset('js/jquery-3.6.0.min.js') }}"></script>
        <script src="{{ asset('js/popper.min.js') }}"></script>
        <script src="{{ asset('js/bootstrap.js') }}"></script>
        <script src="{{ asset('js/plugins/metisMenu/jquery.metisMenu.js') }}"></script>
        <script src="{{ asset('js/plugins/slimscroll/jquery.slimscroll.min.js') }}"></script>
        <script src="{{ asset('js/inspinia.js') }}"></script>
        <script src="{{ asset('js/plugins/pace/pace.min.js') }}"></script>
        <script src="{{ asset('js/plugins/flot/jquery.flot.js') }}"></script>
        <script src="{{ asset('js/plugins/flot/jquery.flot.tooltip.min.js') }}"></script>
        <script src="{{ asset('js/plugins/flot/jquery.flot.resize.js') }}"></script>

        <!-- Styles -->
        <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
        <link href="{{ asset('css/style.css') }}" rel="stylesheet">
        <link href="{{ asset('font-awesome/css/font-awesome.css') }}" rel="stylesheet">

        <!-- Fonts -->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito">
    </head>

    <body class="gray-bg">
        <div class="middle-box text-center loginscreen animated fadeInDown" style="min-width: 500px; margin-top: 200px;">
            @if (session('info'))
                <div class="alert alert-info alert-dismissable">
                    <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
                    {{ session('info') }}
                </div>
            @endif
            @if (session('success'))
                <div class="alert alert-success alert-dismissable">
                    <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissable">
                    <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
                    {{ session('error') }}
                </div>
            @endif
            <div>
                <br/>
                <h3>Login Super Admin</h3>
                <form method="POST" action="{{ route('login.submit') }}">
                    @csrf <!-- CSRF protection -->
                    <div class="form-group">
                        <input type="text" name="email" class="form-control" placeholder="Username / Email" required="">
                    </div>
                    <div class="form-group">
                        <input type="password" name="password" class="form-control" placeholder="Password" required="">
                        @error('email')
                            <span class="text-danger" role="alert">
                                <p>{{ $message }}</p>
                            </span>
                        @enderror
                    </div>
                    @if (session('message'))
                        <p class="text-danger mt-1">
                            {{ session('message') }}
                        </p>
                    @endif
                    <button type="submit" class="btn btn-primary block full-width m-b">Login</button>
                    {{-- <a href="{{ route('password.request') }}"><small>Forgot password?</small></a> --}}
                    {{-- <p class="text-muted text-center"><small>Do not have an account?</small></p>  --}}
                    {{-- <a class="btn btn-sm btn-white btn-block" href="{{ route('register') }}">Create an account</a> --}}
                </form>
            </div>
        </div>
    </body>
</html>
