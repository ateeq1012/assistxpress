<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserRoleRoute extends Model
{
    use HasFactory;

    // Define the table name if it's not the plural of the model name
    protected $table = 'user_role_routes';

    // If you are not using auto-incrementing primary key or want to specify it
    protected $primaryKey = 'id'; // Assuming the table has an 'id' primary key

    // If the primary key is not an integer
    public $incrementing = true; // Set to false if the primary key is not auto-incrementing

    // If timestamps are not being used (created_at, updated_at)
    public $timestamps = false;

    // Define the fillable attributes (columns that can be mass assigned)
    protected $fillable = [
        'user_id',
        'role_id',
        'route_id',
        'created_by',
        'updated_by'
    ];

    // Optionally, define the relationships if you have foreign key references
    // Assuming user_id, role_id, and route_id reference other tables
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function route()
    {
        return $this->belongsTo(Route::class, 'route_id'); // Assuming you have a Route model
    }
}
