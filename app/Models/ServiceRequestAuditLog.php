<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceRequestAuditLog extends Model
{
    protected $fillable = [
        'service_request_id', 'field_name', 'old_value', 'field_type', 'file_path', 'new_value', 'created_by'
    ];

    public function service_request()
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
