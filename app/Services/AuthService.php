<?php
<<<<<<< HEAD

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
=======
namespace App\Services;

class AuthService {

    public function login($email, $password) {

>>>>>>> origin/main
        $credentials = [
            'email' => $email,
            'password' => $password
        ];

<<<<<<< HEAD
        if (!$token = auth('api')->attempt($credentials)) {
=======
        // 🔥 JWT login
        if (!$token = auth()->attempt($credentials)) {
>>>>>>> origin/main
            return null;
        }

        return [
<<<<<<< HEAD
            'user' => auth('api')->user(),
=======
            'user' => auth()->user(),
>>>>>>> origin/main
            'token' => $token
        ];
    }
}