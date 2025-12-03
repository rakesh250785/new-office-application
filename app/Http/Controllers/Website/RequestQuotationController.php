<?php

namespace App\Http\Controllers\Website;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Website\EnquiryItem;
use App\Models\Website\RequestQuotationModel;
use Auth;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RequestQuotationController extends Controller
{
    public function addUpdateEnquiry(Request $request)
    {
        try {
            $rules = [
                'company_name' => 'nullable|string|max:255',
                'person_name' => 'required|string|max:191',
                'email' => 'nullable|email|max:191',
                'mobile' => 'nullable|string|max:32',
                'address' => 'nullable|string',
                'gst_percent' => 'nullable|numeric|min:0',
                'items' => 'required|array|min:1',
                'items.*.part_no' => 'nullable|string|max:191',
                'items.*.description' => 'nullable|string',
                'items.*.qty' => 'required|integer|min:1',
                'items.*.amount' => 'required|numeric|min:0',
                'items.*.discount' => 'nullable|numeric|min:0',
                'id' => 'nullable|integer|exists:enquiries,id',
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            $data = $request->only(['company_name', 'person_name', 'email', 'mobile', 'address', 'gst_percent']);

            $gstPercent = isset($data['gst_percent']) ? (float) $data['gst_percent'] : (float) config('app.default_gst', 18.0);

            // compute totals
            $items = $request->input('items', []);
            $subtotal = 0;
            $discountTotal = 0;

            foreach ($items as $it) {
                $qty = (int) $it['qty'];
                $amount = (float) $it['amount'];
                $discount = isset($it['discount']) ? (float) $it['discount'] : 0.0;
                $lineTotal = ($amount * $qty) - $discount;
                $subtotal += ($amount * $qty);
                $discountTotal += $discount;
            }

            $taxable = $subtotal - $discountTotal;
            $gstAmount = round($taxable * ($gstPercent / 100), 2);
            $totalAmount = round($taxable + $gstAmount, 2);

            $payload = array_merge($data, [
                'subtotal' => round($subtotal, 2),
                'discount_total' => round($discountTotal, 2),
                'gst_amount' => $gstAmount,
                'total_amount' => $totalAmount,
                'gst_percent' => $gstPercent,
            ]);

            $enquiry = $request->filled('id') ? RequestQuotationModel::find($request->input('id')) : new RequestQuotationModel;
            if ($request->filled('id') && ! $enquiry) {
                return Utility::apiError('Enquiry not found', [], 404);
            }

            $enquiry->fill($payload);
            $enquiry->created_by = Auth::id() ?? $enquiry->created_by ?? null;
            $enquiry->save();

            if ($request->filled('id')) {
                EnquiryItem::where('enquiry_id', $enquiry->id)->delete();
            }

            foreach ($items as $index => $it) {
                $qty = (int) $it['qty'];
                $amount = (float) $it['amount'];
                $discount = isset($it['discount']) ? (float) $it['discount'] : 0.0;
                $lineTotal = round(($amount * $qty) - $discount, 2);

                $item = EnquiryItem::create([
                    'enquiry_id' => $enquiry->id,
                    'part_no' => $it['part_no'] ?? null,
                    'description' => $it['description'] ?? null,
                    'qty' => $qty,
                    'amount' => round($amount, 2),
                    'discount' => round($discount, 2),
                    'total' => $lineTotal,
                ]);

                if (! $item) {
                    return Utility::apiError('Fail_to_add_enquery_item', [], 221);
                }
            }

            return Utility::apiSuccess('Saved successfully', [], 200);
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error('Enquiry add/update error: '.$ex->getMessage());

            return Utility::apiError('Something went wrong', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getEnquiryList(Request $request)
    {
        try {
            $data = $request->only(['page', 'per_page', 'search', 'start_date', 'end_date', 'status']);
            $page = max(1, (int) ($data['page'] ?? 1));
            $perPage = (int) ($data['per_page'] ?? config('constant.per_page', 15));
            $q = $data['search'] ?? null;

            $query = RequestQuotationModel::with(['items'])->orderByDesc('created_at');

            if (! empty($q)) {
                $query->where(function ($qb) use ($q) {
                    $qb->where('company_name', 'like', "%$q%")
                        ->orWhere('person_name', 'like', "%$q%")
                        ->orWhere('email', 'like', "%$q%");
                });
            }

            if (! empty($data['status'])) {
                $query->where('status', $data['status']);
            }

            if (! empty($data['start_date'])) {
                $query->whereDate('created_at', '>=', $data['start_date']);
            }
            if (! empty($data['end_date'])) {
                $query->whereDate('created_at', '<=', $data['end_date']);
            }

            $paginator = $query->paginate($perPage, ['*'], 'page', $page);

            return Utility::apiSuccess('Enquiry list fetched', $paginator, 200);
        } catch (Exception $ex) {
            Log::error('Enquiry get error: '.$ex->getMessage());

            return Utility::apiError('Failed to fetch enquiries', ['exception' => $ex->getMessage()], 500);
        }
    }

    // show single enquiry
    public function getEnqueryDetails($id)
    {
        try {
            $enquiry = RequestQuotationModel::with(['items'])->find($id);

            return Utility::apiSuccess('Enquiry fetched', $enquiry, 200);
        } catch (Exception $ex) {
            Log::error('Enquiry show error: '.$ex->getMessage());

            return Utility::apiError('Failed to fetch enquiry', ['exception' => $ex->getMessage()], 500);
        }
    }

    // delete enquiry
    public function removeEnquery(Request $request)
    {
        try {
            $validator = Validator::make($request->only('id'), [
                'id' => 'required|integer|exists:enquiries,id',
            ]);
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }
            $record = RequestQuotationModel::find($request->input('id'));
            if (! $record) {
                return Utility::apiError('Not found', [], 404);
            }

            EnquiryItem::where('enquiry_id', $record->id)->delete();

            $record->delete();

            return Utility::apiSuccess('Deleted successfully', [], 200);
        } catch (Exception $ex) {
            Log::error('Enquiry delete error: '.$ex->getMessage());
            return Utility::apiError('Delete failed', ['exception' => $ex->getMessage()], 500);
        }
    }
}
