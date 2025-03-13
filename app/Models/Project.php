<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'name', 'description', 'color', 'enabled', 'task_types', 'created_by', 'updated_by'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'project_users', 'project_id', 'user_id');
        // return $this->hasManyThrough(User::class, ProjectUser::class, 'project_id', 'id', 'id', 'user_id');
    }
    
    public function tasks()
    {
        return $this->hasMany(Task::class, 'project_id');
    }

    public function taskView()
    {
        return $this->hasMany(TaskView::class, 'project_id');
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'project_groups', 'project_id', 'group_id');
        // return $this->hasManyThrough(Group::class, ProjectGroup::class, 'project_id', 'id', 'id', 'group_id');
    }
    public function groupsEnabled()
    {
        return $this->belongsToMany(Group::class, 'project_groups', 'project_id', 'group_id')->where('enabled', true);
    }
    public function taskTypes()
    {
        return $this->belongsToMany(TaskType::class, 'project_task_types', 'project_id', 'task_type_id');
    }
    public function taskTypesEnabled()
    {
        return $this->belongsToMany(TaskType::class, 'project_task_types', 'project_id', 'task_type_id')->where('enabled', true);
    }

}
