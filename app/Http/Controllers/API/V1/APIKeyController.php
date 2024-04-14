<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Ejarnutowski\LaravelApiKey\Models\ApiKey;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;

class APIKeyController extends Controller
{
    public function generateApiKey(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required|unique:api_keys,name'
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'validation errors',
                'errors' => $validation->errors()
            ], Response::HTTP_BAD_REQUEST);
        }

        $output = Artisan::call('apikey:generate', [
            'name' => $request->name
        ]);

        $output = str_replace("\r\n", "", Artisan::output());

        return response()->json([
            'status' => 'success',
            'message' => 'api key created successfully',
            'apiKey' => $output
        ], Response::HTTP_CREATED);

    }

    public function listApiKey()
    {
        $apiKeyList = ApiKey::orderBy('created_at', 'DESC')->get();

        return response()->json([
            'status' => 'success',
            'apiKey_list' => $apiKeyList
        ], Response::HTTP_OK);
    }

    public function deactivateApiKey($name)
    {
        $apiKey = ApiKey::where('name', $name)->first();

        if ($apiKey) {
            if (!$apiKey->active) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Key ' . $name . ' is already deactivated.'
                ], Response::HTTP_BAD_REQUEST);
            }

            ApiKey::findOrFail($apiKey->id)->update([
                'active' => 0
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Deactivated Key ' . $name . '.'
            ], Response::HTTP_OK);

        } else {
            return response()->json([
                'status' => 'failed',
                'message' => 'Key ' . $name . ' not found.'
            ], Response::HTTP_NOT_FOUND);
        }

    }

    public function activateApiKey($name)
    {
        $apiKey = ApiKey::where('name', $name)->first();

        if ($apiKey) {
            if ($apiKey->active) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Key ' . $name . ' is already activated.'
                ], Response::HTTP_BAD_REQUEST);
            }

            ApiKey::findOrFail($apiKey->id)->update([
                'active' => 1
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Activated Key ' . $name . '.'
            ], Response::HTTP_OK);

        } else {
            return response()->json([
                'status' => 'failed',
                'message' => 'Key ' . $name . ' not found.'
            ], Response::HTTP_NOT_FOUND);
        }
    }
}
