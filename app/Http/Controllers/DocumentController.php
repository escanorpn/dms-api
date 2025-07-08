<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class DocumentController extends Controller
{
    protected $dmsPath;

    public function __construct()
    {
        $this->dmsPath = public_path('dms');

        // Ensure the folder exists
        if (!File::exists($this->dmsPath)) {
            File::makeDirectory($this->dmsPath, 0775, true);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_number' => 'required|string|unique:documents,id_number',
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);
         

        if ($validator->fails()) {
            return response()->json(['error' => true, 'messages' => $validator->errors()], 422);
        }

        $file = $request->file('document');
           if (!$file->isValid()) {
                return response()->json(['error' => 'Uploaded file is invalid.'], 422);
            }
            
            $storedName = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $file->move($this->dmsPath, $storedName);

            // Use the moved file to get size
            $fullPath = $this->dmsPath . '/' . $storedName;

            $document = Document::create([
                'id_number' => $request->id_number,
                'filename' => $storedName,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => filesize($fullPath),
            ]);


        return response()->json([
            'message' => 'Document uploaded',
            'data' => [
                'id_number' => $document->id_number,
                'original_name' => $document->original_name,
                'url' => url('dms/' . $document->filename),
                'mime_type' => $document->mime_type,
              'size' => filesize($fullPath),
            ]
        ]);
    }

public function update1(Request $request, $id_number)
{
    $document = Document::where('id_number', $id_number)->first();
    if (!$document) {
        return response()->json(['error' => 'Document not found'], 404);
    }

    // Ensure a file was uploaded
    if (!$request->hasFile('document')) {
        return response()->json([
            'error' => 'No document file uploaded.',
            'code' => 'FILE_MISSING'
        ], 422);
    }

    $file = $request->file('document');

    // Now safe to check if file is valid
    if (!$file->isValid()) {
        return response()->json([
            'error' => 'Uploaded file is not valid.',
            'code' => 'FILE_INVALID'
        ], 422);
    }

    // Validate file type
    $allowedMimeTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
    if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
        return response()->json([
            'error' => 'Only PDF, JPG, JPEG, PNG files are allowed.',
            'code' => 'INVALID_FILE_TYPE'
        ], 422);
    }

    // Validate file size (max 5MB = 5 * 1024 * 1024 bytes)
    $maxSizeInBytes = 5 * 1024 * 1024;
    if ($file->getSize() > $maxSizeInBytes) {
        return response()->json([
            'error' => 'File exceeds maximum size of 5MB.',
            'code' => 'FILE_TOO_LARGE'
        ], 422);
    }

    // Delete old file if it exists
    $oldPath = $this->dmsPath . '/' . $document->filename;
    if (file_exists($oldPath)) {
        unlink($oldPath);
    }

    // Store new file
    $storedName = Str::uuid() . '.' . $file->getClientOriginalExtension();
    $file->move($this->dmsPath, $storedName);
    $fullPath = $this->dmsPath . '/' . $storedName;
    // Update document
    $document->update([
        'filename' => $storedName,
        'original_name' => $file->getClientOriginalName(),
        'mime_type' => $file->getClientMimeType(),
       'size' => filesize($fullPath),
    ]);

    return response()->json([
        'message' => 'Document updated',
        'data' => [
            'id_number' => $document->id_number,
            'original_name' => $document->original_name,
            'url' => url('dms/' . $document->filename),
            'mime_type' => $document->mime_type,
            'size' => filesize($fullPath)
        ]
    ]);
}


    public function show($id_number)
    {
        $document = Document::where('id_number', $id_number)->first();
        if (!$document) {
            return response()->json(['error' => 'Document not found'], 404);
        }

        return response()->json([
            'id_number' => $document->id_number,
            'original_name' => $document->original_name,
            'url' => url('dms/' . $document->filename),
            'mime_type' => $document->mime_type,
            'size' => $document->size
        ]);
    }

// Inside DocumentController.php
public function update(Request $request, $id_number)
{
    $document = Document::where('id_number', $id_number)->first();
    if (!$document) {
        return response()->json(['error' => 'Document not found'], 404);
    }

    // Validate optional id_number change
    $newIdNumber = $request->input('id_number');
    if ($newIdNumber && $newIdNumber !== $document->id_number) {
        $exists = Document::where('id_number', $newIdNumber)->exists();
        if ($exists) {
            return response()->json([
                'error' => 'ID number already used.',
                'code' => 'ID_DUPLICATE'
            ], 422);
        }
    }

    if ($request->hasFile('document')) {
        $file = $request->file('document');

        if (!$file->isValid()) {
            return response()->json([
                'error' => 'Uploaded file is not valid.',
                'code' => 'FILE_INVALID'
            ], 422);
        }

        $allowedMimeTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            return response()->json([
                'error' => 'Only PDF, JPG, JPEG, PNG files are allowed.',
                'code' => 'INVALID_FILE_TYPE'
            ], 422);
        }

        $maxSizeInBytes = 5 * 1024 * 1024;
        if ($file->getSize() > $maxSizeInBytes) {
            return response()->json([
                'error' => 'File exceeds maximum size of 5MB.',
                'code' => 'FILE_TOO_LARGE'
            ], 422);
        }

        // Delete old file
        $oldPath = $this->dmsPath . '/' . $document->filename;
        if (file_exists($oldPath)) {
            unlink($oldPath);
        }

        $storedName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $file->move($this->dmsPath, $storedName);
        $fullPath = $this->dmsPath . '/' . $storedName;

        // Update fields with file
        $document->filename = $storedName;
        $document->original_name = $file->getClientOriginalName();
        $document->mime_type = $file->getClientMimeType();
        $document->size = filesize($fullPath);
    }

    // Update id_number if provided and changed
    if ($newIdNumber && $newIdNumber !== $document->id_number) {
        $document->id_number = $newIdNumber;
    }

    $document->save();

    return response()->json([
        'message' => 'Document updated',
        'data' => [
            'id_number' => $document->id_number,
            'original_name' => $document->original_name,
            'url' => url('dms/' . $document->filename),
            'mime_type' => $document->mime_type,
            'size' => $document->size
        ]
    ]);
}

public function destroy($id_number)
{
    $document = Document::where('id_number', $id_number)->first();
    if (!$document) {
        return response()->json(['error' => 'Document not found'], 404);
    }

    $path = $this->dmsPath . '/' . $document->filename;
    if (file_exists($path)) {
        unlink($path);
    }

    $document->delete();

    return response()->json(['message' => 'Document deleted successfully']);
}

}
