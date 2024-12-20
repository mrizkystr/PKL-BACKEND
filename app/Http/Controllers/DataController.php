<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\DataResource; // Importing the resource class
use App\Http\Controllers\Controller;

class DataController extends Controller
{
    // Method to display data with pagination
    public function index(Request $request)
    {
        // Retrieve data with specified columns and paginate
        $data = $this->getPaginatedData(10);

        // Return a paginated response with data
        return response()->json([
            'data' => DataResource::collection($data),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
            ]
        ]);
    }

    // Helper method to retrieve paginated data
    protected function getPaginatedData(int $perPage)
    {
        return DB::table('data_ps_agustus_kujang_sql')
            ->select('id', 'ORDER_ID', 'REGIONAL', 'WITEL', 'DATEL', 'STO')
            ->paginate($perPage); // Ensure you're using paginate
    }

    // Method to display details of a record based on ID
    public function showDetails($id)
    {
        // Retrieve detail data based on the provided ID
        $data = DB::table('data_ps_agustus_kujang_sql')->where('id', $id)->first();

        // Check if the data was found
        if ($data) {
            return response()->json(new DataResource($data)); // Return the data as a resource
        }

        // Return a JSON response if data is not found
        return response()->json(['error' => 'Data not found'], 404);
    }
}