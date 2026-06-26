<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AuthService;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }


    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20|unique:users,phone',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'nullable|in:user,owner',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validate lỗi',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->authService->register($request->only([
            'name',
            'email',
            'phone',
            'password',
            'role'
        ]));

        return response()->json([
            'message' => 'Đăng ký thành công',
            'data' => $result
        ], 201);
    }

    public function login(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email',
        'password' => 'required'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validate lỗi',
            'errors' => $validator->errors()
        ], 422);
    }

    $result = $this->authService->login(
        $request->email,
        $request->password
    );

    if (!$result['success']) {
        return response()->json([
            'message' => $result['message']
        ], $result['status']);
    }

    return response()->json([
        'user' => $result['user'],
        'token' => $result['token']
    ], 200);
}

    public function me()
    {
        return response()->json([
            'user' => auth('api')->user()
        ]);
    }
}