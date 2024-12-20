<?php

namespace App\Http\Controllers\Api\User;

use App\Models\SalesCodes;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class UserCodesDataController extends Controller
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

    public function show($id): JsonResponse
    {
        $salesCode = SalesCodes::findOrFail($id);
        return response()->json([
            'data' => $salesCode,
            'message' => 'Sales code retrieved successfully.'
        ]);
    }
}
