<?php

namespace App\Http\Controllers;

use App\Models\DataPsAgustusKujangSql;
use App\Models\SalesCodes;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function dashboard(): JsonResponse
    {
        try {
            // Fetching counts and recent records
            $salesData = $this->getSalesData();
            $recentData = $this->getRecentData();

            // Return a JSON response
            return response()->json(array_merge($salesData, $recentData), 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500); // Handle errors gracefully
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
            ->toArray(); // Convert the result to an array
    }

    private function getRecentOrders(int $limit = 5): array
    {
        return DataPsAgustusKujangSql::orderBy('ORDER_ID', 'desc')
            ->take($limit)
            ->get(['ORDER_ID', 'CUSTOMER_NAME', 'ORDER_DATE']) // Adjust columns to your database structure
            ->toArray(); // Convert the result to an array
    }
}
