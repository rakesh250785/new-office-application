<?php

namespace App\Http\Controllers\Website;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Website\CartModel;
use Auth;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    public function addCart(Request $request)
    {
        $rules = [
            'cart_id' => 'nullable|integer|exists:carts,id',
            'items' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Utility::apiError('Validation failed', $validator->errors(), 221);
        }

        $itemsInput = $request->input('items');
        if (! is_array($itemsInput) || array_keys($itemsInput) !== range(0, count($itemsInput) - 1)) {
            $itemsInput = [$itemsInput];
        }

        // per-item validation
        foreach ($itemsInput as $i => $it) {
            $iv = Validator::make($it, [
                'item_id' => 'nullable|integer',
                'product_id' => 'nullable|integer',
                'sku' => 'nullable|string|max:128',
                'name' => 'required|string|max:255',
                'qty' => 'required|integer|min:1',
                'unit_price' => 'required|numeric|min:0',
                'attributes' => 'nullable|array',
            ]);
            if ($iv->fails()) {
                return Utility::apiError("Validation failed for items[$i]", $iv->errors(), 221);
            }
        }

        try {
            $cart = $this->findOrCreateCart($request->input('cart_id'), $request->input('currency'));

            $items = $cart->items ?? [];

            foreach ($itemsInput as $it) {
                $items = $this->upsertItem($items, $it);
            }

            $computed = $this->recomputeTotals($items, $cart);

            $cart->fill([
                'items' => $items,
                'items_count' => $computed['items_count'],
                'distinct_items' => $computed['distinct_items'],
                'sub_total' => $computed['sub_total'],
                'discount_total' => $computed['discount_total'],
                'tax_total' => $computed['tax_total'],
                'shipping_total' => $computed['shipping_total'],
                'grand_total' => $computed['grand_total'],
            ])->save();

            return Utility::apiSuccess('Cart updated', $cart, 200);
        } catch (Exception $ex) {

            Log::error('Cart add error: '.$ex->getMessage());

            return Utility::apiError('Failed to add to cart', ['exception' => $ex->getMessage()], 500);
        }
    }

    /**
     * GET/POST /cart/get
     * body/query: cart_id (optional)
     */
    public function getCart(Request $request)
    {
        try {
            if ($request->filled('cart_id')) {
                $cart = CartModel::find($request->input('cart_id'));
                if (! $cart) {
                    return Utility::apiError('Cart not found', [], 404);
                }

                return Utility::apiSuccess('Cart fetched', $cart, 200);
            }

            if (Auth::check()) {
                $cart = CartModel::where('user_id', Auth::id())->where('status', 'open')->first();
                if (! $cart) {
                    $empty = [
                        'id' => null,
                        'user_id' => Auth::id(),
                        'items' => [],
                        'items_count' => 0,
                        'distinct_items' => 0,
                        'sub_total' => 0,
                        'discount_total' => 0,
                        'tax_total' => 0,
                        'shipping_total' => 0,
                        'grand_total' => 0,
                    ];

                    return Utility::apiSuccess('No cart found, returning empty', $empty, 200);
                }

                return Utility::apiSuccess('Cart fetched', $cart, 200);
            }

            return Utility::apiError('cart_id is required for guests', [], 221);
        } catch (Exception $ex) {
            Log::error('Cart fetch error: '.$ex->getMessage());

            return Utility::apiError('Failed to fetch cart', ['exception' => $ex->getMessage()], 500);
        }
    }

    /**
     * POST /cart/remove
     * body: cart_id (required), item_id (optional), clear (optional boolean)
     * - For admin: supports delete_all (boolean) to delete cart row as well
     */
    public function removeCart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cart_id' => 'required|integer|exists:carts,id',
            'item_id' => 'nullable',
            'clear' => 'nullable|boolean',
            'delete_all' => 'nullable|boolean',
        ]);
        if ($validator->fails()) {
            return Utility::apiError('Validation failed', $validator->errors(), 221);
        }

        try {
            $cart = CartModel::find($request->input('cart_id'));
            if (! $cart) {

                return Utility::apiError('Cart not found', [], 404);
            }

            // admin: delete entire cart row
            if ($request->boolean('delete_all')) {
                $cart->delete();

                return Utility::apiSuccess('Cart deleted successfully', [], 200);
            }

            // clear all items
            if ($request->boolean('clear')) {
                $cart->update([
                    'items' => [],
                    'items_count' => 0,
                    'distinct_items' => 0,
                    'sub_total' => 0,
                    'discount_total' => 0,
                    'tax_total' => 0,
                    'shipping_total' => 0,
                    'grand_total' => 0,
                ]);

                return Utility::apiSuccess('Cart cleared', $cart, 200);
            }

            // remove specific item(s)
            $itemId = $request->input('item_id', null);
            if (empty($itemId)) {

                return Utility::apiError('item_id or clear flag required', [], 221);
            }

            $idsToRemove = is_array($itemId) ? array_map('intval', $itemId) : [(int) $itemId];

            $items = $cart->items ?? [];
            $newItems = array_values(array_filter($items, function ($it) use ($idsToRemove) {
                return ! (isset($it['id']) && in_array((int) $it['id'], $idsToRemove, true));
            }));

            $computed = $this->recomputeTotals($newItems, $cart);

            $cart->fill([
                'items' => $newItems,
                'items_count' => $computed['items_count'],
                'distinct_items' => $computed['distinct_items'],
                'sub_total' => $computed['sub_total'],
                'discount_total' => $computed['discount_total'],
                'tax_total' => $computed['tax_total'],
                'shipping_total' => $computed['shipping_total'],
                'grand_total' => $computed['grand_total'],
            ])->save();

            return Utility::apiSuccess('Item(s) removed', $cart, 200);
        } catch (Exception $ex) {
            Log::error('Cart remove error: '.$ex->getMessage());

            return Utility::apiError('Failed to remove from cart', ['exception' => $ex->getMessage()], 500);
        }
    }

    /* -------------------- Admin APIs (list & details) -------------------- */

    /**
     * POST /admin/cart/list
     * body: page, per_page, search, start_date, end_date
     */
    public function getCartList(Request $request)
    {
        try {
            $page = max(1, (int) $request->input('page', 1));
            $perPage = (int) $request->input('per_page', 10);
            $search = trim((string) $request->input('search', ''));
            $startDate = $request->input('start_date', null);
            $endDate = $request->input('end_date', null);

            $query = CartModel::query();

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('id', $search)
                        ->orWhere('user_id', $search)
                        ->orWhere('sub_total', 'like', "%{$search}%")
                        ->orWhere('grand_total', 'like', "%{$search}%");
                    try {
                        $q->orWhereJsonContains('items->*->sku', $search);
                    } catch (\Throwable $e) {

                    }
                });
            }

            if (! empty($startDate)) {
                $query->whereDate('created_at', '>=', $startDate);
            }
            if (! empty($endDate)) {
                $query->whereDate('created_at', '<=', $endDate);
            }

            $result = $query->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);

            return Utility::apiSuccess('Cart list fetched', $result, 200);
        } catch (Exception $ex) {
            Log::error('Admin cart list error: '.$ex->getMessage());

            return Utility::apiError('Failed to fetch carts', ['exception' => $ex->getMessage()], 500);
        }
    }

    /**
     * POST /admin/cart/get
     * body: cart_id (required)
     */
    public function getCartDetails(Request $request)
    {
        $v = Validator::make($request->all(), ['cart_id' => 'required|integer|exists:carts,id']);
        if ($v->fails()) {
            return Utility::apiError('Validation failed', $v->errors(), 221);
        }

        try {
            $cart = CartModel::find($request->input('cart_id'));
            if (! $cart) {
                return Utility::apiError('Cart not found', [], 404);
            }

            return Utility::apiSuccess('Cart fetched', $cart, 200);
        } catch (Exception $ex) {
            Log::error('Admin cart fetch error: '.$ex->getMessage());

            return Utility::apiError('Failed to fetch cart', ['exception' => $ex->getMessage()], 500);
        }
    }

    /* -------------------- Helpers -------------------- */

    protected function findOrCreateCart(?int $cartId = null, ?string $currency = 'INR'): CartModel
    {
        if ($cartId) {
            $cart = CartModel::find($cartId);
            if ($cart) {
                return $cart;
            }
        }

        if (Auth::check()) {
            return CartModel::firstOrCreate(
                ['user_id' => Auth::id(), 'status' => 'open'],
                [
                    'currency' => $currency,
                    'items' => [],
                    'items_count' => 0,
                    'distinct_items' => 0,
                    'sub_total' => 0,
                    'discount_total' => 0,
                    'tax_total' => 0,
                    'shipping_total' => 0,
                    'grand_total' => 0,
                ]
            );
        }

        return CartModel::create([
            'currency' => $currency,
            'items' => [],
            'items_count' => 0,
            'distinct_items' => 0,
            'sub_total' => 0,
            'discount_total' => 0,
            'tax_total' => 0,
            'shipping_total' => 0,
            'grand_total' => 0,
        ]);
    }

    protected function upsertItem(array $items, array $it): array
    {
        $itemIndex = null;

        if (! empty($it['item_id'])) {
            foreach ($items as $i => $exist) {
                if (isset($exist['id']) && (int) $exist['id'] === (int) $it['item_id']) {
                    $itemIndex = $i;
                    break;
                }
            }
        }

        if ($itemIndex === null && ! empty($it['product_id'])) {
            foreach ($items as $i => $exist) {
                if (isset($exist['product_id']) && $exist['product_id'] == $it['product_id']) {
                    if (! empty($it['sku'])) {
                        if (isset($exist['sku']) && $exist['sku'] === $it['sku']) {
                            $itemIndex = $i;
                            break;
                        }
                    } else {
                        $itemIndex = $i;
                        break;
                    }
                }
            }
        }

        $unitPrice = (float) ($it['unit_price'] ?? 0);
        $qty = (int) ($it['qty'] ?? 1);

        if ($itemIndex !== null) {
            $existing = $items[$itemIndex];
            $existing['qty'] = $qty;
            $existing['unit_price'] = number_format($unitPrice, 4, '.', '');
            $existing['line_total'] = number_format($unitPrice * $qty, 4, '.', '');
            $existing['attributes'] = $it['attributes'] ?? ($existing['attributes'] ?? null);
            $existing['product_id'] = $it['product_id'] ?? ($existing['product_id'] ?? null);
            $existing['sku'] = $it['sku'] ?? ($existing['sku'] ?? null);
            $existing['name'] = $it['name'] ?? ($existing['name'] ?? null);
            $existing['updated_at'] = now()->toDateTimeString();

            $items[$itemIndex] = $existing;

            return $items;
        }

        $newItemId = collect($items)->pluck('id')->max() ?? 0;
        $newItemId = (int) $newItemId + 1;

        $items[] = [
            'id' => $newItemId,
            'product_id' => $it['product_id'] ?? null,
            'sku' => $it['sku'] ?? null,
            'name' => $it['name'],
            'qty' => $qty,
            'unit_price' => number_format($unitPrice, 4, '.', ''),
            'line_total' => number_format($unitPrice * $qty, 4, '.', ''),
            'tax_amount' => 0,
            'discount_amount' => 0,
            'attributes' => $it['attributes'] ?? null,
            'metadata' => null,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ];

        return $items;
    }

    protected function recomputeTotals(array $items, ?CartModel $cart = null): array
    {
        $itemsCount = 0;
        $distinct = count($items);
        $subTotal = 0.0;
        $discountTotal = 0.0;
        $taxTotal = 0.0;
        $shippingTotal = $cart->shipping_total ?? 0.0;

        foreach ($items as $it) {
            $qty = (int) ($it['qty'] ?? 0);
            $line = (float) ($it['line_total'] ?? ((float) ($it['unit_price'] ?? 0) * $qty));
            $itemsCount += $qty;
            $subTotal += $line;
            $discountTotal += (float) ($it['discount_amount'] ?? 0.0);
            $taxTotal += (float) ($it['tax_amount'] ?? 0.0);
        }

        $grandTotal = $subTotal - $discountTotal + $taxTotal + $shippingTotal;

        return [
            'items_count' => (int) $itemsCount,
            'distinct_items' => (int) $distinct,
            'sub_total' => round($subTotal, 4),
            'discount_total' => round($discountTotal, 4),
            'tax_total' => round($taxTotal, 4),
            'shipping_total' => round($shippingTotal, 4),
            'grand_total' => round($grandTotal, 4),
        ];
    }
}
