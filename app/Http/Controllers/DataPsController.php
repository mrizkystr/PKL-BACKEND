<?php

namespace App\Http\Controllers;

use Exception;
use Carbon\Carbon;
use App\Models\TargetGrowth;
use Illuminate\Http\Request;
use App\Imports\DataPsImport;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\DataResource;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Resources\DataPsResource;
use App\Models\DataPsAgustusKujangSql;

class DataPsController extends Controller
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
        $selectedYear = $request->input('year', date('Y')); // Default ke tahun sekarang
        $viewType = $request->input('view_type', 'table');

        $stoList = DataPsAgustusKujangSql::select('STO')->distinct()->orderBy('STO', 'asc')->get();

        $query = $this->buildStoAnalysisQuery($selectedSto, $selectedYear);
        $stoAnalysis = $query->get();

        if ($stoAnalysis->isEmpty()) {
            return response()->json(['message' => 'Data not found'], 404);
        }

        return $this->successResponse([
            'stoAnalysis' => $stoAnalysis,
            'stoList' => $stoList,
            'selectedSto' => $selectedSto,
            'selectedYear' => $selectedYear,
            'viewType' => $viewType,
        ]);
    }

    private function buildStoAnalysisQuery($selectedSto, $selectedYear)
    {
        $query = DataPsAgustusKujangSql::select('STO');

        for ($month = 1; $month <= 12; $month++) {
            $query->addSelect(DB::raw(
                "SUM(CASE WHEN YEAR(TGL_PS) = {$selectedYear} AND MONTH(TGL_PS) = {$month} THEN 1 ELSE 0 END) AS total_{$month}"
            ));
        }

        $query->addSelect(DB::raw(
            "SUM(CASE WHEN YEAR(TGL_PS) = {$selectedYear} THEN 1 ELSE 0 END) AS grand_total"
        ))->groupBy('STO');

        if ($selectedSto !== 'all') {
            $query->where('STO', $selectedSto);
        }

        return $query;
    }


    public function analysisByMonth(Request $request)
    {
        // Query untuk mengambil data dengan atau tanpa filter bulan
        $query = DataPsAgustusKujangSql::select('Bulan_PS', 'STO', DB::raw('count(*) as total'));

        // Tambahkan filter bulan jika parameter 'bulan' ada
        if ($request->has('bulan') && !empty($request->bulan)) {
            $query->where('Bulan_PS', $request->bulan);
        }

        $monthAnalysis = $query
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

        $organizedData = $this->organizeCodeAnalysisData($codeAnalysis->items()); // Mengambil item dari pagination

        return $this->successResponse([
            'analysis_per_code' => array_values($organizedData),
            'bulan_list' => $bulanPsList,
            'pagination' => [
                'current_page' => $codeAnalysis->currentPage(),
                'total_pages' => $codeAnalysis->lastPage(),
                'total_items' => $codeAnalysis->total(),
                'per_page' => $codeAnalysis->perPage(),
                'next_page_url' => $codeAnalysis->nextPageUrl(),
                'prev_page_url' => $codeAnalysis->previousPageUrl(),
            ],
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
            ->paginate(10); // Pagination dengan 10 item per halaman
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

    // Method to save target growth
    public function saveTargetGrowth(Request $request)
    {
        try {
            $validated = $request->validate([
                'month' => 'required|string',
                'year' => 'required|integer',
                'target_growth' => 'nullable|numeric|min:0',
                'target_rkap' => 'nullable|numeric|min:0',
            ]);

            Log::info("Saving Target Growth: ", $validated);

            $targetGrowth = TargetGrowth::updateOrCreate(
                [
                    'month' => strtolower($validated['month']),
                    'year' => $validated['year']
                ],
                [
                    'target_growth' => $validated['target_growth'],
                    'target_rkap' => $validated['target_rkap'],
                ]
            );

            return response()->json([
                'success' => true,
                'message' => $targetGrowth->wasRecentlyCreated
                    ? 'Target Growth and RKAP successfully created.'
                    : 'Target Growth and RKAP successfully updated.',
                'data' => $targetGrowth
            ], 200);
        } catch (\Exception $e) {
            Log::error('Save Target Growth Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to save Target Growth',
                'error' => $e->getMessage()
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
            $viewType = $request->query('view_type', 'table');

            if (!$month) {
                return response()->json(['success' => false, 'message' => 'Invalid month input'], 400);
            }

            $previousMonth = $month == 1 ? 12 : $month - 1;
            $previousYear = $month == 1 ? $year - 1 : $year;

            // Fetch data
            $currentMonthData = $this->fetchMonthlyData($month, $year);
            $previousMonthData = $this->fetchMonthlyData($previousMonth, $previousYear);
            $targetData = $this->fetchTargetGrowth($month, $year);

            // Debug log untuk target data
            Log::info('Raw Target Data:', $targetData);

            // Set nilai default untuk target
            $targetGrowth = $targetData['target_growth'] ?? 0;
            $targetRkap = $targetData['target_rkap'] ?? 0;

            $currentMonthName = Carbon::createFromDate($year, $month, 1)->translatedFormat('F');
            $previousMonthName = Carbon::createFromDate($previousYear, $previousMonth, 1)->translatedFormat('F');

            $currentMonthData = $this->processMonthlyData($currentMonthData, $month, $year);
            $previousMonthData = $this->processMonthlyData($previousMonthData, $previousMonth, $previousYear);

            // Hitung total realisasi
            $currentTotal = collect($currentMonthData)->sum('ps_harian');
            $previousTotal = collect($previousMonthData)->sum('ps_harian');
            $daysElapsed = now()->day;

            // Debug log untuk nilai-nilai perhitungan
            Log::info('Calculation Values:', [
                'currentTotal' => $currentTotal,
                'targetGrowth' => $targetGrowth,
                'targetRkap' => $targetRkap
            ]);

            // Perhitungan Daily Target Average
            $dailyTargetAvg = $daysElapsed > 0 ? round($currentTotal / $daysElapsed, 2) : 0;

            // Perhitungan Achievement
            $achievementTargetGrowth = 0;
            $achievementTargetRkap = 0;

            if ($targetGrowth > 0 && $currentTotal > 0) {
                $achievementTargetGrowth = round(($currentTotal / $targetGrowth) * 100, 2);
            }

            if ($targetRkap > 0 && $currentTotal > 0) {
                $achievementTargetRkap = round(($currentTotal / $targetRkap) * 100, 2);
            }

            // Debug log untuk hasil achievement
            Log::info('Achievement Results:', [
                'achievementTargetGrowth' => $achievementTargetGrowth,
                'achievementTargetRkap' => $achievementTargetRkap
            ]);

            $responseData = [
                'success' => true,
                'message' => 'Target tracking data retrieved successfully',
                'performance_data' => [
                    'daily_target_average' => $dailyTargetAvg,
                    'mtd_realization' => $currentTotal,
                    'achievement_target_growth' => $achievementTargetGrowth > 0 ? $achievementTargetGrowth : null,
                    'achievement_target_rkap' => $achievementTargetRkap > 0 ? $achievementTargetRkap : null
                ],
                'current_month' => [
                    'month' => $currentMonthName,
                    'year' => $year,
                    'data' => $viewType === 'table' ? $currentMonthData : [],
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

            // Debug log untuk final response
            Log::info('Final Response:', $responseData);

            return response()->json($responseData, 200);
        } catch (\Exception $e) {
            Log::error('Error in Target Tracking and Sales Chart', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

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

    private function fetchTargetGrowth($month, $year)
    {
        $monthName = Carbon::createFromFormat('m', $month)->translatedFormat('F');

        $target = TargetGrowth::whereRaw('LOWER(month) = ?', [strtolower($monthName)])
            ->where('year', $year)
            ->first();

        return [
            'month' => $monthName,
            'year' => $year,
            'target_growth' => $target?->target_growth ?? 0,
            'target_rkap' => $target?->target_rkap ?? 0,
        ];
    }


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

    private function calculateGimmick($ps_harian, $dayOfWeek)
    {
        $threshold = match ($dayOfWeek) {
            'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday' => 7,
            'Saturday' => 6,
            'Sunday' => 5,
            default => 7,
        };

        return $ps_harian >= $threshold ? 'Achieve' : 'Not Achieve';
    }


    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'ORDER_ID' => 'required|unique:data_ps_agustus_kujang_sql,ORDER_ID',
            'REGIONAL' => 'required|string|max:255',
            'WITEL' => 'nullable|string|max:100',
            'DATEL' => 'nullable|string|max:100',
            'STO' => 'nullable|string|max:10',
            'UNIT' => 'nullable|string|max:10',
            'JENISPSB' => 'nullable|string|max:50',
            'TYPE_TRANS' => 'nullable|string|max:50',
            'TYPE_LAYANAN' => 'nullable|string|max:50',
            'STATUS_RESUME' => 'nullable|string|max:255',
            'PROVIDER' => 'nullable|string|max:100',
            'ORDER_DATE' => 'nullable|date',
            'LAST_UPDATED_DATE' => 'nullable|date',
            'NCLI' => 'nullable|string|max:50',
            'POTS' => 'nullable|string|max:50',
            'SPEEDY' => 'nullable|string|max:50',
            'CUSTOMER_NAME' => 'nullable|string|max:255',
            'LOC_ID' => 'nullable|string|max:50',
            'WONUM' => 'nullable|string|max:50',
            'FLAG_DEPOSIT' => 'nullable|string|max:10',
            'CONTACT_HP' => 'nullable|string|max:20',
            'INS_ADDRESS' => 'nullable|string',
            'GPS_LONGITUDE' => 'nullable|string|max:50',
            'GPS_LATITUDE' => 'nullable|string|max:50',
            'KCONTACT' => 'nullable|string',
            'CHANNEL' => 'nullable|string|max:100',
            'STATUS_INET' => 'nullable|string|max:50',
            'STATUS_ONU' => 'nullable|string|max:50',
            'UPLOAD' => 'nullable|string|max:50',
            'DOWNLOAD' => 'nullable|string|max:50',
            'LAST_PROGRAM' => 'nullable|string|max:100',
            'STATUS_VOICE' => 'nullable|string|max:50',
            'CLID' => 'nullable|string|max:500',
            'LAST_START' => 'nullable|date',
            'TINDAK_LANJUT' => 'nullable|string',
            'ISI_COMMENT' => 'nullable|string',
            'USER_ID_TL' => 'nullable|string|max:50',
            'TGL_COMMENT' => 'nullable|date',
            'TANGGAL_MANJA' => 'nullable|date',
            'KELOMPOK_KENDALA' => 'nullable|string|max:100',
            'KELOMPOK_STATUS' => 'nullable|string|max:100',
            'HERO' => 'nullable|string|max:50',
            'ADDON' => 'nullable|string|max:50',
            'TGL_PS' => 'nullable|date',
            'STATUS_MESSAGE' => 'nullable|string|max:50',
            'PACKAGE_NAME' => 'nullable|string|max:100',
            'GROUP_PAKET' => 'nullable|string|max:100',
            'REASON_CANCEL' => 'nullable|string',
            'KETERANGAN_CANCEL' => 'nullable|string',
            'TGL_MANJA' => 'nullable|date',
            'DETAIL_MANJA' => 'nullable|string',
            'Bulan_PS' => 'nullable|string|max:50',
            'Kode_sales' => 'nullable|string|max:50',
            'Nama_SA' => 'nullable|string|max:255',
            'Mitra' => 'nullable|string|max:100',
            'Ekosistem' => 'nullable|string|max:100',
            // Include all other fields from your database table
        ]);

        // Mengecek apakah ORDER_ID sudah ada di database
        $existingOrder = DataPsAgustusKujangSql::where('ORDER_ID', $request->ORDER_ID)->first();
        if ($existingOrder) {
            return redirect()->back()->withErrors(['ORDER_ID' => 'ORDER_ID sudah digunakan.']);
        }

        // Simpan data ke database jika validasi lolos
        DataPsAgustusKujangSql::create($validatedData);

        // // Redirect ke halaman index atau halaman lain setelah penyimpanan
        // return redirect()->route('data-ps.index')->with('success', 'Data berhasil ditambahkan!');
    }

    public function edit($id)
    {
        $item = DataPsAgustusKujangSql::findOrFail($id);
        return view('data-ps.edit', compact('item'));
    }

    public function update(Request $request, $id)
    {
        $item = DataPsAgustusKujangSql::findOrFail($id);

        $validatedData = $request->validate([
            'ORDER_ID' => 'nullable|unique:data_ps_agustus_kujang_sql,ORDER_ID',
            'REGIONAL' => 'required|string|max:255',
            'WITEL' => 'nullable|string|max:100',
            'DATEL' => 'nullable|string|max:100',
            'STO' => 'nullable|string|max:10',
            'UNIT' => 'nullable|string|max:10',
            'JENISPSB' => 'nullable|string|max:50',
            'TYPE_TRANS' => 'nullable|string|max:50',
            'TYPE_LAYANAN' => 'nullable|string|max:50',
            'STATUS_RESUME' => 'nullable|string|max:255',
            'PROVIDER' => 'nullable|string|max:100',
            'ORDER_DATE' => 'nullable|date',
            'LAST_UPDATED_DATE' => 'nullable|date',
            'NCLI' => 'nullable|string|max:50',
            'POTS' => 'nullable|string|max:50',
            'SPEEDY' => 'nullable|string|max:50',
            'CUSTOMER_NAME' => 'nullable|string|max:255',
            'LOC_ID' => 'nullable|string|max:50',
            'WONUM' => 'nullable|string|max:50',
            'FLAG_DEPOSIT' => 'nullable|string|max:10',
            'CONTACT_HP' => 'nullable|string|max:20',
            'INS_ADDRESS' => 'nullable|string',
            'GPS_LONGITUDE' => 'nullable|string|max:50',
            'GPS_LATITUDE' => 'nullable|string|max:50',
            'KCONTACT' => 'nullable|string',
            'CHANNEL' => 'nullable|string|max:100',
            'STATUS_INET' => 'nullable|string|max:50',
            'STATUS_ONU' => 'nullable|string|max:50',
            'UPLOAD' => 'nullable|string|max:50',
            'DOWNLOAD' => 'nullable|string|max:50',
            'LAST_PROGRAM' => 'nullable|string|max:100',
            'STATUS_VOICE' => 'nullable|string|max:50',
            'CLID' => 'nullable|string|max:500',
            'LAST_START' => 'nullable|date',
            'TINDAK_LANJUT' => 'nullable|string',
            'ISI_COMMENT' => 'nullable|string',
            'USER_ID_TL' => 'nullable|string|max:50',
            'TGL_COMMENT' => 'nullable|date',
            'TANGGAL_MANJA' => 'nullable|date',
            'KELOMPOK_KENDALA' => 'nullable|string|max:100',
            'KELOMPOK_STATUS' => 'nullable|string|max:100',
            'HERO' => 'nullable|string|max:50',
            'ADDON' => 'nullable|string|max:50',
            'TGL_PS' => 'nullable|date',
            'STATUS_MESSAGE' => 'nullable|string|max:50',
            'PACKAGE_NAME' => 'nullable|string|max:100',
            'GROUP_PAKET' => 'nullable|string|max:100',
            'REASON_CANCEL' => 'nullable|string',
            'KETERANGAN_CANCEL' => 'nullable|string',
            'TGL_MANJA' => 'nullable|date',
            'DETAIL_MANJA' => 'nullable|string',
            'Bulan_PS' => 'nullable|string|max:50',
            'Kode_sales' => 'nullable|string|max:50',
            'Nama_SA' => 'nullable|string|max:255',
            'Mitra' => 'nullable|string|max:100',
            'Ekosistem' => 'nullable|string|max:100',
            // Include all other fields from your database table
        ]);
        $item->update($validatedData);
        // return redirect()->route('data-ps.index')->with('success', 'Data PS updated successfully.');
    }

    public function destroy($id)
    {
        $item = DataPsAgustusKujangSql::findOrFail($id);
        $item->delete();
        // return redirect()->route('data-ps.index')->with('success', 'Data PS deleted successfully.');
    }

    public function destroyAll()
    {
        DataPsAgustusKujangSql::truncate();
        return response()->json(['message' => 'Semua data PS telah dihapus dan ID telah direset.'], 200);
    }

    public function importExcel(Request $request)
    {
        if (!$request->hasFile('file')) {
            return $this->errorResponse('No file uploaded.', 422);
        }

        $file = $request->file('file');

        Log::info('File MIME type: ' . $file->getClientMimeType());
        Log::info('File extension: ' . $file->getClientOriginalExtension());

        try {
            DB::beginTransaction();

            $import = new DataPsImport;
            Excel::import($import, $file);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Data imported successfully!",
                'rows_imported' => $import->getRowCount()
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Excel Import Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error importing data: ' . $e->getMessage()
            ], 500);
        }
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
