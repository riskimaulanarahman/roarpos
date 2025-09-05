<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RawMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku','name','unit','unit_cost','stock_qty','min_stock'
    ];

    protected $casts = [
        'unit_cost' => 'decimal:4',
        'stock_qty' => 'decimal:4',
        'min_stock' => 'decimal:4',
    ];

    public function movements()
    {
        return $this->hasMany(RawMaterialMovement::class);
    }
}

