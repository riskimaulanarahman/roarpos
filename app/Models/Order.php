<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'user_id',
        'transaction_number',
        'cashier_id',
        'total_price',
        'total_item',
        'payment_method',
        'nominal_bayar',
        'status',
        'refund_method',
        'refund_nominal',
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
    //     return $this->belongsTo(User::class, 'cashier_id', 'id');
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
        return $this->belongsTo(User::class, 'cashier_id');
    }
}
