<?php

namespace App\Exports;

use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class ProductSalesExport implements FromQuery, WithMapping, WithHeadings{
    use Exportable;
    protected $start;
    protected $end;

    public function forRange(String $start, String $end)
    {
        $this->start = $start;
        $this->end = $end;
        return $this;
    }

    public function query()
    {
        $query = OrderItem::select(
            'products.name as product_name',
            DB::raw('SUM(order_items.quantity) as total_quantity'),
            DB::raw('SUM(order_items.total_price) as total_price')
        )
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereBetween(DB::raw('DATE(order_items.created_at)'), [$this->start, $this->end])
            ->groupBy('products.name')
            ->orderBy('total_quantity', 'desc');
        return $query;
    }

    public function map($productSale): array
    {
        static $i = 1;
        return [
            $i++,
            $productSale->product_name,
            $productSale->total_quantity,
            $productSale->total_price

        ];
    }

    public function headings(): array
    {
        return [
            'No',
            'Product Name',
            'Total Quantity',
            'Total Price',
        ];
    }
    public function getCsvSettings(): array
    {
        return [
            'delimiter' => ',',
            'enclosure' => '',
        ];
    }
}
