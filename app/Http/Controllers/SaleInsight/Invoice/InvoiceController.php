<?php

namespace App\Http\Controllers\SaleInsight\Invoice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Invoice;
use App\Models\PartialOrder;
use App\Helpers\Utility;
use Carbon\Carbon;
use Exception;

class InvoiceController extends Controller
{
    public function getInvoices(Request $request)
    {
        try {

            # Get specific fields
            $data = $request->only(['per_page', 'search', 'date_range']);

            # Get field in variable
            $perPage = $data['per_page'] ?? 15;
            $search = $data['search'] ?? null;
            $dateRange = $data['date_range'] ?? null;

            # Get invoice
            $invoices = Invoice::with(['partialOrder.customer', 'partialOrder.order'])
                ->where('deleted_at', 0)
                ->when(Auth::user()->branch_id != 1, function ($query) {
                    return $query->whereHas('partialOrder.order', function ($q) {
                        $q->where('branch_id', Auth::user()->branch_id);
                    });
                })
                ->when($search, function ($query, $search) {
                    $query->whereHas('partialOrder.customer', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })->orWhereHas('partialOrder', function ($q) use ($search) {
                        $q->where('order_no', 'like', "%{$search}%");
                    })->orWhere('invoice_no', 'like', "%{$search}%");
                })
                ->when($dateRange, function ($query, $dateRange) {
                    [$from, $to] = explode('|', $dateRange);
                    return $query->whereBetween('created_at', [Carbon::parse($from), Carbon::parse($to)->addDay()]);
                })
                ->orderByDesc('id')
                ->paginate($perPage);

            # Return response
            return Utility::apiSuccess('Invoice list fetched successfully', $invoices);
        } catch (Exception $e) {
            Log::error($e);
            return Utility::apiError('Failed to fetch invoices', ['exception' => $e->getMessage()]);
        }
    }

    public function uploadInvoice(Request $request)
    {
        try {
            # Get specific fields
            $data = $request->only(['partial_order_id', 'invoice_no', 'courier_doc', 'invoice_doc']);

            # Validation rule
            $validator = Validator::make($request->all(), [
                'partial_order_id' => 'required|integer|exists:partial_order,id',
                'invoice_no' => 'required|string|max:255',
                'courier_doc' => 'nullable|string|max:255',
                'invoice_doc.*' => 'file|mimes:pdf',
            ]);

            # Return validation error
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            # Get partial order
            $partialOrder = PartialOrder::where('id', $data['partial_order_id'])
                ->where('branch_id', Auth::user()->branch_id)
                ->first();

            # If not exist return
            if (!$partialOrder) {
                return Utility::apiError('Partial order not found or unauthorized', [], 404);
            }

            # Manage invoice
            $docs = [];
            if ($request->hasFile('invoicedocs')) {
                foreach ($request->file('invoicedocs') as $pdf) {
                    $filename = 'Order_Invoice_' . now()->format('Ymd_His') . '_' . uniqid() . '.pdf';
                    $pdf->move(public_path('orderinvoicedocs'), $filename);
                    $docs[] = $filename;
                }
            }

            # Update or create invoice
            $invoice = Invoice::updateOrCreate(
                ['partial_order_id' => $data['partial_order_id']],
                [
                    'invoice_no' => $data['invoice_no'],
                    'courier_doc' => $data['courier_doc'],
                    'invoice_docs' => implode(',', array: $docs),
                    'created_at' => Carbon::now(),
                    'branch_id' => $partialOrder['branch_id'],
                    'deleted_at' => null,
                ]
            );

            # Return if fail
            if (!$invoice) {
                return Utility::apiError('Upload fail', [], 221);
            }

            # Return repsonse
            return Utility::apiSuccess('Invoice uploaded successfully', $invoice, 201);
        } catch (Exception $e) {
            Log::error($e);
            return Utility::apiError('Failed to upload invoice', ['exception' => $e->getMessage()]);
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
