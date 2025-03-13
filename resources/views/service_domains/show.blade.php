@extends('layouts.app')

@section('content')
<style type="text/css">
    th, td {
        padding: 3px 8px !important;
    }
    .select2-container--default .select2-results__option[aria-selected=true] {
        display: none !important;
    }
    #color {
        border-radius: 3px;
    }
</style>
<link href="{{ asset('css/plugins/select2/select2.min.css') }}" rel="stylesheet">
<script src="{{ asset('js/plugins/select2/select2.full.min.js') }}"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<div class="ibox pt-2">
    <div class="ibox-title">
        <h5>Edit Service Domain</h5>
        <div class="ibox-tools">
            <a href="{{ route('service_domains.index') }}" class="btn btn-primary btn-xs"> Manage Service Domains </a>
            @if(session('user_routes')['service_domains.edit'] ?? false)
                <a href="{{ route('service_domains.edit', $service_domain->id) }}" class="btn btn-primary btn-xs">Edit Service Domain</a>
            @endif
        </div>
    </div>
    <div class="ibox-content">
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
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="row">
            <div class="col-6">
                <div class="panel panel-primary">
                    <div class="panel-heading">Service Domain Info</div>
                    <div class="panel-body" style="max-height: 750px; overflow: auto;">
                        <table class="table table-bordered table-striped">
                            <tr>
                                <th>Service Domain Name</th>
                                <td>{{ $service_domain->name }}</td>
                            </tr>
                            <tr>
                                <th>Description</th>
                                <td>{{ $service_domain->description }}</td>
                            </tr>
                            <tr>
                                <th>Created By</th>
                                <td>
                                    <badge class="badge badge-info">{{ $service_domain->creator->name }}</badge>
                                    <i class="fa fa-envelope-o"> </i>
                                    <a href="mailto:{{ $service_domain->creator->email }}?subject=&body="> {{ $service_domain->creator->email }} </a>
                                    @if($service_domain->creator->phone)
                                        <i class="fa fa-phone"> </i> <a> {{ $service_domain->creator->phone }} </a>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Created On</th>
                                <td>{{ $service_domain->created_at }}</td>
                            </tr>
                            <tr>
                                <th>Updated By</th>
                                @if(isset($service_domain->updater->name))
                                    <td><badge class="badge badge-info">{{ $service_domain->updater->name }}</badge>
                                        <i class="fa fa-envelope-o"> </i> <a href="mailto:{{ $service_domain->updater->email }}?subject=&body="> {{ $service_domain->updater->email}} </a>
                                        @if($service_domain->updater->phone)
                                            <i class="fa fa-phone"> </i> <a> {{ $service_domain->updater->phone }} </a>
                                        @endif
                                    </td>
                                @else
                                <td>-</td>
                                @endif
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="panel panel-info" id="group-members-box">
                    <div class="panel-heading"> Service Domain Groups </div>
                    <div class="panel-body" style="max-height: 750px; overflow: auto;">

                        @if(isset($service_domain->groups) && count($service_domain->groups) > 0)
                            @foreach($service_domain->groups as $gr)
                                <div class="d-inline-block mr-2">
                                    @if(session('user_routes')['groups.show'] ?? false)
                                        <a href="{{ route('groups.show', $gr->id) }}" target="_blank" class="btn btn-default">
                                            <i class="fa fa-group"></i>&nbsp;&nbsp;<strong> {{ $gr->name }}</strong> &nbsp; ({{ $gr->members_count }} users)&nbsp;&nbsp;
                                        </a>
                                    @else
                                        <span class="btn btn-default">
                                            <i class="fa fa-group"></i>&nbsp;&nbsp;<strong> {{ $gr->name }}</strong> &nbsp; ({{ $gr->members_count }} users)
                                        </span>
                                    @endif

                                </div>
                            @endforeach
                        @else
                            <div class="alert alert-info alert-dismissable">
                                No Groups Assigned to Service Domain.
                            </div>
                        @endif
                    </div>
                </div>
                <div class="panel panel-success" id="group-members-box">
                    <div class="panel-heading">
                        Service Domain Members
                    </div>
                    <div class="panel-body" style="max-height: 750px; overflow: auto;">
                        @if(isset($service_domain->members) && count($service_domain->members) > 0)
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th> Name </th>
                                        <th> Email </th>
                                        <th> Phone Number </th>
                                        <th> Role </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($service_domain->members as $row)
                                    <tr>
                                        <td> {{ $row->name }} </td>
                                        <td> <i class="fa fa-envelope-o"> </i> <a href="mailto:{{ $row->email }}?subject=&body="> {{ $row->email}} </a> </td>
                                        <td>
                                            @if($row->phone)
                                                <i class="fa fa-phone"> </i> <a> {{ $row->phone }} </a>
                                            @endif
                                        </td>
                                        <td> {{ $row->role->name ?? '' }} </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="alert alert-info alert-dismissable">
                                No Users Assigned to Service Domain.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection