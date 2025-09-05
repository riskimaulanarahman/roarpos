<?php

namespace App\Exports;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OrdersExport implements FromQuery, WithMapping, WithHeadings
{
    use Exportable;

    protected $start;
    protected $end;
    protected $status;
    protected $paymentMethod;
    protected $categoryId;
    protected $productId;
    protected $userId;

    public function forRange(string $start, string $end)
    {
        $this->start = $start;
        $this->end = $end;
        return $this;
    }

    public function withFilters(?string $status, ?string $paymentMethod, ?string $categoryId, ?string $productId)
    {
        $this->status = $status;
        $this->paymentMethod = $paymentMethod;
        $this->categoryId = $categoryId;
        $this->productId = $productId;
        return $this;
    }

    public function withUser(?int $userId)
    {
        $this->userId = $userId;
        return $this;
    }

    public function query()
    {
        return Order::query()
            ->with('user')
            ->whereBetween('created_at', [$this->start, $this->end])
            ->when($this->userId, fn($q) => $q->where('user_id', $this->userId))
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->paymentMethod, fn($q) => $q->where('payment_method', $this->paymentMethod))
            ->when($this->categoryId, function ($q) {
                $categoryId = $this->categoryId;
                $q->whereExists(function ($sub) use ($categoryId) {
                    $sub->select(DB::raw(1))
                        ->from('order_items')
                        ->join('products', 'order_items.product_id', '=', 'products.id')
                        ->whereColumn('order_items.order_id', 'orders.id')
                        ->where('products.category_id', $categoryId);
                });
            })
            ->when($this->productId, function ($q) {
                $productId = $this->productId;
                $q->whereExists(function ($sub) use ($productId) {
                    $sub->select(DB::raw(1))
                        ->from('order_items')
                        ->whereColumn('order_items.order_id', 'orders.id')
                        ->where('order_items.product_id', $productId);
                });
            });
    }

    public function map($order): array
    {
        return [
            $order->transaction_time,
            $order->total_price,
            $order->total_item,
            optional($order->user)->name ?? ($order->cashier_name ?? '-')
        ];
    }

    public function headings(): array
    {
        return [
            'Transaction Time',
            'Total Price',
            'Total Item',
            'Kasir',
        ];
    }
}
