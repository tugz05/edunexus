<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Teacher\StoreContentRequest;
use App\Http\Requests\Api\Teacher\UpdateContentRequest;
use App\Http\Resources\ContentItemResource;
use App\Models\ContentItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ContentManagementController extends Controller
{
    /**
     * Get all content items created by the authenticated teacher.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = ContentItem::with(['creator', 'tags'])
            ->where('created_by', $user->id);

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('subject', 'like', '%' . $search . '%');
            });
        }

        // Optional filters
        if ($request->filled('subject')) {
            $query->where('subject', $request->subject);
        }

        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $contentItems = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => ContentItemResource::collection($contentItems),
            'meta' => [
                'current_page' => $contentItems->currentPage(),
                'last_page' => $contentItems->lastPage(),
                'per_page' => $contentItems->perPage(),
                'total' => $contentItems->total(),
            ],
        ]);
    }

    /**
     * Store a new content item.
     */
    public function store(StoreContentRequest $request): JsonResponse
    {
        $filePath = null;
        $url = $request->url;

        // Handle file upload for video, pdf, and Office document types
        if ($request->hasFile('file') && in_array($request->type, ['video', 'pdf', 'document', 'presentation', 'spreadsheet'])) {
            $file = $request->file('file');

            // Validate file type
            $allowedMimes = match ($request->type) {
                'video' => ['video/mp4', 'video/mpeg', 'video/quicktime', 'video/x-msvideo', 'video/webm'],
                'pdf' => ['application/pdf'],
                'document' => [
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ],
                'presentation' => [
                    'application/vnd.ms-powerpoint',
                    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                ],
                'spreadsheet' => [
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ],
                default => [],
            };

            if (!in_array($file->getMimeType(), $allowedMimes)) {
                return response()->json([
                    'message' => 'Invalid file type. Expected ' . $request->type . ' file.',
                    'errors' => ['file' => ['Invalid file type']],
                ], 422);
            }

            // Generate unique filename
            $filename = Str::slug($request->title) . '_' . time() . '.' . $file->getClientOriginalExtension();

            // Store file in public storage (grouped by content type)
            $filePath = $file->storeAs('content/' . $request->type, $filename, 'public');

            // Generate URL for the file
            $url = Storage::url($filePath);
        }

        $contentItem = ContentItem::create([
            'title' => $request->title,
            'description' => $request->description,
            'type' => $request->type,
            'url' => $url,
            'file_path' => $filePath,
            'subject' => $request->subject,
            'difficulty' => $request->difficulty,
            'created_by' => $request->user()->id,
        ]);

        // Attach tags if provided
        if ($request->has('tags')) {
            $tags = $request->input('tags', []);
            // Normalize tags to always be an array
            if (is_string($tags) && $tags === '[]') {
                $tags = [];
            } elseif (!is_array($tags)) {
                // If single tag is sent as string/integer, convert to array
                $tags = [$tags];
            }
            // Filter out empty values and ensure all are integers
            $tags = array_filter(array_map('intval', $tags));
            if (!empty($tags)) {
                $contentItem->tags()->attach($tags);
            }
        }

        $contentItem->load(['creator', 'tags']);

        return response()->json([
            'message' => 'Content item created successfully!',
            'data' => new ContentItemResource($contentItem),
        ], 201);
    }

    /**
     * Update a content item.
     */
    public function update(UpdateContentRequest $request, int $id): JsonResponse
    {
        $contentItem = ContentItem::findOrFail($id);

        $updateData = $request->only([
            'title',
            'description',
            'type',
            'url',
            'subject',
            'difficulty',
        ]);

        // Handle file upload for video, pdf, and Office document types
        if ($request->hasFile('file') && in_array($request->input('type', $contentItem->type), ['video', 'pdf', 'document', 'presentation', 'spreadsheet'])) {
            $file = $request->file('file');

            // Validate file type
            $contentType = $request->input('type', $contentItem->type);
            $allowedMimes = match ($contentType) {
                'video' => ['video/mp4', 'video/mpeg', 'video/quicktime', 'video/x-msvideo', 'video/webm'],
                'pdf' => ['application/pdf'],
                'document' => [
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ],
                'presentation' => [
                    'application/vnd.ms-powerpoint',
                    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                ],
                'spreadsheet' => [
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ],
                default => [],
            };

            if (!in_array($file->getMimeType(), $allowedMimes)) {
                return response()->json([
                    'message' => 'Invalid file type. Expected ' . $contentType . ' file.',
                    'errors' => ['file' => ['Invalid file type']],
                ], 422);
            }

            // Delete old file if exists
            if ($contentItem->file_path && Storage::disk('public')->exists($contentItem->file_path)) {
                Storage::disk('public')->delete($contentItem->file_path);
            }

            // Generate unique filename
            $filename = Str::slug($request->input('title', $contentItem->title)) . '_' . time() . '.' . $file->getClientOriginalExtension();

            // Store file in public storage (grouped by content type)
            $filePath = $file->storeAs('content/' . $contentType, $filename, 'public');

            // Generate URL for the file
            $updateData['file_path'] = $filePath;
            $updateData['url'] = Storage::url($filePath);
        }

        $contentItem->update($updateData);

        // Sync tags if provided
        if ($request->has('tags')) {
            $tags = $request->input('tags', []);
            // Normalize tags to always be an array
            if (is_string($tags) && $tags === '[]') {
                $tags = [];
            } elseif (!is_array($tags)) {
                // If single tag is sent as string/integer, convert to array
                $tags = [$tags];
            }
            // Filter out empty values and ensure all are integers
            $tags = array_filter(array_map('intval', $tags));
            $contentItem->tags()->sync($tags);
        }

        $contentItem->load(['creator', 'tags']);

        return response()->json([
            'message' => 'Content item updated successfully!',
            'data' => new ContentItemResource($contentItem),
        ]);
    }

    /**
     * Delete a content item.
     */
    public function destroy(int $id): JsonResponse
    {
        $contentItem = ContentItem::findOrFail($id);

        // Delete associated file if exists
        if ($contentItem->file_path && Storage::disk('public')->exists($contentItem->file_path)) {
            Storage::disk('public')->delete($contentItem->file_path);
        }

        $contentItem->delete();

        return response()->json([
            'message' => 'Content item deleted successfully!',
        ]);
    }
}
