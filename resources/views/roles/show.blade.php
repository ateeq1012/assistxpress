@extends('layouts.app')

@section('content')
<style type="text/css">
    th, td {
        padding: 3px 8px !important;
    }
</style>
<div class="ibox pt-2">
    <div class="ibox-title">
        <h5>Role Details</h5>
        <div class="ibox-tools">
            @if(session('user_routes')['roles.index'] ?? false)
                <a href="{{ route('roles.index') }}" class="btn btn-primary btn-xs">Manage Roles</a>
            @endif
            @if(session('user_routes')['roles.edit'] ?? false)
                <a href="{{ route('roles.edit', $role->id) }}" class="btn btn-primary btn-xs">Edit Role</a>
            @endif
        </div>
    </div>
    <div class="ibox-content">
        @if (session('error'))
            <div class="alert alert-danger alert-dismissable">
                <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
                {{ session('error') }}
            </div>
        @endif
        <div class="row">
            <div class="col-6">
                <div class="panel panel-primary">
                    <div class="panel-heading"> Role Info </div>
                    <div class="panel-body" style="max-height: 400px; overflow: auto;">
                        <table class="table table-bordered table-striped">
                            <tr>
                                <th>Name</th>
                                <td>{{ $role->name }}</td>
                            </tr>
                            <tr>
                                <th>Description</th>
                                <td>{{ $role->description }}</td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>{{ $role->enabled ? 'Enabled' : 'Disabled' }}</td>
                            </tr>
                            <tr>
                                <th>Created By</th>
                                <td>
                                    <badge class="badge badge-info">{{ $role->creator->name }}</badge>
                                    <i class="fa fa-envelope-o"> </i>
                                    <a href="mailto:{{ $role->creator->email }}?subject=&body="> {{ $role->creator->email }} </a>
                                    @if($role->creator->phone)
                                        <i class="fa fa-phone"> </i> <a> {{ $role->creator->phone }} </a>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Created On</th>
                                <td>{{ $role->created_at }}</td>
                            </tr>
                            <tr>
                                <th>Updated By</th>
                                @if(isset($role->updater->name))
                                    <td><badge class="badge badge-info">{{ $role->updater->name }}</badge>
                                        <i class="fa fa-envelope-o"> </i> <a href="mailto:{{ $role->updater->email }}?subject=&body="> {{ $role->updater->email}} </a>
                                        @if($role->updater->phone)
                                            <i class="fa fa-phone"> </i> <a> {{ $role->updater->phone }} </a>
                                        @endif
                                    </td>
                                @else
                                <td>-</td>
                                @endif
                            </tr>
                            <tr>
                                <th>Updated On</th>
                                <td>{{ $role->updated_at ?? '' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="panel panel-primary">
                    <div class="panel-heading"> Users assigned to this Role </div>
                    <div class="panel-body" style="max-height: 400px; overflow: auto;">
                        @if(isset($role->users) && count($role->users) > 0)
                            <table class="table table-striped table-bordered">
                                <tr>
                                    <th> Name </th>
                                    <th> Email </th>
                                    <th> Phone Number </th>
                                    <th> Role </th>
                                </tr>
                                @foreach($role->users as $row)
                                <tr>
                                    <td> {{ $row->name }} </td>
                                    <td> {{ $row->email }} </td>
                                    <td> {{ $row->phone }} </td>
                                    <td> {{ $row->role->name ?? '' }} </td>
                                </tr>
                                @endforeach
                            </table>
                        @else
                            <div class="alert alert-info alert-dismissable">
                                <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
                                No Users Assigned to this Role.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="panel panel-primary">
                    <div class="panel-heading"> Role Access </div>
                    <div class="panel-body" style="max-height: 750px; overflow: auto;">                         
                        <div class="panel-group" id="accordion">
                            @foreach($route_cfg_resp as $entity => $routes)
                                <div class="panel panel-default">
                                    <div class="panel-heading p-1 pl-2" data-toggle="collapse" data-target="#collapse-{{ Str::slug($entity) }}">
                                        <h4 class="panel-title m-1">
                                            {{ $entity }} <span class="caret"></span>
                                        </h4>
                                    </div>
                                    <div id="collapse-{{ Str::slug($entity) }}" class="panel-collapse in">
                                        <div class="panel-body p-0">
                                            <table class="table table-striped table-bordered mb-0">
                                                @foreach($routes as $row)
                                                    @if($row['selected'])
                                                    <tr>
                                                        <td > {{ $row['description'] }}</td>
                                                        <td style="width:100px;"> <span class="label label-primary">Allowed</span> </td>
                                                    </tr>
                                                    @endif
                                                @endforeach
                                                @foreach($routes as $row)
                                                    @if(!$row['selected'])
                                                        <tr>
                                                            <td class="text-muted"> {{ $row['description'] }} </td>
                                                            <td style="width:100px;"> <span class="label label-warning">Not Allowed</span> </td>
                                                        </tr>
                                                    @endif
                                                @endforeach
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
