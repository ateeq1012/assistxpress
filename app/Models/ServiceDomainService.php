<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceDomainService extends Model
{
    protected $fillable = [
        'service_domain_id', 'service_id'
    ];

    public function service_domain()
    {
        return $this->belongsTo(ServiceDomain::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }
}
