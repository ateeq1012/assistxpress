<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskAuditLog extends Model
{
    protected $fillable = [
        'task_id', 'project_id', 'task_type_id', 'field_name', 'old_value', 'new_value', 'created_by'
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
