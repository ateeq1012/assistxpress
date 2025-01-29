@extends('layouts.app')

@section('content')
<div class="wrapper wrapper-content">
    <div class="middle-box text-center animated fadeInRightBig" style="margin-top: 300px; min-width: 900px;">
        @if (session('error'))
            <div class="alert alert-danger alert-dismissable">
                <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
                {{ session('error') }}
            </div>
        @endif
        <h3>Welcome to {{env('APP_NAME')}}</h3>
        <div class="row">
            <div class="widget style1 lazur-bg" style="width: 150px;">
                <div class="row">
                    <div class="col-4">
                        <i class="fa fa-ticket fa-5x"></i>
                    </div>
                    <div class="col-8 text-right">
                        <span> Go To Dashboard </span>
                        <h2 class="font-bold">260</h2>
                    </div>
                </div>
            </div>
            <div class="ml-4 widget style1 lazur-bg" style="width: 150px;">
                <div class="row">
                    <div class="col-4">
                        <i class="fa fa-ticket fa-5x"></i>
                    </div>
                    <div class="col-8 text-right">
                        <span> Create A New Ticket </span>
                        <h2 class="font-bold">260</h2>
                    </div>
                </div>
            </div>
            <div class="ml-4 widget style1 lazur-bg" style="width: 150px;">
                <div class="row">
                    <div class="col-4">
                        <i class="fa fa-ticket fa-5x"></i>
                    </div>
                    <div class="col-8 text-right">
                        <span> My Ticket </span>
                        <h2 class="font-bold">260</h2>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer')
    @include('layouts.footer')
@endsection