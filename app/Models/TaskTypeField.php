<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskTypeField extends Model
{
    protected $fillable = [
        'name', 'description', 'task_type_id', 'created_by', 'updated_by'
    ];

    public function taskType()
    {
        return $this->belongsTo(TaskType::class);
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
