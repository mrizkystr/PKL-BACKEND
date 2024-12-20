<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\DataPsAgustusKujangSql;
use App\Models\SalesCodes;
use Illuminate\Http\JsonResponse;

class SalesDashboardController extends Controller
{
    public function dashboard(): JsonResponse
    {
        try {
            $salesData = $this->getSalesData();
            $recentData = $this->getRecentData();

            return response()->json(array_merge($salesData, $recentData), 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function getSalesData(): array
    {
        return [
            'totalSalesCodes' => SalesCodes::count(),
            'totalOrders' => DataPsAgustusKujangSql::count(),
            'completedOrders' => DataPsAgustusKujangSql::where('STATUS_MESSAGE', 'completed')->count(),
            'pendingOrders' => DataPsAgustusKujangSql::where('STATUS_MESSAGE', 'pending')->count(),
        ];
    }

    private function getRecentData(): array
    {
        return [
            'recentSalesCodes' => $this->getRecentSalesCodes(),
            'recentOrders' => $this->getRecentOrders(),
        ];
    }

    private function getRecentSalesCodes(int $limit = 5): array
    {
        return SalesCodes::selectRaw("
            CASE 
                WHEN MONTH(created_at) = 8 AND YEAR(created_at) = 2024 THEN kode_agen
                ELSE kode_baru 
            END AS kode,
            mitra_nama, 
            created_at
        ")
            ->latest()
            ->take($limit)
            ->get()
            ->toArray();
    }

    private function getRecentOrders(int $limit = 5): array
    {
        return DataPsAgustusKujangSql::orderBy('ORDER_ID', 'desc')
            ->take($limit)
            ->get(['ORDER_ID', 'CUSTOMER_NAME', 'ORDER_DATE'])
            ->toArray();
    }
}
