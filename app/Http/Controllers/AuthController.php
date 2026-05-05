<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Models\User;

class AuthController extends Controller {

    protected $authService;

    public function __construct(AuthService $authService) {
        $this->authService = $authService;
    }

    public function login(Request $request) {

        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $result = $this->authService->login(
            $request->email,
            $request->password
        );

        if (!$result) {
            return response()->json([
                'message' => 'Sai tài khoản hoặc mật khẩu'
            ], 401);
        }

        return response()->json($result);
    }

    public function fakeLogin(Request $request)
    {
        if (!app()->environment('local') && !env('ENABLE_TEST_LOGIN', false)) {
            return response()->json(['message' => 'Fake login disabled'], 403);
        }

        $validated = $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
            'email'   => 'nullable|email|exists:users,email',
        ]);

        if (empty($validated['user_id']) && empty($validated['email'])) {
            return response()->json([
                'message' => 'user_id hoặc email là bắt buộc để fake login'
            ], 422);
        }

        $user = $validated['user_id']
            ? User::find($validated['user_id'])
            : User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json(['message' => 'Không tìm thấy user'], 404);
        }

        $token = auth()->login($user);

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ]);
    }
}