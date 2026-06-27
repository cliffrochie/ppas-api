<?php

declare(strict_types=1);

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\DashboardRequest;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

final class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $service)
    {
    }

    public function index(DashboardRequest $request): JsonResponse
    {
        $year     = (int) $request->input('year', now()->year);
        $officeId = $request->filled('office_id')
            ? (int) $request->input('office_id')
            : null;

        $data = $this->service->summary($year, $officeId);

        return response()->json([
            'data'    => $data,
            'message' => 'Dashboard data retrieved successfully.',
            'errors'  => null,
        ]);
    }
}
