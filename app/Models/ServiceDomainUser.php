<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceDomainUser extends Model
{
    protected $fillable = [
        'user_id', 'service_domain_id', 'created_by'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
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
