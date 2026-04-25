<?php
namespace App\Services;

class AuthService {

    public function login($email, $password) {

        $credentials = [
            'email' => $email,
            'password' => $password
        ];

        // 🔥 JWT login
        if (!$token = auth()->attempt($credentials)) {
            return null;
        }

        return [
            'user' => auth()->user(),
            'token' => $token
        ];
    }
}