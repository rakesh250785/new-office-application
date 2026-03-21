<?php

namespace App\Http\Controllers\SaleInsight\Invoice;

use App\Exports\InvoiceExport;
use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\PartialOrder;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class InvoiceController extends Controller
{
    public function addUpdateInvoice(Request $request)
    {
        try {
            // Get specific fields
            $data = $request->only(['partial_order_id', 'current_follow_date', 'docket_no', 'product_invoice_list', 'invoice_no', 'customer_id', 'customer_order_no']);

            // Validation rule
            $validator = Validator::make($request->all(), [
                'partial_order_id' => 'required|integer|exists:partial_orders,id',
                'invoice_no' => 'required|string|max:255',
                'docket_no' => 'required|string|max:255',
                'product_invoice_list' => 'required|array',
                'product_invoice_list.*.invoice' => 'file|max:10240',
            ]);

            // Validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            // Handle product invoice files
            $docs = [];
            if ($request->has('product_invoice_list')) {
                foreach ($request->file('product_invoice_list', []) as $index => $row) {
                    if (isset($row['invoice']) && $row['invoice'] instanceof \Illuminate\Http\UploadedFile) {
                        $file = $row['invoice'];
                        $extension = $file->getClientOriginalExtension();
                        $path = public_path('orderinvoicedocs/');
                        $rename_file = 'order_invoice_'.date('Y-m-d').'_'.time().'.'.$extension;
                        $file->move($path, $rename_file);
                        $docs[] = $rename_file;
                    }
                }
            }

            // Update or create invoice
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

            $status = PartialOrder::where('id', $data['partial_order_id'])->update(['invoice_id' => $invoice?->id]);

            if (! $status) {
                return Utility::apiError('Upload failed partial invoice id', [], 221);
            }

            if (! $invoice) {
                return Utility::apiError('Upload failed', [], 221);
            }

            // Return response
            return Utility::apiSuccess('Invoice uploaded successfully', [], 200);

        } catch (Exception $e) {
            Log::error($e);

            return Utility::apiError('Failed to upload invoice', ['exception' => $e->getMessage()]);
        }
    }

    public function getInvoice(Request $request)
    {
        try {

            // Request specific fields
            $data = $request->only([
                'branch_list',
                'owner_list',
                'currency_list',
                'principal_list',
                'start_date',
                'end_date',
                'search',
                'per_page',
                'page',
                'download',
            ]);

            if (! empty($data['download'])) {
                $columns = [
                    'invoice_no' => 'Invoice No',
                    'created_at' => 'Invoice Date',
                    'partial_order_no' => 'Partial Order No',
                    'customer_order_no' => 'Customer Order No',
                    'customer' => 'Customer',
                ];

                $filename = 'invoice_'.now()->format('Ymd_His').'.xlsx';
                (new InvoiceExport($data, $columns))->queue("exports/{$filename}", 'public');

                $fileUrl = url("storage/exports/{$filename}");

                return Utility::apiSuccess('Export started. You will get a download link soon.', [
                    'file' => $filename,
                    'url' => $fileUrl,
                ]);
            }
            // Get invoice data
            $query = Invoice::with(['partialOrder', 'customerDetails'])
                ->whereNull('deleted_at')
                ->orderBy('id', 'DESC');

            // Apply filters (arrays are expected from frontend; cast to array to be safe)
            if (! empty($data['branch_list'])) {
                $query->where('branch_id', (array) $data['branch_list']);
            }

            if (! empty($data['owner_list'])) {
                $query->whereHas('customerDetails.owner', function ($q) use ($data) {
                    $q->where('id', (array) $data['owner_list']);
                });
            }

            if (! empty($data['currency_list'])) {
                $query->whereHas('partialOrder.orderDetails', function ($q) use ($data) {
                    $q->where('currency_id', (array) $data['currency_list']);
                });
            }

            if (! empty($data['principal_list'])) {
                $query->whereHas('partialOrder.orderDetails', function ($q) use ($data) {
                    $q->where('principal_id', (array) $data['principal_list']);
                });
            }

            if (Utility::checkViewPermission('invoice')) {
                $query->where('user_id', Auth::id());
            }

            if (
                Utility::checkViewPermission('invoice') ||
                Utility::checkBranchesViewPermission('invoice')
            ) {
                $query->where(function ($q) {
    
                    if (Utility::checkViewPermission('invoice')) {
                        $q->orWhere('user_id', Auth::id());
                    }
    
                    if (Utility::checkBranchesViewPermission('invoice')) {
                        $q->orWhere('branch_id', Auth::user()->branch_id);
                    }
                });
            }

            // Date range handling:
            if (! empty($data['start_date']) && ! empty($data['end_date'])) {
                $query->whereBetween('created_at', [
                    Carbon::parse($data['start_date'])->startOfDay(),
                    Carbon::parse($data['end_date'])->endOfDay(),
                ]);
            }

            // Search filter
            if (! empty($data['search'])) {
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

            // Get data
            $perPage = $request->get('per_page', 10);
            $invoiceData = $query->paginate($perPage);

            // Map invoice
            $invoiceData->getCollection()->transform(function ($invoice) {
                $invoice->invoice_docs = collect(explode(',', $invoice->invoice_docs))
                    ->filter()
                    ->map(fn ($doc, $idx) => [
                        'id' => $idx + 1,
                        'file' => $doc,
                        'download_url' => url("/orderinvoicedocs/{$doc}"),
                    ])->values();

                return $invoice;        
            });     

            // Return response
            return Utility::apiSuccess('list_invoices', $invoiceData, 200);

        } catch (Exception $ex) {
            Log::error($ex);

            return Utility::apiError('Error deleting invoice', ['exception' => $ex->getMessage()]);
        }
    }

    public function deleteInvoice(Request $request)
    {
        try {
            // Get specific fields
            $data = $request->only(['id']);

            // Validation rule
            $validator = Validator::make($data, [
                'id' => 'required|integer|exists:invoices,id',
            ]);

            // Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 422);
            }

            // Get invoice
            $invoiceRec = Invoice::where('id', $data['id'])->first();

            // Return if fail
            if (! $invoiceRec) {
                return Utility::apiError('Invoice not found', [], 221);
            }

            $updateStatus = PartialOrder::where('id', $invoiceRec['partial_order_id'])->update([
                'invoice_id' => null,
            ]);

            if (! $updateStatus) {
                return Utility::apiError('Fail to update partial order invoice field', [], 221);
            }

            $delStatus = $invoiceRec->delete();

            if (! $delStatus) {
                return Utility::apiError('Fail to delete invoice', [], 221);
            }

            // Return response
            return Utility::apiSuccess('Invoice deleted successfully', [], 200);
        } catch (Exception $e) {
            Log::error($e);

            return Utility::apiError('Error deleting invoice', ['exception' => $e->getMessage()]);
        }
    }
}
