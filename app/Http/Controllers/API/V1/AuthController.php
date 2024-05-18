<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Mail\SendCodeVerifyMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function emailLogin(Request $request) {
        try {

            $validation = Validator::make($request->all(), [
                'username' => 'required',
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
                    ->select('name', 'email', 'profile_picture', 'role', 'password', 'username', 'id', 'email_verified_at')
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
                'data' => $user->makeHidden(['password', 'username', 'id']),
                'token' => $user->createToken($user->id, ['*'], now()->addDays(3))->plainTextToken,
                'token_expiration' => now()->addDays(3)
            ]);

        } catch (\Exception $exception) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Error while processing data!',
                'errors' => $exception->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function emailRegister(Request $request)
    {
        try {
            DB::beginTransaction();
            $validation = Validator::make($request->all(), [
                'username' => 'required|unique:users,username',
                'email' => 'required|email|unique:users,email',
                'name' => 'required',
                'password' => 'required|min:6|confirmed',
            ]);

            if ($validation->fails()) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'validation errors',
                    'errors' => $validation->errors()
                ], Response::HTTP_BAD_REQUEST);
            }

            $user = User::create([
                'name' => ucwords($request->name),
                'email' => $request->email,
                'username' => $request->username,
                'password' => Hash::make($request->password),
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Register successfully',
                'data' =>
                    User::findOrFail($user->id)
                        ->select('name', 'profile_picture', 'role', 'email', 'email_verified_at')->first(),
                'token' => $user->createToken($user->id, ['*'], now()->addDays(3))->plainTextToken,
                'token_expiration' => now()->addDays(3)
            ], Response::HTTP_CREATED);
        } catch (\Exception $exception) {
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
                'email' => 'required',
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
                'username' => $request->email,
                'email' => $request->email,
                'profile_picture' => $request->profile_picture,
                'password' => Hash::make('google_login_' . $request->username . '_' . now()),
                'email_verified_at' => Date::now('Asia/Jakarta')->format('Y-m-d H:i:s'),
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'login successfully',
                'data' =>
                    User::leftJoin('partners', 'users.partner_id', '=', 'partners.id')
                        ->select(
                            'name', 'profile_picture', 'role', 'email', 'email_verified_at',
                            'users.partner_id', 'partners.verified_at AS partner_verified_at',
                            'partners.company_field'
                        )
                        ->findOrFail($user->id),
                'token' => $user->createToken($user->id, ['*'], now()->addDays(3))->plainTextToken,
                'token_expiration' => now()->addDays(3)
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

    public function logout()
    {
        try {
            Auth::user()->currentAccessToken()->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'logout successfully',
            ], Response::HTTP_OK);
        } catch (\Exception $exception) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Error while processing data!',
                'errors' => $exception->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function emailVerifyNotification(Request $request)
    {
        try {

            $userData = User::findOrFail(Auth::user()->id);

            if (!is_null($userData->email_verified_at)) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Email has been verified.',
                ], Response::HTTP_NOT_ACCEPTABLE);
            }

            $mailData = [
                'name' => $userData->name,
                'code' => 123,
            ];

            Mail::to($userData->email)
                ->send(new SendCodeVerifyMail($mailData));

            return response()->json([
                'status' => 'success',
                'message' => 'Email verification sent!',
            ], Response::HTTP_OK);
        } catch (\Exception $exception) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Error while processing data!',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
