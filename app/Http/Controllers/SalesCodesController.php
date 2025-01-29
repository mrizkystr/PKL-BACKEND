<?php

namespace App\Http\Controllers;

use App\Models\SalesCodes;
use App\Http\Requests\SalesCodeRequest;
use App\Http\Requests\SalesCodeImportRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\SalesCodesImport;

class SalesCodesController extends Controller
{
    public function index(): JsonResponse
    {
        $salesCodes = SalesCodes::paginate(10); // Use paginate instead of simplePaginate
        return response()->json([
            'data' => $salesCodes->items(), // Extract the items from the paginated result
            'links' => [
                'first' => $salesCodes->url(1),
                'last' => $salesCodes->url($salesCodes->lastPage()),
                'prev' => $salesCodes->previousPageUrl(),
                'next' => $salesCodes->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $salesCodes->currentPage(),
                'last_page' => $salesCodes->lastPage(),
                'per_page' => $salesCodes->perPage(),
                'total' => $salesCodes->total(),
            ],
            'message' => 'Sales codes retrieved successfully.'
        ]);
    }


    public function store(SalesCodeRequest $request): JsonResponse
    {
        try {
            // Log data yang diterima untuk debugging
            Log::info('Data received for sales code:', $request->validated());

            // Membuat sales code baru
            $salesCode = SalesCodes::create($request->validated());

            // Log hasil setelah penyimpanan
            Log::info('Sales code created:', $salesCode->toArray());

            return response()->json([
                'status' => 'success',
                'data' => $salesCode,
                'message' => 'Sales code successfully added!'
            ], 201);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error while adding sales code: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add sales code due to database error.',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            Log::error('Error while adding sales code: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add sales code.',
                'error' => 'An unexpected error occurred.'
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        $salesCode = SalesCodes::findOrFail($id);
        return response()->json([
            'data' => $salesCode,
            'message' => 'Sales code retrieved successfully.'
        ]);
    }

    public function update(SalesCodeRequest $request, $id): JsonResponse
    {
        $salesCode = SalesCodes::findOrFail($id);
        $salesCode->update($request->validated());
        return response()->json([
            'data' => $salesCode,
            'message' => 'Sales code updated successfully.'
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $salesCode = SalesCodes::findOrFail($id);
        $salesCode->delete();
        return response()->json([
            'message' => 'Sales code deleted successfully.'
        ]);
    }

    public function destroyAll(): JsonResponse
    {
        SalesCodes::truncate(); // Menghapus semua data dan mereset auto-increment
        return response()->json([
            'message' => 'All sales codes have been truncated successfully.'
        ]);
    }


    public function importExcel(SalesCodeImportRequest $request): JsonResponse
    {
        Log::info('Starting file upload process...');

        if (!$request->hasFile('file')) {
            return response()->json(['error' => 'No file uploaded.'], 422);
        }

        $file = $request->file('file');
        Log::info('File details:', [
            'name' => $file->getClientOriginalName(),
            'extension' => $file->getClientOriginalExtension(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        try {
            DB::beginTransaction();

            $import = new SalesCodesImport;
            Excel::import($import, $file);

            DB::commit();

            return response()->json([
                'message' => "Data imported successfully! Rows imported: " . $import->getRowCount(),
                'total_rows_processed' => $import->rows,
                'empty_rows_skipped' => $import->rows - $import->getRowCount(),
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error importing data: ' . $e->getMessage());
            return response()->json(['error' => 'Error importing data: ' . $e->getMessage()], 500);
        }
    }
}
