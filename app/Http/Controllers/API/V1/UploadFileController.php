<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\UploadFile;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UploadFileController extends Controller
{
    public function getFile($id)
    {
        try {
            $fileData = UploadFile::where('file_url', $this->fileUrl($id))->first();

            if (!$fileData) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'File not found'
                ], Response::HTTP_NOT_FOUND);
            }

            if ($this->allowedFileType($fileData->file_extension)) {
                return response()->file($fileData->file_path, [
                    'content-type' => 'image/' .$fileData->file_extension,
                    'content-length' => $fileData->file_size,
                    'content-disposition' => 'inline; filename="' . basename($fileData->file_name) . '"',
                ]);
            }

            return response()->file($fileData->file_path, [
                'content-type' => 'application/' . $fileData->file_extension,
                'content-length' => $fileData->file_size,
                'content-disposition' => 'inline; filename="' . basename($fileData->file_name) . '"',
            ]);

        } catch (\Exception $exception) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Failed to load files',
                'error' => $exception->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function uploadFile(Request $request)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'file' => 'required|file|extensions:jpeg,jpg,png,pdf|max:5120',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'validation errors',
                    'errors' => $validator->errors()
                ], Response::HTTP_BAD_REQUEST);
            }

            $file = $request->file('file');
            $fileName = Str::uuid() . '_' . $file->getClientOriginalName();

            if (!File::exists($this->userPath())) {
                File::makeDirectory($this->userPath(), 0755, true);
            }

            $file->move($this->userPath(), $fileName);

            $uploadedFile = UploadFile::create([
                'user_id' => Auth::user()->id,
                'file_name' => $fileName,
                'file_extension' => $file->getClientOriginalExtension(),
                'file_size' => File::size($this->userPath() . '/' .$fileName),
                'file_type' => $this->allowedFileType($file->getClientOriginalExtension()) ? 'image' : 'pdf',
                'file_path' => $this->userPath() . '/' . $fileName,
                'file_url' => $this->fileUrl(Str::uuid() . '-' . date('ymdhisv')),
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'File uploaded successfully',
                'data' => $uploadedFile->file_url
            ], Response::HTTP_OK);
        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json([
                'status' => 'failed',
                'message' => 'Error while uploading file',
                'errors' => $exception->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function multiUploadFile(Request $request)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'files' => 'required|array',
                'files.*' => 'file|mimes:jpeg,jpg,png,pdf|max:5120',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'validation errors',
                    'errors' => $validator->errors()
                ], Response::HTTP_BAD_REQUEST);
            }

            $files = $request->file('files');
            $responseURL = [];

            foreach ($files as $file) {
                $fileName = Str::uuid() . '_' . $file->getClientOriginalName();

                if (!File::exists($this->userPath())) {
                    File::makeDirectory($this->userPath(), 0755, true);
                }

                $file->move($this->userPath(), $fileName);

                $uploadedFile = UploadFile::create([
                    'user_id' => Auth::user()->id,
                    'file_name' => $fileName,
                    'file_extension' => $file->getClientOriginalExtension(),
                    'file_size' => File::size($this->userPath() . '/' .$fileName),
                    'file_type' => $this->allowedFileType($file->getClientOriginalExtension()) ? 'image' : 'pdf',
                    'file_path' => $this->userPath() . '/' . $fileName,
                    'file_url' => $this->fileUrl(Str::uuid() . '-' . date('ymdhisv')),
                ]);
                $responseURL[] = $uploadedFile->file_url;
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'File uploaded successfully',
                'data' => $responseURL
            ], Response::HTTP_OK);
        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json([
                'status' => 'failed',
                'message' => 'Error while uploading file',
                'errors' => $exception->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function userPath()
    {
        return storage_path("app/public/files/" . Auth::user()->id);
    }

    private function allowedFileType($type)
    {
        $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf'];
        return in_array($type, $allowedTypes);
    }

    private function fileUrl($uuid)
    {
        return url('/api/v1/files') . '/' . $uuid;
    }
}
