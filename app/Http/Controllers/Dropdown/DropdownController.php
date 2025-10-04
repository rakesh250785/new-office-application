<?php

namespace App\Http\Controllers\Dropdown;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Classification;
use App\Models\Country;
use App\Models\Courier;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\NotificationEmail;
use App\Models\OrderReason;
use App\Models\Owner;
use App\Models\Parameter;
use App\Models\PaymentDayAdvance;
use App\Models\Principal;
use App\Models\PrincipalType;
use App\Models\Product;
use App\Models\QuotationType;
use App\Models\ReasonType;
use App\Models\Role;
use App\Models\Source;
use App\Models\States;
use DB;
use Exception;
use Illuminate\Http\Request;
use Log;

class DropdownController extends Controller
{
    public function __construct()
    {
        //
    }

    public function getOwnerDD()
    {
        try {
            // Get owner list
            $owners = Owner::whereNull('deleted_at')->orderBy('name', 'asc')->pluck('name', 'id');

            // Return response
            return Utility::apiSuccess('DD Owner', $owners, 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Something went wrong in getOwnerDD', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getSourceDD()
    {
        try {
            // Get source
            $source = Source::pluck('name', 'id')->toArray();

            // Return response
            return Utility::apiSuccess('DD source', $source, 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Something went wrong in getSourceDD', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getCustomerDD() {}

    public function getCountryDD()
    {
        try {
            // Get source
            $source = Country::pluck('name', 'id')->toArray();

            // Return response
            return Utility::apiSuccess('DD Country', $source, 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Something went wrong in getCountryDD', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getStatusDD()
    {
        try {
            // Get source
            $statusList = ReasonType::pluck('type', 'id');

            // Return response
            return Utility::apiSuccess('DD StatusDD', $statusList, 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Something went wrong in public function getStatusDD', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getClassificationDD()
    {
        try {
            // Get source
            $brands = Classification::whereNull('deleted_at')->pluck('name', 'id');

            // Return response
            return Utility::apiSuccess('DD classification', $brands, 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Something went wrong in public function getClassificationDD', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getBranchDD()
    {
        try {
            // Get source
            $brands = Branch::whereNull('deleted_at')->pluck('name', 'id');

            // Return response
            return Utility::apiSuccess('DD Branch', $brands, 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Something went wrong in     public function getBranchDD', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getCourierDD()
    {
        try {
            // Get source
            $brands = Courier::whereNull('deleted_at')->pluck('name', 'id');

            // Return response
            return Utility::apiSuccess('DD Courier', $brands, 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Something went wrong in getCourierDD', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getStateDD()
    {
        try {

            // Get principal
            $states = States::pluck('name', 'id')->toArray();

            // Return response
            return Utility::apiSuccess('DD state', $states, 200);

        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Something went wrong in getStatusDD', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getPrincipalDD()
    {
        try {
            // Get principal
            $principal = Principal::pluck('type', 'id')->toArray();

            // Return response
            return Utility::apiSuccess('DD principal', $principal, 200);

        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Something went wrong in getPrincipalDD', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getCurrencyDD()
    {
        try {
            // Get currency
            $currency = Currency::pluck('name', 'id')->toArray();

            // Return response
            return Utility::apiSuccess('DD Currency', $currency, 200);

        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Something went wrong in getPrincipalDD', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getNotificationDD()
    {
        try {
            // Get currency
            $notifiaction = NotificationEmail::pluck('name', 'id')->toArray();

            // Return response
            return Utility::apiSuccess('DD getNotificationDD', $notifiaction, 200);

        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Something went wrong in getNotificationDD', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getCompanyDD()
    {
        try {
            // Get currency
            $currency = Customer::whereNull('deleted_at')->select('id', 'customer_name', 'company_name', 'address', 'owner_id', 'state_id', 'other_state', 'city', 'email_id', 'pin_code', 'mobile_no', 'landline_no')
                ->with(['owner:id,name'])->get();

            // Return response
            return Utility::apiSuccess('DD getCompanyDD', $currency, 200);

        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Something went wrong in getCompanyDD', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getProductDD(Request $request)
    {
        try {
            $data = $request->only('search');

            $whereClause = '';
            $bindings = [];

            if (! empty($data['search'])) {
                $whereClause = 'WHERE part_no LIKE ?';
                $bindings[] = '%'.$data['search'].'%';
            }

            $subQuery = "
        SELECT MAX(id) AS id
        FROM products
        $whereClause
        GROUP BY part_no
    ";

            $products = Product::with(['principal:id,type'])
                ->join(DB::raw("($subQuery) AS latest"), 'products.id', '=', 'latest.id')
                ->select('products.id', 'products.part_no', 'products.description', 'products.principal_id', 'products.hsn_no', 'products.description', 'products.quantity', 'products.price', 'products.discount', 'products.price as net_price', 'products.igst_rate')
                ->addBinding($bindings, 'select')
                ->get();

            // Return response
            return Utility::apiSuccess('DD partnodd', $products, 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Something went wrong in getPartNumberDD', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getQuotationTypeDD()
    {
        try {
            // Get quotation type
            $quotationType = QuotationType::pluck('type', 'id')->toArray();

            // Return response
            return Utility::apiSuccess('DD getQuotationFormatDD', $quotationType, 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Something went wrong in getPartNumberDD', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getQuotationFormatDD() {}

    public function getPaymentAdvanceDD()
    {
        try {
            // Get quotation type
            $quotationType = PaymentDayAdvance::pluck('date_type', 'id')->toArray();

            // Return response
            return Utility::apiSuccess('DD getPaymentAdvanceDD', $quotationType, 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Something went wrong in getPaymentAdvanceDD', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getReasonDD() {}

    public function getBrandDD()
    {
        try {
            // Get type
            $brand = Brand::pluck('name', 'id')->toArray();

            // Return response
            return Utility::apiSuccess('DD brand', $brand, 200);

        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Something went wrong in getBrandDD', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getCategoryDD()
    {
        try {
            // Get type
            $category = Category::pluck('name', 'id')->toArray();

            // Return response
            return Utility::apiSuccess('DD brand', $category, 200);

        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Something went wrong in getCategoryDD', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getUpsCategoryDD()
    {
        try {
            // Get type
            $type = Category::whereIn('name', ['HPLC Columns', 'GC Capillary Column'])
                ->pluck('name', 'id')
                ->toArray();

            // Return response
            return Utility::apiSuccess('DD Usp Type', $type, 200);

        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Something went wrong in getUpsCategoryDD', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getUpsTypeDD() {}

    public function getParameterFieldDD()
    {
        try {
            // Get principal
            $parameterFields = Parameter::whereNull('deleted_at')->pluck('parameter_name', 'id');

            // Return response
            return Utility::apiSuccess('DD getParameterFieldDD', $parameterFields, 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Something went wrong in getPartNumberDD', ['exception' => $ex->getMessage()], 500);
        }

    }

    public function getPrincipalTypeDD()
    {
        try {
            // Get principal
            $principal = PrincipalType::whereNull('deleted_at')->pluck('type', 'id');

            // Return response
            return Utility::apiSuccess('DD PrincipalType', $principal, 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Something went wrong in getPartNumberDD', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getRoleDD()
    {
        try {
            // Get principal
            $role = Role::pluck('name', 'id');

            // Return response
            return Utility::apiSuccess('DD RoleDD', $role, 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Something went wrong in RoleDD', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getOrderStatusDD()
    {
        try {
            // Get status
            $role = OrderReason::where('order_type', 'pending_order')->pluck('name', 'id');

            // Return response
            return Utility::apiSuccess('DD OrderStatusDD', $role, 200);
        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Something went wrong in OrderStatusDD', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getModuleDD() {}
}
