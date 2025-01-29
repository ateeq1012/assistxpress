<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskCustomField extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'task_id', 'field_id', 'value', 'jsonval'
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function customField()
    {
        return $this->belongsTo(CustomField::class);
    }
}
