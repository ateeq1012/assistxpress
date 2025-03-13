<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceRequestCustomField extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'service_request_id', 'field_id', 'value', 'jsonval'
    ];

    public function service_request()
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function customField()
    {
        return $this->belongsTo(CustomField::class);
    }
}
