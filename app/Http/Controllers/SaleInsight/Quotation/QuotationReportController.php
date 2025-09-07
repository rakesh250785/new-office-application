<?php

namespace App\Http\Controllers\SaleInsight\Quotation;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Quotation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;
class QuotationReportController extends Controller
{

    public function __construct()
    {
    }

    public function getQuotationReport(Request $request)
    {
        try {
            $data = $request->only([
                'per_page',
                'branch_list',
                'owner_list',
                'currency_list',
                'principal_list',
                'status_list',
                'search',
                'start_date',
                'end_date',
                'download',
            ]);

            $perPage = $data['per_page'] ?? config('constant.per_page', 15);

            $query = Quotation::select([
                'id',
                'unique_quotation_no',
                'lead_from',
                'branch_id',
                'owner_id',
                'currency_id',
                'company_id',
                'total_amount',
                'created_at',
                'billing_state_id',
                'billing_address',
                'billing_city',
                'billing_mobile',
                'billing_email',
                'billing_landline',
                'billing_pin_code',
                'billing_contact_person',
                'shipping_address',
                'shipping_city',
                'shipping_pin_code',
                'shipping_mobile',
                'shipping_email',
                'shipping_state_id',
                'shipping_landline',
                'product_description',
                'notification_id',
                'quotation_type_id',
                'payment_term_condition',
                'date',
                'prepard_by',
                'pdf_name',
                'enq_ref',
                'delivery_date_id',
                'is_order_pending',
            ])
                ->with([
                    'quotationDetails',
                    'quotationDetails.principal:id,type',
                    'companyDetails:id,company_name,email_id',
                    'branchDetails:id,name',
                    'currencyDetails:id,code',
                    'ownerDetails:id,name',
                    'pendingQuotationDetails:unique_quotation_no,quotation_id,reason,status_code,follow_up_date,total_amount,reason_status_id,last_updated_at',
                ])
                ->whereNull('deleted_at')
                ->when(!empty($data['branch_list']), fn($q) => $q->whereIn('branch_id', (array) $data['branch_list']))
                ->when(!empty($data['owner_list']), fn($q) => $q->whereIn('owner_id', (array) $data['owner_list']))
                ->when(!empty($data['currency_list']), fn($q) => $q->whereIn('currency_id', (array) $data['currency_list']))
                ->when(!empty($data['status_list']), fn($q) => $q->whereIn('is_order_pending', (array) $data['status_list']))
                ->when(!empty($data['principal_list']), fn($q) => $q->whereHas('quotationDetails', fn($d) => $d->whereIn('principal_id', (array) $data['principal_list'])))
                ->when(!empty($data['start_date']) && !empty($data['end_date']), function ($q) use ($data) {
                    $q->whereBetween('created_at', [
                        Carbon::parse($data['start_date'])->startOfDay(),
                        Carbon::parse($data['end_date'])->endOfDay()
                    ]);
                })
                ->when(!empty($data['search']), function ($q) use ($data) {
                    $term = $data['search'];
                    $q->where(function ($sub) use ($term) {
                        $sub->where('unique_quotation_no', 'like', "%{$term}%")
                            ->orWhere('lead_from', 'like', "%{$term}%")
                            ->orWhere('total_amount', 'like', "%{$term}%")
                            ->orWhereHas('ownerDetails', fn($o) => $o->where('name', 'like', "%{$term}%"))
                            ->orWhereHas('currencyDetails', fn($c) => $c->where('code', 'like', "%{$term}%"))
                            ->orWhereHas('companyDetails', fn($c) => $c->where('customer_name', 'like', "%{$term}%"))
                            ->orWhereHas('quotationDetails', function ($d) use ($term) {
                                $d->where('part_no', 'like', "%{$term}%")
                                    ->orWhereHas('principal', fn($p) => $p->where('type', 'like', "%{$term}%"));
                            });
                    });
                })
                ->orderByDesc('id');

            $quotationData = $query->paginate($perPage);

            return Utility::apiSuccess('list_quotation', $quotationData, 200);

        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Failed getQuotation server error', ['exception' => $ex->getMessage()], 500);
        }
    }
}