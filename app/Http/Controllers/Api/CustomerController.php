<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CustomerController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        try {
            $siteId = $request->query('site_id') ?? $request->header('X-Site-Id');

            $query = Customer::query();

            if ($siteId) {
                $this->authorizeForSite($siteId);
                $query->where('site_id', $siteId);
            } else {
                $query->whereIn('site_id', $this->getUserSiteIds());
            }

            if ($request->filled('status')) {
                $query->where('status', $request->query('status'));
            }

            if ($request->filled('type')) {
                $query->where('type', $request->query('type'));
            }

            $perPage = min((int) ($request->query('per_page', 100)), 200);
            return $this->paginated($query->orderBy('name')->paginate($perPage));
        } catch (\Throwable $e) {
            return $this->error('Failed to fetch customers: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'site_id'        => 'required|uuid|exists:sites,id',
                'name'           => 'required|string|max:255',
                'type'           => 'nullable|in:external,internal',
                'contact_name'   => 'nullable|string|max:255',
                'contact_email'  => 'nullable|email|max:255',
                'contact_phone'  => 'nullable|string|max:50',
                'contract_start' => 'nullable|date',
                'contract_end'   => 'nullable|date|after_or_equal:contract_start',
                'daily_rate'     => 'nullable|numeric|min:0',
                'notes'          => 'nullable|string',
                'status'         => 'nullable|in:active,inactive,completed',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            $this->authorizeForSite($validated['site_id'], 'site_manager');

            // Always derive org_id from the authenticated user — never trust the client.
            $validated['org_id'] = $this->getAuthUserOrgId();

            $customer = Customer::create($validated);
            return $this->created($customer);
        } catch (\Throwable $e) {
            return $this->error('Failed to create customer: ' . $e->getMessage(), 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $customer = Customer::findOrFail($id);
            $this->authorizeForSite($customer->site_id);
            return $this->success($customer);
        } catch (\Throwable $e) {
            return $this->error('Customer not found', 404);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $customer = Customer::findOrFail($id);
        } catch (\Throwable $e) {
            return $this->error('Customer not found', 404);
        }

        $this->authorizeForSite($customer->site_id, 'site_manager');

        try {
            $validated = $request->validate([
                'name'           => 'sometimes|string|max:255',
                'type'           => 'sometimes|in:external,internal',
                'contact_name'   => 'nullable|string|max:255',
                'contact_email'  => 'nullable|email|max:255',
                'contact_phone'  => 'nullable|string|max:50',
                'contract_start' => 'nullable|date',
                'contract_end'   => 'nullable|date',
                'daily_rate'     => 'nullable|numeric|min:0',
                'notes'          => 'nullable|string',
                'status'         => 'sometimes|in:active,inactive,completed',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            $customer->update($validated);
            return $this->success($customer->fresh());
        } catch (\Throwable $e) {
            return $this->error('Failed to update customer: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $customer = Customer::findOrFail($id);
            $this->authorizeForSite($customer->site_id, 'admin');
            $customer->delete();
            return response()->json(null, 204);
        } catch (\Throwable $e) {
            return $this->error('Failed to delete customer', 500);
        }
    }
}
