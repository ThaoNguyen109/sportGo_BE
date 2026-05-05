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
            'token' => $token
        ];
    }
}