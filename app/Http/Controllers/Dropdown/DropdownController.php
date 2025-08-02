<?php

namespace App\Http\Controllers\Dropdown;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CategoryType;
use App\Models\Category;
use App\Models\Classification;
use App\Models\Parameter;
use App\Models\PrincipalType;
use App\Models\QuotationType;
use App\Models\Usp;
use Illuminate\Support\Facades\Auth;
use App\Models\Brand;
use App\Models\Currency;
use App\Models\Owner;
use App\Models\Country;
use App\Models\States;
use App\Models\Principal;
use App\Models\Product;
use App\Models\Source;
use Exception;
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
            # Get owner list
            $owners = Owner::whereNull('deleted_at')->orderBy('name', 'asc')->pluck('name', 'id');

            # Return response
            return Utility::apiSuccess('DD Owner', $owners, 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Something went wrong in getOwnerDD', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getSourceDD()
    {
        try {
            # Get source
            $source = Source::pluck('name', 'id')->toArray();

            # Return response
            return Utility::apiSuccess('DD source', $source, 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Something went wrong in getSourceDD', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getCustomerDD()
    {

    }

    public function getCountryDD()
    {
        try {
            # Get source
            $source = Country::pluck('name', 'id')->toArray();

            # Return response
            return Utility::apiSuccess('DD Country', $source, 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Something went wrong in getCountryDD', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getStatusDD()
    {

    }

    public function getClassificationDD()
    {
        try {
            # Get source
            $brands = Classification::whereNull('deleted_at')->pluck('name', 'id');

            # Return response
            return Utility::apiSuccess('DD classification', $brands, 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Something went wrong in public function getClassificationDD', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getBranchDD()
    {
        try {
            # Get source
            $brands = Branch::whereNull('deleted_at')->pluck('name', 'id');

            # Return response
            return Utility::apiSuccess('DD Branch', $brands, 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Something went wrong in     public function getBranchDD', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getStateDD()
    {
        try {

            # Get principal
            $states = States::pluck('name', 'id')->toArray();

            # Return response
            return Utility::apiSuccess('DD state', $states, 200);

        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Something went wrong in getStatusDD', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getPrincipalDD()
    {
        try {
            # Get principal
            $principal = Principal::pluck('type', 'id')->toArray();

            # Return response
            return Utility::apiSuccess('DD principal', $principal, 200);

        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Something went wrong in getPrincipalDD', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getCurrencyDD()
    {
        try {
            # Get currency
            $currency = Currency::pluck('name', 'id')->toArray();

            # Return response
            return Utility::apiSuccess('DD Currency', $currency, 200);

        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Something went wrong in getPrincipalDD', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getNotificationGroupDD()
    {

    }

    public function getCompanyDD()
    {

    }

    public function getProductDD()
    {
        try {
            $products = Product::select('id', 'part_no', 'description', 'principal_id')
                ->with(['principal:id,type'])
                ->get()
                ->unique('part_no');
            return Utility::apiSuccess('DD partnodd', $products, 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Something went wrong in getPartNumberDD', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getQuotationTypeDD()
    {
        try {
            # Get quotation type
            $quotationType = QuotationType::pluck('name', 'id')->toArray();

            # Return response
            return Utility::apiSuccess('DD getQuotationFormatDD', $quotationType, 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Something went wrong in getPartNumberDD', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getQuotationFormatDD()
    {

    }

    public function getPaymentAdvanceDD()
    {

    }

    public function getReasonDD()
    {

    }

    public function getBrandDD()
    {
        try {
            # Get type
            $brand = Brand::pluck('name', 'id')->toArray();

            # Return response
            return Utility::apiSuccess('DD brand', $brand, 200);

        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Something went wrong in getBrandDD', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getCategoryDD()
    {
        try {
            # Get type
            $category = Category::pluck('name', 'id')->toArray();

            # Return response
            return Utility::apiSuccess('DD brand', $category, 200);

        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Something went wrong in getCategoryDD', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getUpsCategoryDD()
    {
        try {
            # Get type
            $type = CategoryType::pluck('type', 'id')->toArray();

            # Return response
            return Utility::apiSuccess('DD Usp Type', $type, 200);

        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Something went wrong in getUpsCategoryDD', ['exception' => $ex->getMessage()], 500);
        }
    }


    public function getUpsTypeDD()
    {

    }

    public function getParameterFieldDD()
    {
        try {
            # Get principal
            $parameterFields = Parameter::whereNull('deleted_at')->pluck('parameter_name', 'id');

            # Return response
            return Utility::apiSuccess('DD getParameterFieldDD', $parameterFields, 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Something went wrong in getPartNumberDD', ['exception' => $ex->getMessage()], 500);
        }

    }

    public function getPrincipalTypeDD()
    {
        try {
            # Get principal
            $principal = PrincipalType::whereNull('deleted_at')->pluck('type', 'id');

            # Return response
            return Utility::apiSuccess('DD PrincipalType', $principal, 200);
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Something went wrong in getPartNumberDD', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getRoleDD()
    {

    }

    public function getPermissionDD()
    {

    }

    public function getModuleDD()
    {

    }
}
