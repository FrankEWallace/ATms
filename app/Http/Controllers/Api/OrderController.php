<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\Transaction;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        try {
            $siteId = $request->query('site_id') ?? $request->header('X-Site-Id');
            $query  = Order::with(['supplier', 'channel', 'customer', 'items.inventoryItem']);

            if ($siteId) {
                $query->where('site_id', $siteId);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->query('status'));
            }

            return $this->success($query->orderBy('created_at', 'desc')->get());
        } catch (\Throwable $e) {
            return $this->error('Failed to fetch orders: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'site_id'       => 'required|uuid|exists:sites,id',
                'supplier_id'   => 'nullable|uuid|exists:suppliers,id',
                'channel_id'    => 'nullable|uuid|exists:channels,id',
                'customer_id'   => 'nullable|uuid|exists:customers,id',
                'order_number'  => 'nullable|string|max:100',
                'status'        => 'nullable|in:draft,sent,confirmed,received,cancelled',
                'total_amount'  => 'nullable|numeric|min:0',
                'expected_date' => 'nullable|date',
                'notes'         => 'nullable|string',
                'items'         => 'nullable|array',
                'items.*.inventory_item_id' => 'nullable|uuid|exists:inventory_items,id',
                'items.*.quantity'          => 'required_with:items|numeric|min:0.0001',
                'items.*.unit_price'        => 'nullable|numeric|min:0',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            return DB::transaction(function () use ($validated) {
                $items = $validated['items'] ?? [];
                unset($validated['items']);

                $validated['created_by'] = auth()->id();
                $order = Order::create($validated);

                foreach ($items as $item) {
                    $total = ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0);
                    $order->items()->create([
                        'inventory_item_id' => $item['inventory_item_id'] ?? null,
                        'quantity'          => $item['quantity'],
                        'unit_price'        => $item['unit_price'] ?? 0,
                        'total'             => $total,
                    ]);
                }

                return $this->created($order->load(['supplier', 'channel', 'customer', 'items.inventoryItem']));
            });
        } catch (\Throwable $e) {
            return $this->error('Failed to create order: ' . $e->getMessage(), 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $order = Order::with(['supplier', 'channel', 'customer', 'items.inventoryItem'])->findOrFail($id);
            return $this->success($order);
        } catch (\Throwable $e) {
            return $this->error('Order not found', 404);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $order = Order::findOrFail($id);
        } catch (\Throwable $e) {
            return $this->error('Order not found', 404);
        }

        try {
            $validated = $request->validate([
                'supplier_id'   => 'nullable|uuid|exists:suppliers,id',
                'channel_id'    => 'nullable|uuid|exists:channels,id',
                'customer_id'   => 'nullable|uuid|exists:customers,id',
                'order_number'  => 'nullable|string|max:100',
                'status'        => 'nullable|in:draft,sent,confirmed,received,cancelled',
                'total_amount'  => 'nullable|numeric|min:0',
                'expected_date' => 'nullable|date',
                'received_date' => 'nullable|date',
                'notes'         => 'nullable|string',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            $order->update($validated);
            return $this->success($order->load(['supplier', 'channel', 'customer', 'items.inventoryItem']));
        } catch (\Throwable $e) {
            return $this->error('Failed to update order: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $order = Order::findOrFail($id);
            $order->delete();
            return $this->success(['message' => 'Deleted successfully']);
        } catch (\Throwable $e) {
            return $this->error('Failed to delete order', 500);
        }
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:draft,sent,confirmed,received,cancelled',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            $order = Order::with('items.inventoryItem')->findOrFail($id);
        } catch (\Throwable $e) {
            return $this->error('Order not found', 404);
        }

        try {
            return DB::transaction(function () use ($order, $validated) {
                if ($validated['status'] === 'received' && $order->status !== 'received') {
                    $order->update(['received_date' => now()->toDateString()]);
                    $this->processReceived($order);
                }

                $order->update(['status' => $validated['status']]);

                return $this->success($order->load(['supplier', 'channel', 'customer', 'items.inventoryItem']));
            });
        } catch (\Throwable $e) {
            return $this->error('Failed to update order status: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /orders/{id}/receive
     * Dedicated receive endpoint called by the frontend REST path.
     */
    public function receive(string $id): JsonResponse
    {
        try {
            $order = Order::with('items.inventoryItem')->findOrFail($id);
        } catch (\Throwable $e) {
            return $this->error('Order not found', 404);
        }

        if ($order->status === 'received') {
            return $this->error('Order is already received', 422);
        }

        try {
            return DB::transaction(function () use ($order) {
                $order->update([
                    'status'        => 'received',
                    'received_date' => now()->toDateString(),
                ]);

                $this->processReceived($order);

                return $this->success($order->load(['supplier', 'channel', 'customer', 'items.inventoryItem']));
            });
        } catch (\Throwable $e) {
            return $this->error('Failed to receive order: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Increment inventory quantities and create source:'order' expense transactions
     * for each line item. Called when an order transitions to "received".
     */
    private function processReceived(Order $order): void
    {
        $today = now()->toDateString();

        foreach ($order->items as $orderItem) {
            if (! $orderItem->inventory_item_id) {
                continue;
            }

            $invItem = InventoryItem::find($orderItem->inventory_item_id);
            if (! $invItem) {
                continue;
            }

            // Increment inventory stock
            $invItem->increment('quantity', $orderItem->quantity);

            // Log inventory movement
            InventoryTransaction::create([
                'inventory_item_id' => $invItem->id,
                'site_id'           => $order->site_id,
                'quantity_change'   => $orderItem->quantity,
                'reason'            => 'Order received: ' . ($order->order_number ?? $order->id),
                'created_by'        => auth()->id(),
            ]);

            // Auto-create financial expense transaction
            if ((float) $orderItem->unit_price > 0) {
                Transaction::create([
                    'site_id'           => $order->site_id,
                    'customer_id'       => $order->customer_id,
                    'inventory_item_id' => $invItem->id,
                    'description'       => 'PO received — ' . $invItem->name . ' × ' . $orderItem->quantity . ' ' . ($invItem->unit ?? 'units'),
                    'type'              => 'expense',
                    'status'            => 'success',
                    'quantity'          => $orderItem->quantity,
                    'unit_price'        => $orderItem->unit_price,
                    'category'          => $invItem->category,
                    'transaction_date'  => $today,
                    'source'            => 'order',
                    'created_by'        => auth()->id(),
                ]);
            }
        }
    }
}
