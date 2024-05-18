<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PartnerController extends Controller
{

    public function getPartner()
    {
        try {

            if (is_null($this->whoIsRegistered()->id)) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'You not registered as partner',
                ], Response::HTTP_FORBIDDEN);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Partner find successfully',
                'data' => $this->whoIsRegistered()
            ], Response::HTTP_OK);
        } catch (\Exception $exception) {
            return response()->json([
                'status' => 'failed',
                'message' => $exception->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function registerPartners(Request $request)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'company_name' => 'required|unique:partners,company_name|',
                'owner' => 'required',
                'company_field' => 'required|' . Rule::in(['hotels', 'flights']),
                'company_email' => 'required|unique:partners,company_email',
                'company_address' => 'required',
                'legality_file' => 'required|url:http,https',
                'mou_file' => 'required|url:http,https',
            ]);

            if (!is_null(Auth::user()->partner_id)) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'You have registered to become a partner',
                ], Response::HTTP_NOT_ACCEPTABLE);
            }

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'validation errors',
                    'errors' => $validator->errors()
                ], Response::HTTP_BAD_REQUEST);
            }

            $partner = Partner::updateOrCreate([
                'id' => Auth::user()->partner_id
            ],[
                'company_name' => $request->company_name,
                'owner' => $request->owner,
                'company_field' => $request->company_field,
                'company_email' => $request->company_email,
                'company_address' => $request->company_address,
                'legality_file' => $request->legality_file,
                'mou_file' => $request->mou_file,
            ]);

            User::findOrFail(Auth::user()->id)->update([
                'role' => 'partner',
                'partner_id' => $partner->id
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'partner created successfully',
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

    public function whoIsRegistered()
    {
        try {
            $data = User::leftJoin('partners', 'partners.id', '=', 'users.partner_id')
                ->where('users.id', Auth::user()->id)
                ->select('users.name AS partner_registered_by', 'partners.*')
                ->first()
                ->makeHidden(['updated_at', 'deleted_at', 'partners.id']);

            return $data;
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }
}
