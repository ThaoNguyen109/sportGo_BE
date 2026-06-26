<?php

namespace App\Http\Controllers;

use App\Services\AdminUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class AdminUserController extends Controller
{
    protected $adminUserService;

    public function __construct(AdminUserService $adminUserService)
    {
        $this->adminUserService = $adminUserService;
    }

    public function toggleStatus($id)
    {
        $result = $this->adminUserService->toggleStatus($id);

        return response()->json($result['data'], $result['status']);
    }

    public function getAllUsers(Request $request): JsonResponse
    {
        try {
            $result = $this->adminUserService->getAllUsers($request);

            return response()->json($result['data'], $result['status']);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Lỗi server',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}