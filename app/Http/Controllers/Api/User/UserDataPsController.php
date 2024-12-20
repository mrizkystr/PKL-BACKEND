<?php

namespace App\Http\Controllers\Api\User;

use Exception;
use Carbon\Carbon;
use App\Models\TargetGrowth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Resources\DataPsResource;
use App\Models\DataPsAgustusKujangSql;

class UserDataPsController extends Controller
{
    const MONTHS = [
        'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    ];

    // Method to get distinct STO list
    public function getStoList()
    {
        $stoList = DataPsAgustusKujangSql::select('STO')
            ->distinct()
            ->orderBy('STO', 'asc')
            ->pluck('STO');

        if ($stoList->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No STO found'
            ]);
        }

        Log::info('STO List Retrieved:', ['data' => $stoList]);

        return response()->json([
            'success' => true,
            'data' => $stoList
        ]);
    }

    // Method to get distinct Month list
    public function getMonthList()
    {
        $monthList = DataPsAgustusKujangSql::select('Bulan_PS')
            ->distinct()
            ->orderBy('Bulan_PS', 'asc')
            ->pluck('Bulan_PS');

        if ($monthList->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No Month data found'
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $monthList
        ]);
    }

    // Method to get unique Date list
    public function getDateList()
    {
        $dateList = DataPsAgustusKujangSql::select(DB::raw('DISTINCT DATE(TGL_PS) as tanggal'))
            ->orderBy('tanggal', 'asc')
            ->pluck('tanggal');

        if ($dateList->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No Dates found'
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $dateList
        ]);
    }

    // Method to get distinct Mitra list
    public function getMitraList()
    {
        $mitraList = DataPsAgustusKujangSql::select('Mitra')
            ->distinct()
            ->orderBy('Mitra', 'asc')
            ->pluck('Mitra');

        if ($mitraList->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No Mitra data found'
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $mitraList
        ]);
    }

    // Method to fetch paginated data
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);

        $paginatedData = DataPsAgustusKujangSql::select('id', 'ORDER_ID', 'REGIONAL', 'WITEL', 'DATEL', 'STO')
            ->paginate($perPage);

        if ($paginatedData->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No data available'
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $paginatedData->items(),
            'pagination' => [
                'total' => $paginatedData->total(),
                'per_page' => $paginatedData->perPage(),
                'current_page' => $paginatedData->currentPage(),
                'last_page' => $paginatedData->lastPage(),
            ],
        ]);
    }

    // Method to fetch single record by ID
    public function show($id)
    {
        $item = DataPsAgustusKujangSql::find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }

    public function analysisBySto(Request $request)
    {
        $selectedSto = $request->input('sto', 'all');
        $selectedMonth = $request->input('month', null); // Get month filter
        $viewType = $request->input('view_type', 'table');

        $stoList = DataPsAgustusKujangSql::select('STO')->distinct()->orderBy('STO', 'asc')->get();

        $query = $this->buildStoAnalysisQuery($selectedSto, $selectedMonth); // ```php
        $stoAnalysis = $query->get();

        if ($stoAnalysis->isEmpty()) {
            return response()->json(['message' => 'Data not found'], 404);
        }

        return $this->successResponse([
            'stoAnalysis' => $stoAnalysis,
            'stoList' => $stoList,
            'selectedSto' => $selectedSto,
            'viewType' => $viewType,
        ]);
    }

    private function buildStoAnalysisQuery($selectedSto, $selectedMonth = null)
    {
        $query = DataPsAgustusKujangSql::select('STO');

        foreach (self::MONTHS as $month) {
            $query->addSelect(DB::raw("SUM(CASE WHEN Bulan_PS = '{$month}' THEN 1 ELSE 0 END) AS total_{$month}"));
        }

        $query->addSelect(DB::raw('SUM(1) AS grand_total'))
            ->groupBy('STO');

        if ($selectedSto !== 'all') {
            $query->where('STO', $selectedSto);
        }

        if ($selectedMonth) {
            $query->where('Bulan_PS', $selectedMonth);
        }

        return $query;
    }

    public function analysisByMonth(Request $request)
    {
        $monthAnalysis = DataPsAgustusKujangSql::select('Bulan_PS', 'STO', DB::raw('count(*) as total'))
            ->groupBy('Bulan_PS', 'STO')
            ->orderBy('Bulan_PS', 'asc')
            ->orderBy('STO', 'asc')
            ->get();

        return $this->successResponse(['month_analysis' => $monthAnalysis]);
    }

    public function analysisByCode(Request $request)
    {
        $selectedSto = $request->input('sto', null);
        $selectedMonth = $request->input('month', null);

        $bulanPsList = DataPsAgustusKujangSql::select('Bulan_PS')->distinct()->pluck('Bulan_PS');

        $codeAnalysis = $this->buildCodeAnalysisQuery($selectedSto, $selectedMonth);

        $organizedData = $this->organizeCodeAnalysisData($codeAnalysis);

        return $this->successResponse([
            'analysis_per_code' => array_values($organizedData),
            'bulan_list' => $bulanPsList,
        ]);
    }

    private function buildCodeAnalysisQuery($selectedSto = null, $selectedMonth = null)
    {
        $query = DataPsAgustusKujangSql::select(
            'data_ps_agustus_kujang_sql.Bulan_PS',
            'data_ps_agustus_kujang_sql.STO',
            'data_ps_agustus_kujang_sql.Kode_sales',
            'data_ps_agustus_kujang_sql.Nama_SA',
            DB::raw("
            CASE 
                WHEN data_ps_agustus_kujang_sql.Bulan_PS = 'Agustus' THEN sales_codes.kode_agen
                WHEN data_ps_agustus_kujang_sql.Bulan_PS = 'September' THEN sales_codes.kode_baru
                ELSE NULL
            END as kode_selected
        "),
            DB::raw("COUNT(DISTINCT data_ps_agustus_kujang_sql.id) as total")
        )
            ->leftJoin('sales_codes', function ($join) {
                $join->on('data_ps_agustus_kujang_sql.STO', '=', 'sales_codes.sto')
                    ->on(function ($query) {
                        $query->where('data_ps_agustus_kujang_sql.Bulan_PS', 'Agustus')
                            ->whereColumn('data_ps_agustus_kujang_sql.Kode_sales', 'sales_codes.kode_agen')
                            ->orWhere('data_ps_agustus_kujang_sql.Bulan_PS', 'September')
                            ->whereColumn('data_ps_agustus_kujang_sql.Kode_sales', 'sales_codes.kode_baru');
                    });
            });

        if ($selectedSto) {
            $query->where('data_ps_agustus_kujang_sql.STO', $selectedSto);
        }

        if ($selectedMonth) {
            $query->where('data_ps_agustus_kujang_sql.Bulan_PS', $selectedMonth);
        }

        return $query->groupBy(
            'data_ps_agustus_kujang_sql.Bulan_PS',
            'data_ps_agustus_kujang_sql.STO',
            'data_ps_agustus_kujang_sql.Kode_sales',
            'data_ps_agustus_kujang_sql.Nama_SA',
            DB::raw('kode_selected')
        )
            ->orderBy('data_ps_agustus_kujang_sql.Bulan_PS', 'asc')
            ->orderBy('data_ps_agustus_kujang_sql.STO', 'asc')
            ->orderBy('kode_selected', 'asc')
            ->get();
    }

    /*************  ✨ Codeium Command ⭐  *************/
    /**
     * Organize the code analysis data into a format that can be easily 
     * consumed by the frontend.
     * 
     * The data is organized into an associative array where the keys are 
     * the codes and the values are another associative array that contains 
     * the code, the name of the sales and the total number of sales.
     * 
     * @param  array  $codeAnalysis The code analysis data from the database.
     * @return array                The organized data.
     */
    /******  9724ea56-0c2e-4e4c-a751-96bc427cad78  *******/
    private function organizeCodeAnalysisData($codeAnalysis)
    {
        $organizedData = [];
        foreach ($codeAnalysis as $item) {
            $key = $item->kode_selected ?? $item->Kode_sales;
            if (!isset($organizedData[$key])) {
                $organizedData[$key] = [
                    'kode' => $key,
                    'nama' => $item->Nama_SA,
                    'total' => 0
                ];
            }
            $organizedData[$key]['total'] += $item->total;
        }
        return $organizedData;
    }

    public function analysisByMitra(Request $request)
    {
        try {
            $bulanPsList = DataPsAgustusKujangSql::distinct()->pluck('Bulan_PS');
            $mitraList = DataPsAgustusKujangSql::distinct()->pluck('Mitra');
            $stoList = DataPsAgustusKujangSql::distinct()->pluck('STO')->sort();

            $mitraAnalysis = DataPsAgustusKujangSql::select(
                'data_ps_agustus_kujang_sql.Mitra',
                DB::raw('COUNT(DISTINCT data_ps_agustus_kujang_sql.id) as total')
            )
                ->groupBy('Mitra')
                ->orderBy('Mitra', 'asc')
                ->get();

            return $this->successResponse([
                'bulan_list' => $bulanPsList,
                'sto_list' => $stoList,
                'mitra_list' => $mitraList,
                'mitra_analysis' => $mitraAnalysis,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while retrieving data.', 500, $e->getMessage());
        }
    }

    public function stoChart(Request $request)
    {
        $bulanPs = $request->input('bulan_ps');
        $selectedMitra = $request->input('id_mitra');

        $data = DataPsAgustusKujangSql::select('STO', DB::raw('count(*) as total'))
            ->when($bulanPs, function ($query) use ($bulanPs) {
                return $query->where('Bulan_PS', $bulanPs);
            })
            ->when($selectedMitra, function ($query) use ($selectedMitra) {
                return $query->where('Mitra', $selectedMitra);
            })
            ->groupBy('STO')
            ->get();

        return $this->successResponse([
            'labels' => $data->pluck('STO'),
            'data' => $data->pluck('total'),
        ]);
    }

    public function stoPieChart(Request $request)
    {
        $bulanPs = $request->input('bulan_ps');
        $selectedMitra = $request->input('id_mitra');

        $data = DataPsAgustusKujangSql::select('STO', DB::raw('count(*) as total'))
            ->when($bulanPs, function ($query) use ($bulanPs) {
                return $query->where('Bulan_PS', $bulanPs);
            })
            ->when($selectedMitra, function ($query) use ($selectedMitra) {
                return $query->where('Mitra', $selectedMitra);
            })
            ->groupBy('STO')
            ->get();

        return $this->successResponse([
            'labels' => $data->pluck('STO'),
            'data' => $data->pluck('total'),
        ]);
    }

    public function mitraBarChartAnalysis(Request $request)
    {
        $selectedSto = $request->input('sto');
        $bulanPs = $request->input('bulan_ps');

        $stoList = DataPsAgustusKujangSql::distinct()->pluck('STO')->sort();

        $mitraAnalysis = DataPsAgustusKujangSql::select(
            'Mitra',
            DB::raw("COUNT(DISTINCT id) as total")
        )
            ->when($bulanPs, function ($query) use ($bulanPs) {
                return $query->where('Bulan_PS', $bulanPs);
            })
            ->when($selectedSto, function ($query) use ($selectedSto) {
                return $query->where('STO', $selectedSto);
            })
            ->groupBy('Mitra')
            ->get();

        return $this->successResponse([
            'stoList' => $stoList,
            'labels' => $mitraAnalysis->pluck('Mitra')->toArray(),
            'totals' => $mitraAnalysis->pluck('total')->toArray(),
        ]);
    }

    public function mitraPieChartAnalysis(Request $request)
    {
        try {
            // Ambil input dari request
            $selectedSto = $request->input('sto');
            $bulanPs = $request->input('bulan_ps');

            // Logging untuk debugging
            Log::info('Request received for mitraPieChartAnalysis', [
                'sto' => $selectedSto,
                'bulan_ps' => $bulanPs
            ]);

            // Ambil daftar STO (distinct dan terurut)
            $stoList = DataPsAgustusKujangSql::distinct()
                ->pluck('STO')
                ->sort()
                ->values(); // Pastikan array memiliki indeks numerik berurutan

            // Query untuk analisis mitra
            $mitraAnalysis = DataPsAgustusKujangSql::select(
                'Mitra',
                DB::raw("COUNT(DISTINCT id) as total")
            )
                ->when($bulanPs, function ($query) use ($bulanPs) {
                    return $query->where('Bulan_PS', $bulanPs);
                })
                ->when($selectedSto, function ($query) use ($selectedSto) {
                    return $query->where('STO', $selectedSto);
                })
                ->groupBy('Mitra')
                ->get();

            // Response sukses
            return response()->json([
                'success' => true,
                'data' => [
                    'stoList' => $stoList,
                    'labels' => $mitraAnalysis->pluck('Mitra')->toArray(),
                    'totals' => $mitraAnalysis->pluck('total')->toArray(),
                ]
            ], 200);
        } catch (Exception $e) {
            // Logging error untuk debugging
            Log::error('Error in mitraPieChartAnalysis', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Response error
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function dayAnalysis(Request $request)
    {
        try {
            $page = $request->input('page', 1);
            $perPage = 9;

            // Ambil semua tanggal unik terurut
            $uniqueDates = DataPsAgustusKujangSql::query()
                ->select('TGL_PS')
                ->distinct()
                ->orderBy('TGL_PS', 'asc')
                ->paginate($perPage, ['*'], 'page', $page);

            // Buat data group untuk setiap tanggal
            $groupedData = $uniqueDates->map(function ($dateItem) {
                // Cari data untuk tanggal ini
                $items = DataPsAgustusKujangSql::query()
                    ->where('TGL_PS', $dateItem->TGL_PS)
                    ->get();

                return [
                    'TGL_PS' => $dateItem->TGL_PS,
                    'total' => $items->count(),
                    'details' => $items->map(function ($item) {
                        return [
                            'ORDER_ID' => $item->ORDER_ID,
                            'STO' => $item->STO,
                            'CUSTOMER_NAME' => $item->CUSTOMER_NAME,
                            'ADDON' => $item->ADDON,
                            'KODE_SALES' => $item->Kode_sales,
                            'NAMA_SA' => $item->Nama_SA,
                        ];
                    }),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $groupedData,
                'pagination' => [
                    'current_page' => $uniqueDates->currentPage(),
                    'per_page' => $uniqueDates->perPage(),
                    'total' => $uniqueDates->total(),
                    'last_page' => $uniqueDates->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Day Analysis Error:', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'request_data' => $request->all(),
            ], 500);
        }
    }

    public function targetTrackingAndSalesChart(Request $request)
    {
        try {
            Carbon::setLocale('id');

            // Ambil query input bulan, tahun, dan view type
            $monthInput = $request->query('month', now()->month);
            $month = is_numeric($monthInput) ? (int)$monthInput : $this->convertMonthToNumber($monthInput);
            $year = (int)$request->query('year', now()->year);
            $viewType = $request->query('view_type', 'table'); // Default "table"

            if (!$month) {
                return response()->json(['success' => false, 'message' => 'Invalid month input'], 400);
            }

            $previousMonth = $month == 1 ? 12 : $month - 1;
            $previousYear = $month == 1 ? $year - 1 : $year;

            // Fetch data
            $currentMonthData = $this->fetchMonthlyData($month, $year);
            $previousMonthData = $this->fetchMonthlyData($previousMonth, $previousYear);
            $targetData = $this->fetchTargetGrowth($month, $year);

            $currentMonthName = Carbon::createFromDate($year, $month, 1)->translatedFormat('F');
            $previousMonthName = Carbon::createFromDate($previousYear, $previousMonth, 1)->translatedFormat('F');

            $currentMonthData = $this->processMonthlyData($currentMonthData, $month, $year);
            $previousMonthData = $this->processMonthlyData($previousMonthData, $previousMonth, $previousYear);

            // Hitung total realisasi dan performance data
            $currentTotal = collect($currentMonthData)->sum('ps_harian');
            $previousTotal = collect($previousMonthData)->sum('ps_harian');
            $daysElapsed = now()->day; // Hari berjalan

            $dailyTargetAvg = $daysElapsed > 0 ? round($currentTotal / $daysElapsed, 2) : 0;
            $achievementGrowth = $targetData['target_growth'] > 0 ? round(($currentTotal / $targetData['target_growth']) * 100, 2) : 0;
            $achievementRkap = $targetData['target_rkap'] > 0 ? round(($currentTotal / $targetData['target_rkap']) * 100, 2) : 0;

            // Response berdasarkan View Type
            $responseData = [
                'success' => true,
                'message' => 'Target tracking data retrieved successfully',
                'performance_data' => [
                    'daily_target_average' => $dailyTargetAvg,
                    'mtd_realization' => $currentTotal,
                    'achievement_target_growth' => $achievementGrowth,
                    'achievement_target_rkap' => $achievementRkap
                ],
                'current_month' => [
                    'month' => $currentMonthName,
                    'year' => $year,
                    'data' => $viewType === 'table' ? $currentMonthData : [], // Tabel atau kosong jika chart
                    'total_mtd' => $currentTotal
                ],
                'previous_month' => [
                    'month' => $previousMonthName,
                    'year' => $previousYear,
                    'data' => $viewType === 'table' ? $previousMonthData : [],
                    'total_mtd' => $previousTotal
                ],
                'comparison' => [
                    'gap_mtd' => $currentTotal - $previousTotal
                ],
                'view_type' => $viewType
            ];

            return response()->json($responseData, 200);
        } catch (\Exception $e) {
            Log::error('Error in Target Tracking and Sales Chart', ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to retrieve data', 'error' => $e->getMessage()], 500);
        }
    }


    /**
     * Mengonversi nama bulan bahasa Indonesia menjadi angka.
     */
    private function convertMonthToNumber($monthName)
    {
        $months = [
            'januari' => 1,
            'februari' => 2,
            'maret' => 3,
            'april' => 4,
            'mei' => 5,
            'juni' => 6,
            'juli' => 7,
            'agustus' => 8,
            'september' => 9,
            'oktober' => 10,
            'november' => 11,
            'desember' => 12
        ];

        return $months[strtolower($monthName)] ?? null;
    }

    /**
     * Mengambil data PS berdasarkan bulan dan tahun.
     */
    private function fetchMonthlyData($month, $year)
    {
        return DataPsAgustusKujangSql::query()
            ->whereMonth('TGL_PS', $month)
            ->whereYear('TGL_PS', $year)
            ->select(
                DB::raw('DATE(TGL_PS) as tgl'),
                DB::raw('DAY(TGL_PS) as day'),
                DB::raw('COUNT(*) as ps_harian')
            )
            ->groupBy(DB::raw('DATE(TGL_PS), DAY(TGL_PS)'))
            ->orderBy('tgl')
            ->get();
    }

    /**
     * Mengambil target growth dan RKAP berdasarkan bulan dan tahun.
     */
    private function fetchTargetGrowth($month, $year)
    {
        $monthFormatted = ucfirst(strtolower($month));
        $target = TargetGrowth::whereRaw('LOWER(month) = ?', [strtolower($monthFormatted)])
            ->where('year', $year)
            ->first();

        return [
            'month' => $monthFormatted,
            'year' => $year,
            'target_growth' => $target?->target_growth ?? 0,
            'target_rkap' => $target?->target_rkap ?? 0,
        ];
    }

    /**
     * Menghasilkan data kosong jika tidak ada data.
     */
    private function generateEmptyData($month, $year)
    {
        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;

        return collect(range(1, $daysInMonth))->map(function ($day) use ($month, $year) {
            return [
                'date' => Carbon::create($year, $month, $day)->format('Y-m-d'),
                'day' => $day,
                'ps_harian' => 0,
                'realisasi_mtd' => 0,
            ];
        });
    }

    /**
     * Memproses data bulanan untuk dihitung realisasi MTD.
     */
    private function processMonthlyData($monthlyData, $month, $year)
    {
        $cumulativeTotal = 0;
        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;

        return collect(range(1, $daysInMonth))->map(function ($day) use ($monthlyData, &$cumulativeTotal, $month, $year) {
            $dailyData = $monthlyData->firstWhere('day', $day);
            $dailyCount = $dailyData ? $dailyData['ps_harian'] : 0;
            $cumulativeTotal += $dailyCount;

            $dayOfWeek = Carbon::create($year, $month, $day)->format('l');
            $gimmick = $this->calculateGimmick($dailyCount, $dayOfWeek);

            return [
                'date' => Carbon::create($year, $month, $day)->format('Y-m-d'),
                'day' => $day,
                'ps_harian' => $dailyCount,
                'realisasi_mtd' => $cumulativeTotal,
                'gimmick' => $gimmick
            ];
        });
    }

    /**
     * Menentukan status 'gimmick' berdasarkan ps_harian dan hari dalam seminggu.
     */
    private function calculateGimmick($ps_harian, $dayOfWeek)
    {
        $threshold = match ($dayOfWeek) {
            'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday' => 7,
            'Saturday' => 6,
            'Sunday' => 5,
            default => 7,
        };

        return $ps_harian >= $threshold ? 'achieve' : 'not achieve';
    }
    
    private function successResponse($data, $status = 200)
    {
        return response()->json(['success' => true, 'data' => $data], $status);
    }

    private function errorResponse($message, $status = 400, $errors = null)
    {
        return response()->json(['success' => false, 'message' => $message, 'errors' => $errors], $status);
    }
}
