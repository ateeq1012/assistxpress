<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceRequestComment extends Model
{
    protected $fillable = [
        'service_request_id', 'text', 'created_by'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function service_request()
    {
        return $this->belongsTo(ServiceRequest::class);
    }
}
