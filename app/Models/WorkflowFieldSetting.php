<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowFieldSetting extends Model
{
    protected $fillable = [
        'workflow_id', 'status_id', 'settings', 'created_by', 'updated_by'
    ];

    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
