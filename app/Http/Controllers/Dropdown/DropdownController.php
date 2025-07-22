<?php

namespace App\Http\Controllers\Dropdown;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\PrincipalType;
use Illuminate\Support\Facades\Auth;
use App\Models\Brand;
use App\Models\Currency;
use App\Models\Owner;
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
            $query = Owner::whereNull('deleted_at')->orderBy('name', 'ASC');

            # Permission condition
            if (!Auth::user()->hasPermission('branch_all')) {
                $query->where('branch_id', Auth::user()->branch_id);
            }

            # Get records
            $owners = $query->get()->toArray();

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

    }

    public function getStateDD()
    {

    }

    public function getCustomerClassificationDD()
    {

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

    public function getStatusDD()
    {

    }

    public function getPrincipalDD()
    {
        try {
            # Get principal
            $principal = Principal::pluck('name', 'id')->toArray();

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
            $currency = Currency::pluck('name', 'code')->toArray();

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

    public function getPartNumberDD()
    {
        try {
            $product = Product::select('id', 'part_no')
                ->groupBy('part_no', 'id')
                ->get()
                ->pluck('part_no', 'id')
                ->toArray();
            return Utility::apiSuccess('DD principal', $product, 200);
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

    }

    public function getCategoryDD()
    {

    }

    public function getUpsTypeDD()
    {

    }

    public function getUpsCategoryDD()
    {

    }

    public function getProductFieldDD()
    {

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
