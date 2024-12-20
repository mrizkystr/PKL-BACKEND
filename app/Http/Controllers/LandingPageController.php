<?php

namespace App\Http\Controllers;

use App\Models\SalesCodes;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Models\DataPsAgustusKujangSql;

class LandingPageController extends Controller
{
    /**
     * Get sales statistics for the landing page.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $salesStatistics = $this->getSalesStatistics();

        // Debug respons
        Log::info('Sales Statistics:', $salesStatistics);

        return response()->json($salesStatistics);
    }


    /**
     * Get sales statistics.
     *
     * @return array
     */
    private function getSalesStatistics(): array
    {
        return [
            'totalSalesCodes' => SalesCodes::count(),
            'totalOrders' => DataPsAgustusKujangSql::count(),
            'completedOrders' => $this->getOrderCountByStatus('completed'),
            'pendingOrders' => $this->getOrderCountByStatus('pending'),
        ];
    }

    /**
     * Get the count of orders by status.
     *
     * @param string $status
     * @return int
     */
    private function getOrderCountByStatus(string $status): int
    {
        return DataPsAgustusKujangSql::where('STATUS_MESSAGE', $status)->count();
    }
}
