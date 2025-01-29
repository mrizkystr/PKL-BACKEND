<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\User;
use App\Imports\UsersImport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            // Retrieve all users with their roles
            $users = User::with('roles')->get();

            return response()->json([
                'success' => true,
                'message' => 'Users retrieved successfully!',
                'users' => $users, // Key matches frontend expectations
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve users.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified user by ID.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            // Find the user by ID with roles
            $user = User::with('roles')->find($id);

            if (!$user) {
                return $this->errorResponse('User not found.', 404);
            }

            return $this->successResponse('User retrieved successfully!', $user);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve user.', 500, $e->getMessage());
        }
    }

    /**
     * Register a new user by admin.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validate input data
            $validated = $this->validateUser($request);

            // Log input data for debugging
            Log::info('User data received:', $validated);

            // Hash the password
            $validated['password'] = Hash::make($validated['password']);

            // Determine role
            $roleName = $request->input('role', 'sales');
            $role = Role::where('name', $roleName)->where('guard_name', 'api')->first();

            if (!$role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or not found role for guard `api`!',
                ], 400);
            }

            // Create user
            $user = User::create($validated);
            $user->assignRole($roleName);

            return $this->successResponse('User successfully added!', $user);
        } catch (\Exception $e) {
            Log::error('Error creating user:', ['message' => $e->getMessage()]); // Log detailed error
            return $this->errorResponse('Failed to create user.', 500, $e->getMessage());
        }
    }

    /**
     * Update an existing user.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        // Find user by ID
        $user = User::find($id);

        if (!$user) {
            return $this->errorResponse('User not found.', 404);
        }

        // Validate input data
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'username' => 'sometimes|string|unique:users,username,' . $id,
            'password' => 'sometimes|string|min:6',
            'role' => 'sometimes|in:admin,sales,user',
        ]);

        // Hash the password if provided
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        // Update role if provided
        if (isset($validated['role'])) {
            $role = Role::where('name', $validated['role'])->where('guard_name', 'api')->first();

            if (!$role) {
                return $this->errorResponse('Invalid or not found role for guard `api`!', 400);
            }

            // Remove old roles and assign new role
            $user->syncRoles([$validated['role']]);
        }

        try {
            $user->update($validated);

            return $this->successResponse('User successfully updated!', $user);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update user.', 500, $e->getMessage());
        }
    }

    /**
     * Delete a user.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        // Find user by ID
        $user = User::find($id);

        if (!$user) {
            return $this->errorResponse('User not found.', 404);
        }

        try {
            $user->delete();
            return $this->successResponse('User successfully deleted!');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete user.', 500, $e->getMessage());
        }
    }


    /**
     * Import users from an Excel file.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function import(Request $request): JsonResponse
    {
        $this->validateImportRequest($request);

        try {
            Excel::import(new UsersImport, $request->file('file'));

            return $this->successResponse('Users data imported successfully!');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to import data.', 500, $e->getMessage());
        }
    }

    /**
     * Validate user registration data.
     *
     * @param Request $request
     * @return array
     */
    protected function validateUser(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'username' => 'required|string|unique:users,username|max:255',
            'password' => 'required|string|min:6',
            'role' => 'sometimes|in:admin,sales,user',
        ]);
    }

    /**
     * Validate the import request.
     *
     * @param Request $request
     * @return void
     */
    protected function validateImportRequest(Request $request): void
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
        ]);
    }

    /**
     * Return a success response.
     *
     * @param string $message
     * @param mixed $data
     * @return JsonResponse
     */
    protected function successResponse(string $message, $data = null): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], 200);
    }

    /**
     * Return an error response.
     *
     * @param string $message
     * @param int $statusCode
     * @param string|null $errorDetails
     * @return JsonResponse
     */
    protected function errorResponse(string $message, int $statusCode, ?string $errorDetails = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => $errorDetails,
        ], $statusCode);
    }
}
