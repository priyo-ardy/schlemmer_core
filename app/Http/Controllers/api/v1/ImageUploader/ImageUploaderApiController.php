<?php

namespace App\Http\Controllers\api\v1\ImageUploader;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ImageUploaderApiController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048'
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('pfmea-image', 'public');

            return response()->json([
                'url' => asset('storage/' . $path)
            ]);
        }

        return response()->json([
            'message' => "Failed to upload image"
        ], 400);
    }
}
