@extends('layouts.app')

@section('content')
<style type="text/css">
    th, td {
        padding: 3px 8px !important;
    }
</style>
<div class="ibox pt-2">
    <div class="ibox-title">
        <h5>Group Details</h5>
        <div class="ibox-tools">
            @if(session('user_routes')['groups.index'] ?? false)
                <a href="{{ route('groups.index') }}" class="btn btn-primary btn-xs">Manage Groups</a>
            @endif
            @if(session('user_routes')['groups.edit'] ?? false)
                <a href="{{ route('groups.edit', $group->id) }}" class="btn btn-primary btn-xs">Edit Group</a>
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
                    <div class="panel-heading"> User Info </div>
                    <div class="panel-body" style="max-height: 750px; overflow: auto;">
                        <table class="table table-bordered table-striped">
                            <tr>
                                <th>Name</th>
                                <td>{{ $group->name }}</td>
                            </tr>
                            <tr>
                                <th>Description</th>
                                <td>{{ $group->description }}</td>
                            </tr>
                            <tr>
                                <th>Parent</th>
                                <td>{{ $group->parent ? $group->parent->name : '' }}</td>
                            </tr>
                            <tr>
                                <th>Created By</th>
                                <td>
                                    <badge class="badge badge-info">{{ $group->creator->name }}</badge>
                                    <i class="fa fa-envelope-o"> </i>
                                    <a href="mailto:{{ $group->creator->email }}?subject=&body="> {{ $group->creator->email }} </a>
                                    @if($group->creator->phone)
                                        <i class="fa fa-phone"> </i> <a> {{ $group->creator->phone }} </a>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Created On</th>
                                <td>{{ $group->created_at }}</td>
                            </tr>
                            <tr>
                                <th>Updated By</th>
                                @if(isset($group->updater->name))
                                    <td><badge class="badge badge-info">{{ $group->updater->name }}</badge>
                                        <i class="fa fa-envelope-o"> </i> <a href="mailto:{{ $group->updater->email }}?subject=&body="> {{ $group->updater->email}} </a>
                                        @if($group->updater->phone)
                                            <i class="fa fa-phone"> </i> <a> {{ $group->updater->phone }} </a>
                                        @endif
                                    </td>
                                @else
                                <td>-</td>
                                @endif
                            </tr>
                            <tr>
                                <th>Updated On</th>
                                <td>{{ $group->updated_at ?? '' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="panel panel-info">
                    <div class="panel-heading"><strong> Group Members</strong> </div>
                    <div class="panel-body" style="max-height: 750px; overflow: auto;">                         
                        <table class="table table-striped table-bordered">
                            <tr>
                                <th> Name </th>
                                <th> Email </th>
                                <th> Phone Number </th>
                                <th> Role </th>
                            </tr>
                            @foreach($group->members as $row)
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
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
