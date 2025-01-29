<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectTaskType extends Model
{
    protected $fillable = [
        'project_id', 'task_type_id'
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function taskType()
    {
        return $this->belongsTo(TaskType::class, 'task_type_id');
    }
}
