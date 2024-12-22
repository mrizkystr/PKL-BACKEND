<?php

namespace App\Http\Controllers;

use App\Models\DataPsAgustusKujangSql;
use App\Models\SalesCodes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        try {
            // Mendapatkan filter tanggal dari request
            $startDate = $request->query('start_date'); // Format: YYYY-MM-DD
            $endDate = $request->query('end_date');     // Format: YYYY-MM-DD

            // Fetching counts and recent records
            $salesData = $this->getSalesData($startDate, $endDate);
            $recentData = $this->getRecentData();

            // Return a JSON response
            return response()->json(array_merge($salesData, $recentData), 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500); // Handle errors gracefully
        }
    }

    private function getSalesData(?string $startDate, ?string $endDate): array
    {
        $salesCodesQuery = SalesCodes::query();
        $ordersQuery = DataPsAgustusKujangSql::query();

        // Filter berdasarkan created_at untuk sales codes
        if ($startDate && $endDate) {
            $salesCodesQuery->whereBetween('created_at', [$startDate, $endDate]);
            $ordersQuery->whereBetween('TGL_PS', [$startDate, $endDate]);
        }

        return [
            'totalSalesCodes' => $salesCodesQuery->count(),
            'totalOrders' => $ordersQuery->count(),
            'completedOrders' => $ordersQuery->where('STATUS_MESSAGE', 'completed')->count(),
            'pendingOrders' => $ordersQuery->where('STATUS_MESSAGE', 'pending')->count(),
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
