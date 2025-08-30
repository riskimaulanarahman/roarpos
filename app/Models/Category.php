<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id', 'name', 'image', 'sync_status', 'last_synced', 
        'client_version', 'version_id'
    ];

    protected $casts = [
        'last_synced' => 'datetime',
        'version_id' => 'integer',
    ];

    /**
     * Get the user that owns this category
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the products for this category
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
