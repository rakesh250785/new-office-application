<?php

namespace App\Http\Controllers\SaleInsight\Invoice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Invoice;
use App\Helpers\Utility;
use Carbon\Carbon;
use Exception;
use Symfony\Component\ErrorHandler\Debug;

class InvoiceController extends Controller
{
    public function addUpdateInvoice(Request $request)
    {
        try {
            # Get specific fields 
            $data = $request->only(['partial_order_id', 'current_follow_date', 'docket_no', 'product_invoice_list', 'invoice_no', 'customer_id', 'customer_order_no']);

            # Validation rule
            $validator = Validator::make($request->all(), [
                'partial_order_id' => 'required|integer|exists:partial_orders,id',
                'invoice_no' => 'required|string|max:255',
                'docket_no' => 'required|string|max:255',
                'product_invoice_list' => 'required|array',
                'product_invoice_list.*.invoice' => 'file|max:10240',
            ]);

            # Validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            # Handle product invoice files
            $docs = [];
            if ($request->has('product_invoice_list')) {
                foreach ($request->file('product_invoice_list', []) as $index => $row) {
                    if (isset($row['invoice']) && $row['invoice'] instanceof \Illuminate\Http\UploadedFile) {
                        $file = $row['invoice'];
                        $extension = $file->getClientOriginalExtension();
                        $path = public_path('orderinvoicedocs/');
                        $rename_file = 'order_invoice_' . date('Y-m-d') . '_' . time() . '.' . $extension;
                        $file->move($path, $rename_file);
                        $docs[] = $rename_file;
                    }
                }
            }

            # Update or create invoice
            $invoice = Invoice::updateOrCreate(
                ['partial_order_id' => $data['partial_order_id'] ?? null],
                [
                    'invoice_date' => $data['current_follow_date'] ?? null,
                    'customer_order_no' => $data['customer_order_no'] ?? null,
                    'customer_id' => $data['customer_id'] ?? null,
                    'invoice_no' => $data['invoice_no'] ?? null,
                    'docket_no' => $data['docket_no'] ?? null,
                    'invoice_docs' => implode(',', $docs),
                    'branch_id' => Auth::user()->branch_id,
                    'user_id' => Auth::id(),
                ]
            );

            # Return if fail to insert
            if (!$invoice) {
                return Utility::apiError('Upload failed', [], 221);
            }

            # Return response
            return Utility::apiSuccess('Invoice uploaded successfully', [], 200);

        } catch (Exception $e) {
            Log::error($e);
            return Utility::apiError('Failed to upload invoice', ['exception' => $e->getMessage()]);
        }
    }
    public function getInvoice(Request $request)
    {
        try {
            $data = $request->only(['per_page', 'search', 'date_range']);
    
            $query = Invoice::with(['partialOrder', 'customerDetails'])
                ->whereNull('deleted_at')
                ->orderBy('id', 'DESC');
    
            // Date range filter
            if (!empty($data['date_range'])) {
                $dates = explode('|', $data['date_range']);
                $from_date = (new Carbon($dates[0]))->startOfDay();
                $to_date = (new Carbon($dates[1]))->endOfDay();
                $query->whereBetween('created_at', [$from_date, $to_date]);
            }
    
            // Search filter
            if (!empty($data['search'])) {
                $search = $data['search'];
                $query->where(function ($q) use ($search) {
                    $q->whereHas('customerDetails', function ($q2) use ($search) {
                        $q2->where('company_name', 'like', "%$search%");
                    })
                    ->orWhereHas('partialOrder', function ($q2) use ($search) {
                        $q2->where('customer_order_no', 'like', "%$search%");
                    })
                    ->orWhere('invoice_no', 'like', "%$search%");
                });
            }
    
            $perPage = $request->get('per_page', 10);
            $invoiceData = $query->paginate($perPage);
    
            $invoiceData->getCollection()->transform(function ($invoice) {
                $invoice->invoice_docs = collect(explode(',', $invoice->invoice_docs))
                    ->filter()
                    ->map(fn($doc, $idx) => [
                        'id' => $idx + 1,
                        'file' => $doc,
                        'download_url' => url("/orderinvoicedocs/download/{$doc}")
                    ])->values();
    
                return $invoice;
            });
    
            return Utility::apiSuccess('list_invoices', $invoiceData, 200);
    
        } catch (Exception $ex) {
            Log::error($ex);
            return Utility::apiError('Error deleting invoice', ['exception' => $e->getMessage()]);
        }
    }
    



    public function deleteInvoice(Request $request)
    {
        try {
            # Get specific fields
            $data = $request->only(['partial_order_id']);

            # Validation rule
            $validator = Validator::make($data, [
                'partial_order_id' => 'required|integer|exists:invoice,partial_order_id',
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            # Delete invoice
            $deleted = Invoice::where('id', $data['partial_order_id'])->delete();

            # Return if fail
            if (!$deleted) {
                return Utility::apiError('Invoice deletion failed', [], 221);
            }

            # Return response
            return Utility::apiSuccess('Invoice deleted successfully', [], 200);
        } catch (Exception $e) {
            Log::error($e);
            return Utility::apiError('Error deleting invoice', ['exception' => $e->getMessage()]);
        }
    }
}
