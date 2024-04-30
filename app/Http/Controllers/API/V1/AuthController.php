<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function emailLogin(Request $request) {
        try {
            DB::beginTransaction();

            $validation = Validator::make($request->all(), [
                'username' => 'required|exists:users,username|exists:users,email',
                'password' => 'required',
            ]);

            if ($validation->fails()) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'validation errors',
                    'errors' => $validation->errors()
                ], Response::HTTP_BAD_REQUEST);
            }

            $user = User::where('email', $request->username)
                    ->orWhere('username', $request->username)
                    ->select('name', 'email', 'profile_picture', 'role', 'password')
                    ->first();

            if ($user == null) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'user not found',
                ], Response::HTTP_NOT_FOUND);
            }

            if (!password_verify($request->password, $user->password)) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'wrong password',
                ], Response::HTTP_BAD_REQUEST);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'login successfully',
                'data' => $user->makeHidden(['password']),
                'token' => $user->createToken($user->username, ['*'], now()->addDays(3))->plainTextToken,
                'token_expiration' => now()->addDays(3)->format('i')
            ]);

        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json([
                'status' => 'failed',
                'message' => 'Error while processing data!',
                'errors' => $exception->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function googleLoginRegister(Request $request)
    {
        try {
            DB::beginTransaction();

            $validation = Validator::make($request->all(), [
                'name' => 'required',
                'email' => 'required|exists:users,email',
                'profile_picture' => 'required|url:http,https',
            ]);

            if ($validation->fails()) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'validation errors',
                    'errors' => $validation->errors()
                ], Response::HTTP_BAD_REQUEST);
            }

            $user = User::updateOrCreate([
               'email' => $request->email,
            ],[
                'name' => ucwords(strtolower($request->name)),
                'username' => $request->username,
                'email' => $request->email,
                'profile_picture' => $request->profile_picture,
                'password' => Hash::make('google_login_' . $request->username . '_' . now())
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'login successfully',
                'data' =>
                    User::findOrFail($user->id)
                        ->makeHidden(['password', 'created_at', 'updated_at', 'deleted_at']),
                'token' => $user->createToken($user->username, ['*'], now()->addDays(3))->plainTextToken,
                'token_expiration' => now()->addDays(3)->format('i')
            ], Response::HTTP_OK);
        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json([
                'status' => 'failed',
                'message' => 'Error while processing data!',
                'errors' => $exception->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
