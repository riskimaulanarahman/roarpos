<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'transaction_number',
        'cashier_id',
        'total_price',
        'total_item',
        'payment_method',
        'status'
        // 'payment_amount',
        // 'sub_total',
        // 'tax',
        // 'discount',
        // 'discount_amount',
        // 'service_charge',
        // 'cashier_name',
        // 'transaction_time'
    ];

    // public function kasir()
    // {
    //     return $this->belongsTo(User::class, 'kasir_id', 'id');
    // }

    //
    // public function orderItems()
    // {
    //     return $this->hasMany(OrderItem::class, 'order_id', 'id');
    // }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'cahier_id');
    }
}
