<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceDomainGroup extends Model
{
    protected $fillable = [
        'group_id', 'service_domain_id', 'created_by'
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function serviceDomain()
    {
        return $this->belongsTo(ServiceDomain::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
