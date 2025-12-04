<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Teacher\StoreTagRequest;
use App\Models\ContentTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagController extends Controller
{
    /**
     * List all tags.
     */
    public function index(Request $request): JsonResponse
    {
        $tags = ContentTag::orderBy('name')->get();

        return response()->json([
            'data' => $tags,
        ]);
    }

    /**
     * Store a new tag.
     */
    public function store(StoreTagRequest $request): JsonResponse
    {
        $tag = ContentTag::create([
            'name' => $request->name,
        ]);

        return response()->json([
            'message' => 'Tag created successfully!',
            'data' => $tag,
        ], 201);
    }
}
