<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SalesAnalyticsService
{
    /**
     * Build revenue timeseries.
     * @param int $userId
     * @param string $from Y-m-d
     * @param string $to Y-m-d
     * @param string $bucket day|week|month|year
     * @param string|null $segmentBy payment_method|status|null
     * @return array{labels: array<int,string>, datasets: array<int, array{label: string, data: array<int,int>}>}
     */
    public function timeseries(int $userId, string $from, string $to, string $bucket = 'day', ?string $segmentBy = null): array
    {
        [$selectExpr, $groupExpr, $labelFormatter] = $this->bucketExpr($bucket);

        $base = DB::table('orders')
            ->selectRaw("$selectExpr as bucket")
            ->selectRaw($segmentBy ? "$segmentBy as segment" : "'All' as segment")
            ->selectRaw('SUM(total_price) as revenue')
            ->where('user_id', $userId)
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->groupByRaw($segmentBy ? "$groupExpr, $segmentBy" : $groupExpr)
            ->orderBy('bucket');

        $rows = $base->get();

        // Unique raw buckets sorted
        $rawBuckets = $rows->pluck('bucket')->unique()->sort()->values()->all();
        $labels = array_map($labelFormatter, $rawBuckets);

        $segments = $rows->pluck('segment')->unique()->values()->all();
        if (empty($segments)) $segments = ['All'];

        // Zero-fill
        $datasetMap = [];
        foreach ($segments as $seg) {
            $datasetMap[$seg] = array_fill(0, count($labels), 0);
        }

        // Map raw bucket -> index
        $bucketIndex = array_flip($rawBuckets);

        foreach ($rows as $r) {
            $idx = $bucketIndex[$r->bucket] ?? null;
            if ($idx !== null) {
                $seg = $r->segment ?? 'All';
                $datasetMap[$seg][$idx] = (int) $r->revenue;
            }
        }

        $datasets = [];
        foreach ($datasetMap as $seg => $data) {
            $datasets[] = [
                'label' => $seg,
                'data' => $data,
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
        ];
    }

    /**
     * Return [selectExpr, groupExpr, labelFormatter]
     */
    private function bucketExpr(string $bucket): array
    {
        switch ($bucket) {
            case 'week':
                return [
                    'YEARWEEK(created_at, 3)',
                    'YEARWEEK(created_at, 3)',
                    fn($b) => $this->formatYearWeek((string) $b),
                ];
            case 'month':
                return [
                    "DATE_FORMAT(created_at, '%Y-%m')",
                    "DATE_FORMAT(created_at, '%Y-%m')",
                    fn($b) => (string) $b,
                ];
            case 'year':
                return [
                    'YEAR(created_at)',
                    'YEAR(created_at)',
                    fn($b) => (string) $b,
                ];
            case 'day':
            default:
                return [
                    'DATE(created_at)',
                    'DATE(created_at)',
                    fn($b) => Carbon::parse($b)->format('Y-m-d'),
                ];
        }
    }

    private function formatYearWeek(string $yearWeek): string
    {
        // e.g., 202536 -> 2025 W36
        $year = substr($yearWeek, 0, 4);
        $week = substr($yearWeek, -2);
        return $year . ' W' . $week;
    }
}
