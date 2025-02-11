<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskAuditLog extends Model
{
    protected $fillable = [
        'task_id', 'field_name', 'old_value', 'field_type', 'file_path', 'new_value', 'created_by'
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
