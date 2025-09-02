<?php

namespace App\Http\Controllers;

use App\Services\SalesAnalyticsService;
use App\Support\ReportDateRange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GrafikSalesController extends Controller
{
    public function series(Request $request, SalesAnalyticsService $svc)
    {
        $userId = auth()->id();
        $resolved = ReportDateRange::fromRequest($request);

        $from = $resolved['from'] ?? now()->startOfMonth()->toDateString();
        $to   = $resolved['to']   ?? now()->endOfMonth()->toDateString();

        $period = $request->input('period', 'bulanan');
        $bucket = match ($period) {
            'harian' => 'day',
            'mingguan' => 'week',
            'tahunan' => 'year',
            default => 'month',
        };

        $segmentBy = $request->input('segment_by'); // payment_method|status|null

        $cacheKey = 'sales_series:' . md5(json_encode([$userId, $from, $to, $bucket, $segmentBy]));
        $series = Cache::remember($cacheKey, 300, function () use ($svc, $userId, $from, $to, $bucket, $segmentBy) {
            return $svc->timeseries($userId, $from, $to, $bucket, $segmentBy);
        });

        return response()->json($series);
    }

    public function seriesCsv(Request $request, SalesAnalyticsService $svc)
    {
        $userId = auth()->id();
        $resolved = ReportDateRange::fromRequest($request);
        $from = $resolved['from'] ?? now()->startOfMonth()->toDateString();
        $to   = $resolved['to']   ?? now()->endOfMonth()->toDateString();

        $period = $request->input('period', 'bulanan');
        $bucket = match ($period) {
            'harian' => 'day',
            'mingguan' => 'week',
            'tahunan' => 'year',
            default => 'month',
        };
        $segmentBy = $request->input('segment_by');

        $cacheKey = 'sales_series:' . md5(json_encode([$userId, $from, $to, $bucket, $segmentBy]));
        $series = Cache::remember($cacheKey, 300, function () use ($svc, $userId, $from, $to, $bucket, $segmentBy) {
            return $svc->timeseries($userId, $from, $to, $bucket, $segmentBy);
        });

        $labels = $series['labels'] ?? [];
        $datasets = $series['datasets'] ?? [];

        // Build wide CSV: Label + each dataset as a column
        $header = array_merge(['Label'], array_map(fn($ds) => $ds['label'], $datasets));
        $rows = [];
        $rowCount = count($labels);
        for ($i=0; $i<$rowCount; $i++) {
            $row = [$labels[$i]];
            foreach ($datasets as $ds) {
                $row[] = (string) (($ds['data'][$i] ?? 0));
            }
            $rows[] = $row;
        }

        $fh = fopen('php://temp', 'w+');
        fputcsv($fh, $header);
        foreach ($rows as $r) { fputcsv($fh, $r); }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="sales-series.csv"',
        ]);
    }
}
