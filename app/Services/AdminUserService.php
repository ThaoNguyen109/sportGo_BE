<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;

class AdminUserService
{
    public function toggleStatus($id)
    {
        $admin = auth('api')->user();

        // check admin
        if ($admin->role !== 'admin') {
            return [
                'status' => 403,
                'data' => [
                    'message' => 'Không có quyền'
                ]
            ];
        }

        $user = User::find($id);

        if (!$user) {
            return [
                'status' => 404,
                'data' => [
                    'message' => 'Không tìm thấy user'
                ]
            ];
        }

        // không cho khóa chính mình
        if ($user->id === $admin->id) {
            return [
                'status' => 400,
                'data' => [
                    'message' => 'Không thể khóa chính mình'
                ]
            ];
        }

        $user->status = !$user->status;
        $user->save();

        return [
            'status' => 200,
            'data' => [
                'message' => $user->status
                    ? 'Mở khóa tài khoản thành công'
                    : 'Khóa tài khoản thành công',
                'user' => $user
            ]
        ];
    }

    public function getAllUsers(Request $request)
    {
        $admin = auth('api')->user();

        if ($admin->role !== 'admin') {
            return [
                'status' => 403,
                'data' => [
                    'message' => 'Không có quyền'
                ]
            ];
        }

        $perPage = (int) $request->query('per_page', 10);
        $keyword = $request->query('keyword');
        $status = $request->query('status');
        $role = $request->query('role');

        $query = User::select(['id', 'name', 'email', 'role', 'status', 'phone', 'avatar', 'created_at'])
            ->where('role', '!=', 'admin');

        if ($keyword) {
            $query->where(function ($subQuery) use ($keyword) {
                $subQuery->where('name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        if ($status !== null) {
            $query->where('status', $status);
        }

        if ($role) {
            $query->where('role', $role);
        }

        $users = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return [
            'status' => 200,
            'data' => $users
        ];
    }
}