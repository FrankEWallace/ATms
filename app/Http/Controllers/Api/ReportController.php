<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\ProductionLog;
use App\Models\ShiftRecord;
use App\Models\Transaction;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    use ApiResponse;

    public function summary(Request $request): JsonResponse
    {
        try {
            $siteId = $request->query('site_id') ?? $request->header('X-Site-Id');
            $from   = $request->query('from', now()->startOfMonth()->toDateString());
            $to     = $request->query('to', now()->endOfMonth()->toDateString());

            if ($siteId) {
                $this->authorizeForSite($siteId);
                $siteScope = fn($q) => $q->where('site_id', $siteId);
            } else {
                $siteIds   = $this->getUserSiteIds();
                $siteScope = fn($q) => $q->whereIn('site_id', $siteIds);
            }

            $query = Transaction::query()
                ->whereBetween('transaction_date', [$from, $to])
                ->where('status', '!=', 'cancelled');
            $siteScope($query);

            $transactions = $query->get();

            $income  = $transactions->where('type', 'income')->sum(fn($t) => $t->quantity * $t->unit_price);
            $expense = $transactions->where('type', 'expense')->sum(fn($t) => $t->quantity * $t->unit_price);
            $refund  = $transactions->where('type', 'refund')->sum(fn($t) => $t->quantity * $t->unit_price);

            // Shift count
            $shiftQuery = ShiftRecord::whereBetween('shift_date', [$from, $to]);
            $siteScope($shiftQuery);
            $shiftCount  = $shiftQuery->count();
            $totalHours  = $shiftQuery->sum('hours_worked');

            // Production summary
            $prodQuery = ProductionLog::whereBetween('log_date', [$from, $to]);
            $siteScope($prodQuery);
            $totalOre   = $prodQuery->sum('ore_tonnes');
            $totalWaste = $prodQuery->sum('waste_tonnes');

            return $this->success([
                'period'       => ['from' => $from, 'to' => $to],
                'income'       => round($income, 2),
                'expense'      => round($expense, 2),
                'refund'       => round($refund, 2),
                'net'          => round($income - $expense + $refund, 2),
                'shift_count'  => $shiftCount,
                'total_hours'  => round($totalHours, 2),
                'ore_tonnes'   => round($totalOre, 4),
                'waste_tonnes' => round($totalWaste, 4),
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to generate summary: ' . $e->getMessage(), 500);
        }
    }

    public function monthlyTrend(Request $request): JsonResponse
    {
        try {
            $siteId = $request->query('site_id') ?? $request->header('X-Site-Id');
            $months = (int) ($request->query('months', 12));
            $months = min($months, 24);

            $from = now()->subMonths($months - 1)->startOfMonth()->toDateString();
            $to   = now()->endOfMonth()->toDateString();

            $query = Transaction::select(
                DB::raw("DATE_FORMAT(transaction_date, '%Y-%m') as month"),
                'type',
                DB::raw('SUM(quantity * unit_price) as total')
            )
            ->whereBetween('transaction_date', [$from, $to])
            ->where('status', '!=', 'cancelled')
            ->groupBy('month', 'type')
            ->orderBy('month');

            if ($siteId) {
                $this->authorizeForSite($siteId);
                $query->where('site_id', $siteId);
            } else {
                $query->whereIn('site_id', $this->getUserSiteIds());
            }

            $rows = $query->get();

            // Build month-indexed map
            $map = [];
            foreach ($rows as $row) {
                $m = $row->month;
                if (!isset($map[$m])) {
                    $map[$m] = ['month' => $m, 'income' => 0, 'expense' => 0, 'refund' => 0];
                }
                $map[$m][$row->type] = round((float) $row->total, 2);
            }

            $result = array_values($map);

            return $this->success($result);
        } catch (\Throwable $e) {
            return $this->error('Failed to generate monthly trend: ' . $e->getMessage(), 500);
        }
    }

    public function expensesByCategory(Request $request): JsonResponse
    {
        try {
            $siteId     = $request->query('site_id') ?? $request->header('X-Site-Id');
            $from       = $request->query('from', now()->startOfMonth()->toDateString());
            $to         = $request->query('to', now()->endOfMonth()->toDateString());
            $customerId = $request->query('customer_id');

            $query = Transaction::select(
                'category',
                DB::raw('SUM(quantity * unit_price) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->where('type', 'expense')
            ->where('status', '!=', 'cancelled')
            ->whereBetween('transaction_date', [$from, $to])
            ->groupBy('category')
            ->orderByDesc('total');

            if ($siteId) {
                $this->authorizeForSite($siteId);
                $query->where('site_id', $siteId);
            } else {
                $query->whereIn('site_id', $this->getUserSiteIds());
            }

            if ($customerId) {
                $query->where('customer_id', $customerId);
            }

            $result = $query->get()->map(fn($r) => [
                'category' => $r->category ?? 'Uncategorized',
                'total'    => round((float) $r->total, 2),
                'count'    => $r->count,
            ]);

            return $this->success($result);
        } catch (\Throwable $e) {
            return $this->error('Failed to generate expenses by category: ' . $e->getMessage(), 500);
        }
    }

    public function customerSummary(Request $request): JsonResponse
    {
        try {
            $siteId     = $request->query('site_id') ?? $request->header('X-Site-Id');
            $from       = $request->query('from', now()->startOfMonth()->toDateString());
            $to         = $request->query('to', now()->endOfMonth()->toDateString());
            $customerId = $request->query('customer_id');

            $query = Transaction::with(['customer', 'expenseCategory'])
                ->whereBetween('transaction_date', [$from, $to])
                ->where('status', '!=', 'cancelled')
                ->whereNotNull('customer_id');

            if ($siteId) {
                $this->authorizeForSite($siteId);
                $query->where('site_id', $siteId);
            } else {
                $query->whereIn('site_id', $this->getUserSiteIds());
            }

            if ($customerId) {
                $query->where('customer_id', $customerId);
            }

            $transactions = $query->get();

            $grouped = $transactions->groupBy('customer_id');

            $result = $grouped->map(function ($txs, $custId) {
                $customer = $txs->first()->customer;
                $income   = $txs->where('type', 'income')
                    ->sum(fn($t) => (float) $t->quantity * (float) $t->unit_price);
                $expenses = $txs->whereIn('type', ['expense', 'refund'])
                    ->sum(fn($t) => (float) $t->quantity * (float) $t->unit_price);

                $expByCategory = $txs->where('type', 'expense')
                    ->groupBy(fn($t) => $t->expenseCategory?->name ?? $t->category ?? 'Uncategorized')
                    ->map(fn($g) => round($g->sum(fn($t) => (float) $t->quantity * (float) $t->unit_price), 2));

                return [
                    'customer_id'          => $custId,
                    'customer_name'        => $customer?->name ?? 'Unknown',
                    'customer_type'        => $customer?->type ?? 'external',
                    'total_income'         => round($income, 2),
                    'total_expenses'       => round($expenses, 2),
                    'net_profit'           => round($income - $expenses, 2),
                    'transaction_count'    => $txs->count(),
                    'expenses_by_category' => $expByCategory->map(fn($total, $cat) => [
                        'category' => $cat,
                        'total'    => $total,
                    ])->values(),
                ];
            })->values();

            return $this->success($result);
        } catch (\Throwable $e) {
            return $this->error('Failed to generate customer summary: ' . $e->getMessage(), 500);
        }
    }

    public function productionByDay(Request $request): JsonResponse
    {
        try {
            $siteId = $request->query('site_id') ?? $request->header('X-Site-Id');
            $from   = $request->query('from', now()->subDays(29)->toDateString());
            $to     = $request->query('to', now()->toDateString());

            $query = ProductionLog::whereBetween('log_date', [$from, $to])
                ->orderBy('log_date');

            if ($siteId) {
                $this->authorizeForSite($siteId);
                $query->where('site_id', $siteId);
            } else {
                $query->whereIn('site_id', $this->getUserSiteIds());
            }

            $logs = $query->get([
                'log_date',
                'ore_tonnes',
                'waste_tonnes',
                'grade_g_t',
                'water_m3',
                'site_id',
            ]);

            return $this->success($logs);
        } catch (\Throwable $e) {
            return $this->error('Failed to generate production by day: ' . $e->getMessage(), 500);
        }
    }
}
