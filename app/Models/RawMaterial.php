<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

use App\Models\Traits\Blameable;

class RawMaterial extends Model
{
    use HasFactory, Blameable;

    protected $fillable = [
        'sku','name','unit','unit_cost','stock_qty','min_stock','created_by','updated_by'
    ];

    protected $casts = [
        'unit_cost' => 'decimal:4',
        'stock_qty' => 'decimal:4',
        'min_stock' => 'decimal:4',
    ];

    protected static function booted(): void
    {
        static::saving(function (RawMaterial $material) {
            if (! $material->sku) {
                $material->sku = static::generateSku($material->name);
            }
        });

        static::creating(function (RawMaterial $material) {
            if (is_null($material->stock_qty)) {
                $material->stock_qty = 0;
            }
        });
    }

    public function movements()
    {
        return $this->hasMany(RawMaterialMovement::class);
    }

    public function expenseItems()
    {
        return $this->hasMany(ExpenseItem::class);
    }

    public function recipeItems()
    {
        return $this->hasMany(ProductRecipeItem::class);
    }

    public static function generateSku(?string $name): string
    {
        $base = Str::upper(Str::slug($name ?? 'RM', ''));
        if ($base === '') {
            $base = 'RM';
        }

        $base = substr($base, 0, 8) ?: 'RM';
        $candidate = $base;
        $suffix = 1;

        while (static::where('sku', $candidate)->exists()) {
            $candidate = sprintf('%s-%02d', $base, $suffix);
            $suffix++;
            if ($suffix > 99) {
                $candidate = $base . '-' . Str::upper(Str::random(4));
                break;
            }
        }

        return $candidate;
    }
}
