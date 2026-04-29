<?php
<<<<<<< HEAD

=======
>>>>>>> origin/main
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AuthService;

<<<<<<< HEAD
class AuthController extends Controller
{
=======
class AuthController extends Controller {

>>>>>>> origin/main
    protected $authService;

    public function __construct(AuthService $authService) {
        $this->authService = $authService;
    }

<<<<<<< HEAD
    public function register(Request $request) {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20|unique:users,phone',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $result = $this->authService->register($request->only([
            'name',
            'email',
            'phone',
            'password'
        ]));

        return response()->json([
            'message' => 'Đăng ký thành công',
            'data' => $result
        ], 201);
    }

    public function login(Request $request) {
=======
    public function login(Request $request) {

>>>>>>> origin/main
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
<<<<<<< HEAD
    public function me()
{
    return response()->json([
        'user' => auth('api')->user()
    ]);
}
=======
>>>>>>> origin/main
}