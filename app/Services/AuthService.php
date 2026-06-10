<?php

namespace App\Services;

use App\Models\User;

class AuthService
{
    public function register(array $data)
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'role' => 'user',
            'status' => true,
            'avatar' => null,
        ]);

        $token = auth('api')->login($user);

        return [
            'user' => $user,
            'token' => $token
        ];
    }

    public function login($email, $password)
    {
        $credentials = [
            'email' => $email,
            'password' => $password
        ];

        // JWT login
        if (!$token = auth('api')->attempt($credentials)) {
            return null;
        }

        return [
            'user' => auth('api')->user(),

        return [
            'user' => $user,
            'token' => $token
        ];
    }

    public function login($email, $password)
{
    $user = User::where('email', $email)->first();

    // không tồn tại email
    if (!$user) {
        return [
            'success' => false,
            'status' => 401,
            'message' => 'Sai tài khoản hoặc mật khẩu'
        ];
    }

    // tài khoản bị khóa
    if (!$user->status) {
        return [
            'success' => false,
            'status' => 403,
            'message' => 'Tài khoản đã bị khóa'
        ];
    }

    $credentials = [
        'email' => $email,
        'password' => $password
    ];

    if (!$token = auth('api')->attempt($credentials)) {
        return [
            'success' => false,
            'status' => 401,
            'message' => 'Sai tài khoản hoặc mật khẩu'
        ];
    }

    return [
        'success' => true,
        'status' => 200,
        'user' => auth('api')->user(),
        'token' => $token
    ];
}

    /**
     * Đăng xuất người dùng (thu hồi token JWT hiện tại)
     */
    public function logout()
    {
        auth('api')->logout();
    }

    /**
     * Làm mới token JWT
     */
    public function refresh()
    {
        return auth('api')->refresh();
    }
}