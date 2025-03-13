<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceRequestAttachment extends Model
{
    protected $table = 'service_request_attachements';

    protected $fillable = ['name', 'url', 'service_request_id', 'field_id', 'created_by', 'created_at', 'field_id' ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
