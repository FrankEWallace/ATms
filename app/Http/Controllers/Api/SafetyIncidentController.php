<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SafetyIncident;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SafetyIncidentController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        try {
            $siteId = $request->query('site_id') ?? $request->header('X-Site-Id');
            $query  = SafetyIncident::with('reporter');

            if ($siteId) {
                $this->authorizeForSite($siteId);
                $query->where('site_id', $siteId);
            } else {
                $query->whereIn('site_id', $this->getUserSiteIds());
            }

            if ($request->filled('severity')) {
                $query->where('severity', $request->query('severity'));
            }

            if ($request->filled('type')) {
                $query->where('type', $request->query('type'));
            }

            if ($request->boolean('unresolved')) {
                $query->whereNull('resolved_at');
            }

            return $this->success($query->orderBy('created_at', 'desc')->get());
        } catch (\Throwable $e) {
            return $this->error('Failed to fetch safety incidents: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'site_id'       => 'required|uuid|exists:sites,id',
                'severity'      => 'nullable|in:low,medium,high,critical',
                'type'          => 'nullable|string|max:50',
                'title'         => 'required|string|max:255',
                'description'   => 'nullable|string',
                'actions_taken' => 'nullable|string',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            // Any worker or above can report a safety incident — safety first.
            $this->authorizeForSite($validated['site_id'], 'worker');

            $validated['reported_by'] = auth()->id();
            $incident = SafetyIncident::create($validated);
            return $this->created($incident->load('reporter'));
        } catch (\Throwable $e) {
            return $this->error('Failed to create safety incident: ' . $e->getMessage(), 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $incident = SafetyIncident::with('reporter')->findOrFail($id);
            $this->authorizeForSite($incident->site_id);
            return $this->success($incident);
        } catch (\Throwable $e) {
            return $this->error('Safety incident not found', 404);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $incident = SafetyIncident::findOrFail($id);
        } catch (\Throwable $e) {
            return $this->error('Safety incident not found', 404);
        }

        $this->authorizeForSite($incident->site_id, 'site_manager');

        try {
            $validated = $request->validate([
                'severity'      => 'nullable|in:low,medium,high,critical',
                'type'          => 'nullable|string|max:50',
                'title'         => 'sometimes|string|max:255',
                'description'   => 'nullable|string',
                'actions_taken' => 'nullable|string',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            $incident->update($validated);
            return $this->success($incident->load('reporter'));
        } catch (\Throwable $e) {
            return $this->error('Failed to update safety incident: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $incident = SafetyIncident::findOrFail($id);
            $this->authorizeForSite($incident->site_id, 'admin');
            $incident->delete();
            return $this->success(['message' => 'Deleted successfully']);
        } catch (\Throwable $e) {
            return $this->error('Failed to delete safety incident', 500);
        }
    }

    public function resolve(string $id): JsonResponse
    {
        try {
            $incident = SafetyIncident::findOrFail($id);
        } catch (\Throwable $e) {
            return $this->error('Safety incident not found', 404);
        }

        $this->authorizeForSite($incident->site_id, 'site_manager');

        try {
            $incident->update(['resolved_at' => now()]);
            return $this->success($incident->load('reporter'));
        } catch (\Throwable $e) {
            return $this->error('Failed to resolve safety incident: ' . $e->getMessage(), 500);
        }
    }
}
