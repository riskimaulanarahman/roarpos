<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{

    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'price',
        'stock',
        'category_id',
        'image',
        'is_best_seller',
        'sync_status',
        'last_synced',
        'client_version',
        'version_id'
    ];

    protected $casts = [
        'price' => 'integer',
        'stock' => 'integer',
        'category_id' => 'integer',
        'last_synced' => 'datetime',
        'version_id' => 'integer',
    ];

    /**
     * Get the user that owns this product
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category for this product
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the order items for this product
     */
    public function orderItems() 
    {
        return $this->hasMany(\App\Models\OrderItem::class, 'product_id');
    }
}
