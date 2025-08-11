<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{

    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'stock',
        'category_id',
        'image',
        'is_best_seller'
    ];

    protected $casts = [
        'price' => 'integer',
        'stock' => 'integer',
        'category_id' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, );
    }
}
