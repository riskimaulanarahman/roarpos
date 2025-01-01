<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'payment_amount',
        'sub_total',
        'tax',
        'discount',
        'discount_amount',
        'service_charge',
        'total_price',
        'payment_method',
        'total_item',
        'kasir_id',
        'cashier_name',
        'transaction_time'
    ];

    public function kasir()
    {
        return $this->belongsTo(User::class, 'kasir_id', 'id');
    }

    //
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'id');
    }
}
