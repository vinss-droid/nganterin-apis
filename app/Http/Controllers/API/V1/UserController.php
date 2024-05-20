<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\UserDetail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function profile()
    {
        try {
            $user = User::findOrFail(Auth::user()->id)
                ->leftJoin('user_details', 'users.id', '=', 'user_details.user_id')
                ->leftJoin('addresses', 'users.id', '=', 'addresses.user_id')
                ->select(
                    'users.name', 'users.email', 'users.profile_picture', 'users.email_verified_at',
                    'user_details.gender', 'user_details.phone_number',
                    'addresses.country', 'addresses.province', 'addresses.city', 'addresses.zip_code', 'addresses.complete_address'
                )->first();

            return response()->json([
                'status' => 'success',
                'data' => $user->makeHidden(['created_at', 'user_id', 'updated_at'])
            ], Response::HTTP_OK);
        } catch (\Exception $exception) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Error while processing data!',
                'errors' => $exception->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function createOrUpdateUserDetail(Request $request)
    {
        try {
            DB::beginTransaction();

            $validation = Validator::make($request->all(), [
               'gender' => 'required|in:male,female',
               'phone_number' => 'required|unique:user_details,phone_number,' . Auth::user()->id . ',user_id|min:10|max:13',
            ]);

            if ($validation->fails()) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Validation errors',
                    'errors' => $validation->errors()
                ], Response::HTTP_BAD_REQUEST);
            }

            UserDetail::updateOrCreate([
                'user_id' => Auth::user()->id,
            ],[
                'user_id' => Auth::user()->id,
                'gender' => $request->gender,
                'phone_number' => $request->phone_number
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'User detail saved.'
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

    public function updateUserAddress(Request $request)
    {
        try {
            DB::beginTransaction();

            $user_id = Auth::user()->id;

            $validation = Validator::make($request->all(), [
                'country' => 'required',
                'province' => 'required',
                'city' => 'required',
                'zip_code' => 'required',
                'complete_address' => 'required'
            ]);

            if ($validation->fails()) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Validation errors',
                    'errors' => $validation->errors()
                ], Response::HTTP_BAD_REQUEST);
            }

            $userAddress = Address::updateOrCreate([
                'user_id' => $user_id
            ],[
                'country' => $request->country,
                'province' => $request->province,
                'city' => $request->city,
                'zip_code' => $request->zip_code,
                'complete_address' => $request->complete_address
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Address saved.',
            ], Response::HTTP_CREATED);

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
