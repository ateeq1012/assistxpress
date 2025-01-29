<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskAttachment extends Model
{
    protected $table = 'task_attachements';

    protected $fillable = ['name', 'url', 'task_id', 'field_id', 'created_by', 'created_at', 'field_id' ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
