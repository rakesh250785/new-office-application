<?php

namespace App\Http\Controllers\Website;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\OfferItemModel;
use App\Models\OfferModel;
use Auth;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class OfferController extends Controller
{
    public function addUpdateOffer(Request $request)
    {
        try {
            $rules = [
                'title' => 'nullable|string|max:255',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|integer|exists:products,id',
                'items.*.offer_price' => 'nullable|numeric|min:0',
                'items.*.discount_percent' => 'nullable|numeric|min:0',
                'id' => 'nullable|integer|exists:offers,id',
            ];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            $offer = $request->filled('id') ? OfferModel::find($request->id) : new OfferModel;
            if ($request->filled('id') && ! $offer) {
                return Utility::apiError('Offer not found', [], 404);
            }

            $offer->fill($request->only(['title', 'description', 'start_date', 'end_date']));
            $offer->status = $request->has('status') ? (bool) $request->status : $offer->status ?? true;
            $offer->created_by = Auth::id() ?? $offer->created_by;
            $offer->save();

            OfferItemModel::where('offer_id', $offer->id)->delete();

            foreach ($request->input('items', []) as $it) {
                $data = [
                    'offer_id' => $offer->id,
                    'product_id' => $it['product_id'],
                    'offer_price' => $it['offer_price'] ?? null,
                    'discount_percent' => $it['discount_percent'] ?? 0,
                    'qty_limit' => $it['qty_limit'] ?? null,
                    'igst_percent' => $it['igst_percent'] ?? 0,
                    'hsn' => $it['hsn'] ?? null,
                    'principal_id' => $it['principal_id'] ?? null,
                    'category_id' => $it['category_id'] ?? null,
                    'active' => $it['active'] ?? true,
                    'sort_order' => $it['sort_order'] ?? 0,
                ];

                $row = OfferItemModel::create($data);

                if (! $row) {
                    return Utility::apiError('fail_to_add_offer_product', [], 221);
                }
            }

            return Utility::apiSuccess('Offer saved', $offer->load('items.product'), 200);
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error('Offer save error: '.$ex->getMessage());

            return Utility::apiError('Something went wrong', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function listOffers(Request $request)
    {
        try {
            $page = max(1, (int) ($request->page ?? 1));
            $perPage = (int) ($request->per_page ?? config('constant.per_page', 15));
            $q = OfferModel::with(['items.product'])->orderByDesc('created_at');
            $p = $q->paginate($perPage, ['*'], 'page', $page);

            return Utility::apiSuccess('Offer list', $p, 200);
        } catch (Exception $ex) {
            Log::error('Offer list error: '.$ex->getMessage());

            return Utility::apiError('Failed', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function deleteOffer(Request $request)
    {
        try {
            $validator = Validator::make($request->only('id'), ['id' => 'required|integer|exists:offers,id']);
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }
            $offer = OfferModel::find($request->id);
            if (! $offer) {
                return Utility::apiError('Not found', [], 404);
            }
            $offer->delete();

            return Utility::apiSuccess('Deleted', [], 200);
        } catch (Exception $ex) {
            Log::error('Offer delete error: '.$ex->getMessage());

            return Utility::apiError('Failed', ['exception' => $ex->getMessage()], 500);
        }
    }
}
