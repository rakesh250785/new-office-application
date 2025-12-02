<?php

namespace App\Http\Controllers\Website;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Website\WishlistModel;
use Auth;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WishListController extends Controller
{
    /**
     * POST /wishlist/add
     * body: wishlist_id (optional), item (object) OR items (array)
     * item shape: item_id (opt), product_id (opt), sku, name (required), attributes (opt)
     *
     * This will add item(s) to wishlist. If item already exists (match by item_id or product_id+sku),
     * it will not duplicate it (for wishlist we treat as idempotent add).
     */
    public function add(Request $request)
    {
        $rules = [
            'wishlist_id' => 'nullable|integer|exists:wishlists,id',
            'item' => 'nullable',
            'items' => 'nullable',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Utility::apiError('Validation failed', $validator->errors(), 221);
        }

        $itemsInput = $request->input('items') ?? ($request->input('item') ? $request->input('item') : []);
        if (! is_array($itemsInput) || array_keys($itemsInput) !== range(0, count($itemsInput) - 1)) {
            // single object => wrap
            $itemsInput = [$itemsInput];
        }

        // validate items minimally
        foreach ($itemsInput as $i => $it) {
            $iv = Validator::make($it, [
                'item_id' => 'nullable|integer',
                'product_id' => 'nullable|integer',
                'sku' => 'nullable|string|max:128',
                'name' => 'required|string|max:255',
                'attributes' => 'nullable|array',
            ]);
            if ($iv->fails()) {
                return Utility::apiError("Validation failed for items[$i]", $iv->errors(), 221);
            }
        }

        DB::beginTransaction();
        try {
            $wishlist = $this->findOrCreateWishlist($request->input('wishlist_id'), $request->input('currency'));

            $items = $wishlist->items ?? [];

            foreach ($itemsInput as $it) {
                // idempotent add: find existing index using item_id or product_id+sku
                $found = null;
                if (! empty($it['item_id'])) {
                    foreach ($items as $k => $e) {
                        if (isset($e['id']) && (int) $e['id'] === (int) $it['item_id']) {
                            $found = $k;
                            break;
                        }
                    }
                }

                if ($found === null && ! empty($it['product_id'])) {
                    foreach ($items as $k => $e) {
                        if (isset($e['product_id']) && $e['product_id'] == $it['product_id']) {
                            // if sku present ensure match else accept
                            if (! empty($it['sku'])) {
                                if (isset($e['sku']) && $e['sku'] === $it['sku']) {
                                    $found = $k;
                                    break;
                                }
                            } else {
                                $found = $k;
                                break;
                            }
                        }
                    }
                }

                if ($found === null) {
                    // add new unique item
                    $newId = collect($items)->pluck('id')->max() ?? 0;
                    $newId = (int) $newId + 1;
                    $items[] = [
                        'id' => $newId,
                        'product_id' => $it['product_id'] ?? null,
                        'sku' => $it['sku'] ?? null,
                        'name' => $it['name'],
                        'attributes' => $it['attributes'] ?? null,
                        'created_at' => now()->toDateTimeString(),
                    ];
                } else {
                    // already present - update metadata optionally
                    $existing = $items[$found];
                    $existing['attributes'] = $it['attributes'] ?? ($existing['attributes'] ?? null);
                    $existing['updated_at'] = now()->toDateTimeString();
                    $items[$found] = $existing;
                }
            }

            // recompute counts
            $itemsCount = 0;
            foreach ($items as $it) { /* wishlist counts per item = 1 */ $itemsCount += 1;
            }
            $distinct = count($items);

            $wishlist->fill([
                'items' => $items,
                'items_count' => $itemsCount,
                'distinct_items' => $distinct,
            ])->save();

            DB::commit();

            return Utility::apiSuccess('Wishlist updated', $wishlist, 200);
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error('Wishlist add error: '.$ex->getMessage());

            return Utility::apiError('Failed to update wishlist', ['exception' => $ex->getMessage()], 500);
        }
    }

    /**
     * GET/POST /wishlist/get
     * body/query: wishlist_id (optional)
     */
    public function get(Request $request)
    {
        try {
            if ($request->filled('wishlist_id')) {
                $wl = WishlistModel::find($request->input('wishlist_id'));
                if (! $wl) {
                    return Utility::apiError('Wishlist not found', [], 404);
                }

                return Utility::apiSuccess('Wishlist fetched', $wl, 200);
            }

            if (Auth::check()) {
                $wl = WishlistModel::where('user_id', Auth::id())->first();
                if (! $wl) {
                    return Utility::apiSuccess('No wishlist, returning empty', WishlistModel::emptySkeleton(Auth::id()), 200);
                }

                return Utility::apiSuccess('Wishlist fetched', $wl, 200);
            }

            // guests: require wishlist_id or frontend should keep local wishlist
            return Utility::apiError('wishlist_id required for guests', [], 221);
        } catch (Exception $ex) {
            Log::error('Wishlist fetch error: '.$ex->getMessage());

            return Utility::apiError('Failed to fetch wishlist', ['exception' => $ex->getMessage()], 500);
        }
    }

    /**
     * POST /wishlist/remove
     * body: wishlist_id (required), item_id (optional single or array), clear (optional boolean), delete_all (optional boolean - admin)
     */
    public function remove(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'wishlist_id' => 'required|integer|exists:wishlists,id',
            'item_id' => 'nullable',
            'clear' => 'nullable|boolean',
            'delete_all' => 'nullable|boolean',
        ]);
        if ($validator->fails()) {
            return Utility::apiError('Validation failed', $validator->errors(), 221);
        }

        DB::beginTransaction();
        try {
            $wl = WishlistModel::find($request->input('wishlist_id'));
            if (! $wl) {
                return Utility::apiError('Wishlist not found', [], 404);
            }

            // delete entire wishlist row (admin only) — protect route with admin middleware in routes
            if ($request->boolean('delete_all')) {
                $wl->delete();
                DB::commit();

                return Utility::apiSuccess('Wishlist deleted', [], 200);
            }

            // clear wishlist items
            if ($request->boolean('clear')) {
                $wl->update(['items' => [], 'items_count' => 0, 'distinct_items' => 0]);
                DB::commit();

                return Utility::apiSuccess('Wishlist cleared', $wl, 200);
            }

            // remove specific item(s)
            $itemId = $request->input('item_id', null);
            if (empty($itemId)) {
                DB::rollBack();

                return Utility::apiError('item_id or clear/delete_all required', [], 221);
            }

            $removeIds = is_array($itemId) ? array_map('intval', $itemId) : [(int) $itemId];

            $items = $wl->items ?? [];
            $items = array_values(array_filter($items, function ($it) use ($removeIds) {
                return ! (isset($it['id']) && in_array((int) $it['id'], $removeIds, true));
            }));

            $wl->update([
                'items' => $items,
                'items_count' => count($items),
                'distinct_items' => count($items),
            ]);

            DB::commit();

            return Utility::apiSuccess('Item(s) removed', $wl, 200);
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error('Wishlist remove error: '.$ex->getMessage());

            return Utility::apiError('Failed to remove wishlist items', ['exception' => $ex->getMessage()], 500);
        }
    }

    /* ---------------- Admin list & details ---------------- */

    /**
     * POST /admin/wishlist/list
     * body: page, per_page, search, start_date, end_date
     */
    public function getList(Request $request)
    {
        try {
            $page = max(1, (int) $request->input('page', 1));
            $perPage = (int) $request->input('per_page', 10);
            $search = trim((string) $request->input('search', ''));
            $startDate = $request->input('start_date', null);
            $endDate = $request->input('end_date', null);

            $query = WishlistModel::query();

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('id', $search)
                        ->orWhere('user_id', $search);
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

            return Utility::apiSuccess('Wishlist list fetched', $result, 200);
        } catch (Exception $ex) {
            Log::error('Admin wishlist list error: '.$ex->getMessage());

            return Utility::apiError('Failed to fetch wishlists', ['exception' => $ex->getMessage()], 500);
        }
    }

    /**
     * POST /admin/wishlist/get
     * body: wishlist_id required
     */
    public function getDetails(Request $request)
    {
        $v = Validator::make($request->all(), ['wishlist_id' => 'required|integer|exists:wishlists,id']);
        if ($v->fails()) {
            return Utility::apiError('Validation failed', $v->errors(), 221);
        }

        try {
            $wl = WishlistModel::find($request->input('wishlist_id'));
            if (! $wl) {
                return Utility::apiError('Wishlist not found', [], 404);
            }

            return Utility::apiSuccess('Wishlist fetched', $wl, 200);
        } catch (Exception $ex) {
            Log::error('Admin wishlist fetch error: '.$ex->getMessage());

            return Utility::apiError('Failed to fetch wishlist', ['exception' => $ex->getMessage()], 500);
        }
    }

    /* ---------------- Helpers ---------------- */

    protected function findOrCreateWishlist(?int $wishlistId = null, ?string $currency = 'INR'): WishlistModel
    {
        if ($wishlistId) {
            $wl = WishlistModel::find($wishlistId);
            if ($wl) {
                return $wl;
            }
        }

        if (Auth::check()) {
            return WishlistModel::firstOrCreate(
                ['user_id' => Auth::id()],
                ['currency' => $currency, 'items' => [], 'items_count' => 0, 'distinct_items' => 0, 'sub_total' => 0]
            );
        }

        return WishlistModel::create(['currency' => $currency, 'items' => [], 'items_count' => 0, 'distinct_items' => 0, 'sub_total' => 0]);
    }
}
