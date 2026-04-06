<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        try {
            $siteId = $request->query('site_id') ?? $request->header('X-Site-Id');

            $query = Transaction::query();

            if ($siteId) {
                $query->where('site_id', $siteId);
            }

            if ($request->filled('type')) {
                $query->where('type', $request->query('type'));
            }

            if ($request->filled('status')) {
                $query->where('status', $request->query('status'));
            }

            if ($request->filled('category')) {
                $query->where('category', $request->query('category'));
            }

            if ($request->filled('customer_id')) {
                $query->where('customer_id', $request->query('customer_id'));
            }

            if ($request->filled('expense_category_id')) {
                $query->where('expense_category_id', $request->query('expense_category_id'));
            }

            if ($request->filled('source')) {
                $query->where('source', $request->query('source'));
            }

            if ($request->filled('from')) {
                $query->where('transaction_date', '>=', $request->query('from'));
            }

            if ($request->filled('to')) {
                $query->where('transaction_date', '<=', $request->query('to'));
            }

            $transactions = $query->orderBy('transaction_date', 'desc')->get();

            return $this->success($transactions);
        } catch (\Throwable $e) {
            return $this->error('Failed to fetch transactions: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'site_id'              => 'required|uuid|exists:sites,id',
                'customer_id'          => 'nullable|uuid|exists:customers,id',
                'expense_category_id'  => 'nullable|uuid|exists:expense_categories,id',
                'inventory_item_id'    => 'nullable|uuid|exists:inventory_items,id',
                'reference_no'         => 'nullable|string|max:100',
                'description'          => 'nullable|string|max:500',
                'category'             => 'nullable|string|max:100',
                'type'                 => 'required|in:income,expense,refund',
                'status'               => 'nullable|in:success,pending,refunded,cancelled',
                'source'               => 'nullable|in:manual,inventory,order',
                'quantity'             => 'nullable|numeric|min:0',
                'unit_price'           => 'nullable|numeric|min:0',
                'currency'             => 'nullable|string|size:3',
                'transaction_date'     => 'required|date',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            $validated['created_by'] = auth()->id();
            $transaction = Transaction::create($validated);
            return $this->created($transaction);
        } catch (\Throwable $e) {
            return $this->error('Failed to create transaction: ' . $e->getMessage(), 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $transaction = Transaction::findOrFail($id);
            return $this->success($transaction);
        } catch (\Throwable $e) {
            return $this->error('Transaction not found', 404);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $transaction = Transaction::findOrFail($id);
        } catch (\Throwable $e) {
            return $this->error('Transaction not found', 404);
        }

        try {
            $validated = $request->validate([
                'customer_id'          => 'nullable|uuid|exists:customers,id',
                'expense_category_id'  => 'nullable|uuid|exists:expense_categories,id',
                'reference_no'         => 'nullable|string|max:100',
                'description'          => 'nullable|string|max:500',
                'category'             => 'nullable|string|max:100',
                'type'                 => 'sometimes|in:income,expense,refund',
                'status'               => 'nullable|in:success,pending,refunded,cancelled',
                'quantity'             => 'nullable|numeric|min:0',
                'unit_price'           => 'nullable|numeric|min:0',
                'currency'             => 'nullable|string|size:3',
                'transaction_date'     => 'sometimes|date',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            $transaction->update($validated);
            return $this->success($transaction->fresh());
        } catch (\Throwable $e) {
            return $this->error('Failed to update transaction: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $transaction = Transaction::findOrFail($id);
            $transaction->delete();
            return $this->success(['message' => 'Deleted successfully']);
        } catch (\Throwable $e) {
            return $this->error('Failed to delete transaction', 500);
        }
    }

    public function categories(Request $request): JsonResponse
    {
        try {
            $siteId = $request->query('site_id') ?? $request->header('X-Site-Id');

            $query = Transaction::select('category')
                ->whereNotNull('category')
                ->distinct();

            if ($siteId) {
                $query->where('site_id', $siteId);
            }

            $categories = $query->pluck('category')->sort()->values();

            return $this->success($categories);
        } catch (\Throwable $e) {
            return $this->error('Failed to fetch categories: ' . $e->getMessage(), 500);
        }
    }
}
