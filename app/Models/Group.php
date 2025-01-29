<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;
    
    protected $table = 'groups';
    
    protected $fillable = [
        'name',
        'parent_id',
        'created_by',
        'updated_by',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function parent()
    {
        return $this->belongsTo(Group::class, 'parent_id');
    }
    public function children()
    {
        return $this->hasMany(Group::class, 'parent_id');
    }
    public function members()
    {
        return $this->belongsToMany(User::class, 'user_groups', 'group_id', 'user_id');
    }
    public function memberCount()
    {
        return $this->members()->count();
    }
}