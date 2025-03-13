<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceDomain extends Model
{
    protected $fillable = [
        'name', 'description', 'color', 'enabled', 'created_by', 'updated_by', 'created_at', 'updated_at'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'service_domain_users', 'service_domain_id', 'user_id');
        // return $this->hasManyThrough(User::class, service_domainUser::class, 'service_domain_id', 'id', 'id', 'user_id');
    }
    
    public function serviceRequests()
    {
        return $this->hasMany(ServiceRequest::class, 'service_domain_id');
    }

    public function serviceRequestView()
    {
        return $this->hasMany(ServiceRequestView::class, 'service_domain_id');
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'service_domain_groups', 'service_domain_id', 'group_id');
        // return $this->hasManyThrough(Group::class, service_domainGroup::class, 'service_domain_id', 'id', 'id', 'group_id');
    }
    public function groupsEnabled()
    {
        return $this->belongsToMany(Group::class, 'service_domain_groups', 'service_domain_id', 'group_id')->where('enabled', true);
    }
    public function services()
    {
        return $this->belongsToMany(Service::class, 'service_domain_services', 'service_domain_id', 'service_id');
    }
    public function servicesEnabled()
    {
        return $this->belongsToMany(Service::class, 'service_domain_services', 'service_domain_id', 'service_id')->where('enabled', true);
    }

}
