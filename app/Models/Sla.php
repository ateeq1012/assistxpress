<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sla extends Model
{
    protected $table = 'sla_rules';

    protected $fillable = [
        'name', 'description', 'color', 'order', 'last_run_ts', 'settings', 'qb_rules', 'created_by', 'created_at', 'created_by', 'updated_at'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
