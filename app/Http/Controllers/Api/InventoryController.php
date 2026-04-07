<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Transaction;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        try {
            $siteId = $request->query('site_id') ?? $request->header('X-Site-Id');

            $query = InventoryItem::with('supplier');

            if ($siteId) {
                $this->authorizeForSite($siteId);
                $query->where('site_id', $siteId);
            } else {
                $query->whereIn('site_id', $this->getUserSiteIds());
            }

            if ($request->filled('category')) {
                $query->where('category', $request->query('category'));
            }

            if ($request->filled('search')) {
                $search = $request->query('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            }

            $items = $query->orderBy('name')->get();

            return $this->success($items);
        } catch (\Throwable $e) {
            return $this->error('Failed to fetch inventory: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'site_id'       => 'required|uuid|exists:sites,id',
                'name'          => 'required|string|max:255',
                'category'      => 'nullable|string|max:100',
                'sku'           => 'nullable|string|max:100',
                'quantity'      => 'nullable|numeric|min:0',
                'unit'          => 'nullable|string|max:50',
                'unit_cost'     => 'nullable|numeric|min:0',
                'reorder_level' => 'nullable|numeric|min:0',
                'supplier_id'   => 'nullable|uuid|exists:suppliers,id',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            $this->authorizeForSite($validated['site_id'], 'site_manager');

            $item = InventoryItem::create($validated);
            return $this->created($item->load('supplier'));
        } catch (\Throwable $e) {
            return $this->error('Failed to create inventory item: ' . $e->getMessage(), 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $item = InventoryItem::with('supplier', 'transactions')->findOrFail($id);
            $this->authorizeForSite($item->site_id);
            return $this->success($item);
        } catch (\Throwable $e) {
            return $this->error('Inventory item not found', 404);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $item = InventoryItem::findOrFail($id);
        } catch (\Throwable $e) {
            return $this->error('Inventory item not found', 404);
        }

        $this->authorizeForSite($item->site_id, 'site_manager');

        try {
            $validated = $request->validate([
                'name'          => 'sometimes|string|max:255',
                'category'      => 'nullable|string|max:100',
                'sku'           => 'nullable|string|max:100',
                'quantity'      => 'nullable|numeric|min:0',
                'unit'          => 'nullable|string|max:50',
                'unit_cost'     => 'nullable|numeric|min:0',
                'reorder_level' => 'nullable|numeric|min:0',
                'supplier_id'   => 'nullable|uuid|exists:suppliers,id',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            $item->update($validated);
            return $this->success($item->load('supplier'));
        } catch (\Throwable $e) {
            return $this->error('Failed to update inventory item: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $item = InventoryItem::findOrFail($id);
            $this->authorizeForSite($item->site_id, 'admin');
            $item->delete();
            return $this->success(['message' => 'Deleted successfully']);
        } catch (\Throwable $e) {
            return $this->error('Failed to delete inventory item', 500);
        }
    }

    public function categories(Request $request): JsonResponse
    {
        try {
            $siteId = $request->query('site_id') ?? $request->header('X-Site-Id');

            $query = InventoryItem::select('category')->whereNotNull('category')->distinct();

            if ($siteId) {
                $this->authorizeForSite($siteId);
                $query->where('site_id', $siteId);
            } else {
                $query->whereIn('site_id', $this->getUserSiteIds());
            }

            $categories = $query->pluck('category')->sort()->values();

            return $this->success($categories);
        } catch (\Throwable $e) {
            return $this->error('Failed to fetch categories: ' . $e->getMessage(), 500);
        }
    }

    public function consume(Request $request, string $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'quantity'            => 'required|numeric|min:0.0001',
                'reason'              => 'nullable|string|max:255',
                'customer_id'         => 'nullable|uuid|exists:customers,id',
                'expense_category_id' => 'nullable|uuid|exists:expense_categories,id',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            $item = InventoryItem::findOrFail($id);
            $this->authorizeForSite($item->site_id, 'worker');

            if ($item->quantity < $validated['quantity']) {
                return $this->error('Insufficient stock', 400);
            }

            // All three writes are atomic — a failure after decrement must not
            // leave stock reduced with no corresponding audit or expense record.
            $item = DB::transaction(function () use ($item, $validated) {
                $item->decrement('quantity', $validated['quantity']);

                InventoryTransaction::create([
                    'inventory_item_id' => $item->id,
                    'site_id'           => $item->site_id,
                    'quantity_change'   => -$validated['quantity'],
                    'reason'            => $validated['reason'] ?? 'consumption',
                    'created_by'        => auth()->id(),
                ]);

                $unitCost = (float) ($item->unit_cost ?? 0);
                if ($unitCost > 0) {
                    $notes = $validated['reason'] ? ' (' . $validated['reason'] . ')' : '';
                    Transaction::create([
                        'site_id'             => $item->site_id,
                        'customer_id'         => $validated['customer_id'] ?? null,
                        'expense_category_id' => $validated['expense_category_id'] ?? null,
                        'inventory_item_id'   => $item->id,
                        'description'         => $item->name . ' usage — ' . $validated['quantity'] . ' ' . ($item->unit ?? 'units') . $notes,
                        'type'                => 'expense',
                        'status'              => 'success',
                        'quantity'            => $validated['quantity'],
                        'unit_price'          => $unitCost,
                        'category'            => $item->category,
                        'transaction_date'    => now()->toDateString(),
                        'source'              => 'inventory',
                        'created_by'          => auth()->id(),
                    ]);
                }

                return $item->fresh();
            });

            return $this->success($item);
        } catch (\Throwable $e) {
            return $this->error('Failed to record consumption: ' . $e->getMessage(), 500);
        }
    }

    public function restock(Request $request, string $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'quantity' => 'required|numeric|min:0.0001',
                'reason'   => 'nullable|string|max:255',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            $item = InventoryItem::findOrFail($id);
            $this->authorizeForSite($item->site_id, 'site_manager');

            $item->increment('quantity', $validated['quantity']);

            InventoryTransaction::create([
                'inventory_item_id' => $item->id,
                'site_id'           => $item->site_id,
                'quantity_change'   => $validated['quantity'],
                'reason'            => $validated['reason'] ?? 'restock',
                'created_by'        => auth()->id(),
            ]);

            return $this->success($item->fresh());
        } catch (\Throwable $e) {
            return $this->error('Failed to record restock: ' . $e->getMessage(), 500);
        }
    }

    public function transactions(Request $request, string $id): JsonResponse
    {
        try {
            $item = InventoryItem::findOrFail($id);
            $this->authorizeForSite($item->site_id);
            $txns = $item->transactions()->orderBy('created_at', 'desc')->get();
            return $this->success($txns);
        } catch (\Throwable $e) {
            return $this->error('Failed to fetch transactions: ' . $e->getMessage(), 500);
        }
    }
}
