<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PurchaseRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class DashboardService
{
    public function summary(int $year, ?int $officeId = null): array
    {
        return [
            'kpi'                         => $this->kpi($year, $officeId),
            'budget_utilization_by_month' => $this->budgetByMonth($year, $officeId),
            'requests_per_section'        => $this->requestsPerSection($year, $officeId),
            'budget_per_section'          => $this->budgetPerSection($year, $officeId),
            'requests_per_category'       => $this->requestsPerCategory($year, $officeId),
            'recent_requests'             => $this->recentRequests($year, $officeId),
            'high_value_requests'         => $this->highValueRequests($year, $officeId),
        ];
    }

    /**
     * Build a reusable base query scoped to year + optional office, excluding drafts.
     * All column references are fully qualified to prevent ambiguity after joins.
     */
    private function baseQuery(int $year, ?int $officeId): Builder
    {
        $q = PurchaseRequest::query()
            ->whereYear('purchase_requests.created_at', $year)
            ->where('purchase_requests.status', '!=', 'draft');

        if ($officeId !== null) {
            $q->where('purchase_requests.requesting_office_id', $officeId);
        }

        return $q;
    }

    private function kpi(int $year, ?int $officeId): array
    {
        $base = $this->baseQuery($year, $officeId);

        $pendingStatuses  = ['submitted', 'under_review', 'returned', 'for_budget_approval'];
        $approvedStatuses = [
            'budget_approved', 'forwarded_to_ppu', 'pr_prepared', 'pr_approved',
            'rfq_prepared', 'canvassing', 'abstract_prepared', 'bac_resolution_noa', 'po_prepared',
        ];

        return [
            'total_requests'     => (clone $base)->count(),
            'pending_requests'   => (clone $base)->whereIn('purchase_requests.status', $pendingStatuses)->count(),
            'approved_requests'  => (clone $base)->whereIn('purchase_requests.status', $approvedStatuses)->count(),
            'completed_requests' => (clone $base)->where('purchase_requests.status', 'completed')->count(),
        ];
    }

    private function budgetByMonth(int $year, ?int $officeId): array
    {
        // SQLite stores dates as text — use strftime() for month extraction.
        // MySQL provides a native MONTH() function.
        if (DB::getDriverName() === 'sqlite') {
            $selectExpr = 'CAST(strftime("%m", purchase_requests.created_at) AS INTEGER) as month, SUM(purchase_requests.total_amount) as total';
            $groupExpr  = 'strftime("%m", purchase_requests.created_at)';
        } else {
            $selectExpr = 'MONTH(purchase_requests.created_at) as month, SUM(purchase_requests.total_amount) as total';
            $groupExpr  = 'MONTH(purchase_requests.created_at)';
        }

        $rows = $this->baseQuery($year, $officeId)
            ->selectRaw($selectExpr)
            ->groupByRaw($groupExpr)
            ->orderByRaw($groupExpr)
            ->pluck('total', 'month')
            ->toArray();

        // Materialise all 12 months — months with no data default to 0.
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[] = [
                'month' => $m,
                'label' => date('M', mktime(0, 0, 0, $m, 1)),
                'total' => round((float) ($rows[$m] ?? 0), 2),
            ];
        }

        return [
            'months'      => $months,
            'grand_total' => round(array_sum(array_column($months, 'total')), 2),
        ];
    }

    private function requestsPerSection(int $year, ?int $officeId): array
    {
        return $this->baseQuery($year, $officeId)
            ->join('offices', 'purchase_requests.requesting_office_id', '=', 'offices.id')
            ->selectRaw('offices.name as office, COUNT(*) as count')
            ->groupBy('offices.id', 'offices.name')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($r) => ['office' => $r->office, 'count' => (int) $r->count])
            ->toArray();
    }

    private function budgetPerSection(int $year, ?int $officeId): array
    {
        return $this->baseQuery($year, $officeId)
            ->join('offices', 'purchase_requests.requesting_office_id', '=', 'offices.id')
            ->selectRaw('offices.name as office, SUM(purchase_requests.total_amount) as total')
            ->groupBy('offices.id', 'offices.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => ['office' => $r->office, 'total' => round((float) $r->total, 2)])
            ->toArray();
    }

    private function requestsPerCategory(int $year, ?int $officeId): array
    {
        return $this->baseQuery($year, $officeId)
            ->leftJoin('categories', 'purchase_requests.category_id', '=', 'categories.id')
            ->selectRaw("COALESCE(categories.name, 'Uncategorized') as category, COUNT(*) as count")
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($r) => ['category' => $r->category, 'count' => (int) $r->count])
            ->toArray();
    }

    private function recentRequests(int $year, ?int $officeId): array
    {
        return $this->baseQuery($year, $officeId)
            ->with(['requester', 'requestingOffice'])
            ->orderByDesc('purchase_requests.submitted_at')
            ->limit(5)
            ->get()
            ->map(fn (PurchaseRequest $pr) => [
                'rf_number'    => $pr->rf_number,
                'pr_number'    => $pr->pr_number,
                'requester'    => $pr->requester?->full_name,
                'office'       => $pr->requestingOffice?->name,
                'total_amount' => $pr->total_amount,
                'status'       => $pr->status,
            ])
            ->toArray();
    }

    private function highValueRequests(int $year, ?int $officeId): array
    {
        return $this->baseQuery($year, $officeId)
            ->with(['requestingOffice'])
            ->orderByDesc('purchase_requests.total_amount')
            ->limit(5)
            ->get()
            ->map(fn (PurchaseRequest $pr) => [
                'rf_number'    => $pr->rf_number,
                'purpose'      => $pr->purpose,
                'office'       => $pr->requestingOffice?->name,
                'total_amount' => $pr->total_amount,
            ])
            ->toArray();
    }
}
