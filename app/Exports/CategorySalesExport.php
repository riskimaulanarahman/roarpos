<?php

namespace App\Exports;

use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CategorySalesExport implements FromQuery, WithMapping, WithHeadings
{
    use Exportable;

    protected $start;
    protected $end;
    protected $userId;
    protected $status;
    protected $paymentMethod;
    protected $categoryId;
    protected $productId;

    public function forRange(string $start, string $end)
    {
        $this->start = $start;
        $this->end = $end;
        return $this;
    }

    public function withUser(?int $userId)
    {
        $this->userId = $userId;
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

    public function query()
    {
        return OrderItem::select([
                'categories.name as category_name',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.total_price) as total_price'),
            ])
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->whereBetween(DB::raw('DATE(orders.created_at)'), [$this->start, $this->end])
            ->when($this->userId, fn($q) => $q->where('orders.user_id', $this->userId))
            ->when($this->status, fn($q) => $q->where('orders.status', $this->status))
            ->when($this->paymentMethod, fn($q) => $q->where('orders.payment_method', $this->paymentMethod))
            ->when($this->categoryId, fn($q) => $q->where('products.category_id', $this->categoryId))
            ->when($this->productId, fn($q) => $q->where('order_items.product_id', $this->productId))
            ->groupBy('categories.name')
            ->orderByDesc('total_price');
    }

    public function map($row): array
    {
        static $i = 1;
        return [
            $i++,
            $row->category_name,
            $row->total_quantity,
            $row->total_price,
        ];
    }

    public function headings(): array
    {
        return [
            'No',
            'Category',
            'Total Quantity',
            'Total Price',
        ];
    }
}
