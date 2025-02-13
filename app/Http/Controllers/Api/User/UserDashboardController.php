<?php

namespace App\Http\Controllers\Api\User;

use App\Models\SalesCodes;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\DataPsAgustusKujangSql;

class UserDashboardController extends Controller
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
            $chartData = $this->getChartData(); // Tambahan untuk data grafik

            // Return a JSON response
            return response()->json(array_merge($salesData, $recentData, $chartData), 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function getSalesData(?string $startDate, ?string $endDate): array
    {
        $salesCodesQuery = SalesCodes::query();
        $ordersQuery = DataPsAgustusKujangSql::query();

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
            ->toArray();
    }

    private function getRecentOrders(int $limit = 5): array
    {
        return DataPsAgustusKujangSql::orderBy('ORDER_ID', 'desc')
            ->take($limit)
            ->get(['ORDER_ID', 'CUSTOMER_NAME', 'ORDER_DATE'])
            ->toArray();
    }

    // Method baru untuk mengambil data grafik
    private function getChartData(): array
    {
        // Data untuk grafik batang - Jumlah order per bulan
        $barChartData = DataPsAgustusKujangSql::select(
            DB::raw('COALESCE(Bulan_PS, MONTH(TGL_PS)) as bulan'),
            DB::raw('COUNT(*) as jumlah_order')
        )
            ->groupBy(DB::raw('COALESCE(Bulan_PS, MONTH(TGL_PS))'))
            ->orderBy('bulan')
            ->get();

        // Data untuk grafik pie - Status pesanan
        $pieChartData = DataPsAgustusKujangSql::select(
            'STATUS_MESSAGE',
            DB::raw('COUNT(*) as jumlah')
        )
            ->groupBy('STATUS_MESSAGE')
            ->get();

        // Format data untuk grafik batang
        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $formattedBarData = [
            'labels' => $barChartData->map(function ($item) use ($monthNames) {
                return $monthNames[$item->bulan] ?? "Bulan {$item->bulan}";
            })->values(),
            'datasets' => [[
                'label' => 'Jumlah Order per Bulan',
                'data' => $barChartData->pluck('jumlah_order')->values(),
            ]]
        ];

        // Format data untuk grafik pie
        $formattedPieData = [
            'labels' => $pieChartData->pluck('STATUS_MESSAGE'),
            'datasets' => [[
                'data' => $pieChartData->pluck('jumlah'),
            ]]
        ];

        return [
            'barChartData' => $formattedBarData,
            'pieChartData' => $formattedPieData
        ];
    }
}
