<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'project_id', 'task_type_id', 'subject', 'description', 'status_id', 'priority_id', 'creator_group_id', 'created_by', 'updated_by', 'executor_id', 'executor_group_id',
        'sla_rule_id', 'time_spent', 'planned_start', 'planned_end', 'actual_execution_start', 'actual_execution_end'
    ];

    protected $task_fields = [
        'project_id' => 'Project',
        'task_type_id' => 'Task Type',
        'subject' => 'Subject',
        'description' => 'Description',
        'status_id' => 'Status',
        'priority_id' => 'Priority',
        // 'planned_start' => 'Planned Start',
        // 'planned_end' => 'Planned End',
        // 'actual_execution_start' => 'Actual Exec. Start',
        // 'actual_execution_end' => 'Actual Exec. End',
    ];
    protected $all_task_fields = [
        'id' => 'ID',
        'project_id' => 'Project',
        'task_type_id' => 'Task Type',
        'subject' => 'Subject',
        'description' => 'Description',
        'status_id' => 'Status',
        'priority_id' => 'Priority',
        // 'planned_start' => 'Planned Start',
        // 'planned_end' => 'Planned End',
        // 'actual_execution_start' => 'Actual Exec. Start',
        // 'actual_execution_end' => 'Actual Exec. End',
        'creator_group_id' => 'Creator Group',
        'created_by' => 'Creator',
        'updated_by' => 'Updater',
        'executor_id' => 'Assignee',
        'executor_group_id' => 'Assignee Group',
        'sla_rule_id' => 'SLA Rule',
        'created_at' => 'Created at',
        'updated_at' => 'Updated at',
    ];

    public function getTaskFields()
    {
        return $this->task_fields;
    }
    public function getAllTaskFields()
    {
        return $this->all_task_fields;
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function taskType()
    {
        return $this->belongsTo(TaskType::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function priority()
    {
        return $this->belongsTo(TaskPriority::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function executor()
    {
        return $this->belongsTo(User::class, 'executor_id');
    }

    public function creatorGroup()
    {
        return $this->belongsTo(Group::class, 'creator_group_id');
    }
    public function executorGroup()
    {
        return $this->belongsTo(Group::class, 'executor_group_id');
    }
    public function sla()
    {
        return $this->belongsTo(Sla::class, 'sla_rule_id');
    }
    public function taskAttachments()
    {
        return $this->hasMany(TaskAtachment::class, 'task_id');
    }
    public function taskAuditLogs()
    {
        return $this->hasMany(TaskAuditLog::class, 'task_id');
    }
    public function taskComment()
    {
        return $this->hasMany(TaskAuditLog::class, 'task_id');
    }
    public function taskCustomField()
    {
        return $this->hasMany(TaskCustomField::class, 'task_id');
    }
}
