<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function findByPhone(?string $phone): ?User
    {
        if (!$phone) {
            return null;
        }

        return User::where('phone', $phone)->first();
    }

    public function existsByEmail(string $email): bool
    {
        return User::where('email', $email)->exists();
    }

    public function existsByPhone(?string $phone): bool
    {
        if (!$phone) {
            return false;
        }

        return User::where('phone', $phone)->exists();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }
}