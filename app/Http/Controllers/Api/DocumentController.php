<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteDocument;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DocumentController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        try {
            $siteId = $request->query('site_id') ?? $request->header('X-Site-Id');
            $query  = SiteDocument::with('uploader');

            if ($siteId) {
                $this->authorizeForSite($siteId);
                $query->where('site_id', $siteId);
            } else {
                $query->whereIn('site_id', $this->getUserSiteIds());
            }

            if ($request->filled('category')) {
                $query->where('category', $request->query('category'));
            }

            return $this->success($query->orderBy('created_at', 'desc')->get());
        } catch (\Throwable $e) {
            return $this->error('Failed to fetch documents: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'site_id'  => 'required|uuid|exists:sites,id',
                'file'     => 'required|file|max:51200',
                'name'     => 'nullable|string|max:255',
                'category' => 'nullable|string|max:100',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            $this->authorizeForSite($validated['site_id'], 'worker');

            $siteId = $validated['site_id'];
            $file   = $request->file('file');

            $path = $file->store("documents/{$siteId}", 'public');

            $document = SiteDocument::create([
                'site_id'      => $siteId,
                'uploaded_by'  => auth()->id(),
                'name'         => $validated['name'] ?? $file->getClientOriginalName(),
                'category'     => $validated['category'] ?? null,
                'storage_path' => $path,
                'file_size'    => $file->getSize(),
                'mime_type'    => $file->getMimeType(),
            ]);

            return $this->created($document->load('uploader'));
        } catch (\Throwable $e) {
            return $this->error('Failed to upload document: ' . $e->getMessage(), 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $document = SiteDocument::with('uploader')->findOrFail($id);
            $this->authorizeForSite($document->site_id);
            return $this->success($document);
        } catch (\Throwable $e) {
            return $this->error('Document not found', 404);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $document = SiteDocument::findOrFail($id);
            $this->authorizeForSite($document->site_id, 'site_manager');

            if ($document->storage_path && Storage::disk('public')->exists($document->storage_path)) {
                Storage::disk('public')->delete($document->storage_path);
            }

            $document->delete();

            return $this->success(['message' => 'Document deleted successfully']);
        } catch (\Throwable $e) {
            return $this->error('Failed to delete document: ' . $e->getMessage(), 500);
        }
    }
}
