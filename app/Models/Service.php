<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'name', 'description', 'settings', 'approval_settings', /*'is_planned',*/ 'color', 'enabled', 'created_by', 'updated_by'
    ];

    public function service_requests()
    {
        return $this->hasMany(ServiceRequest::class, 'service_id');
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function workflow()
    {
        return $this->belongsTo(Workflow::class, 'workflow_id');
    }

    public function fields()
    {
        return $this->hasMany(ServiceField::class);
    }
}
