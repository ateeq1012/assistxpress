<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowStatusTransition extends Model
{
    protected $fillable = [
        'workflow_id', 'status_from_id', 'status_to_id', 'role_id', 'group_id', 'user_id', 'is_allowed', 'created_by'
    ];

    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }

    public function statusFrom()
    {
        return $this->belongsTo(Status::class, 'status_from_id');
    }

    public function statusTo()
    {
        return $this->belongsTo(Status::class, 'status_to_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
