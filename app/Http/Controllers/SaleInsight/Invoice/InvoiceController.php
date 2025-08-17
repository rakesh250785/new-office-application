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
    public function getInvoice(Request $request)
    {
        try {
            $data = $request->all();
            $query = Invoice::select(
                'tbl_invoice.in_invoice_id',
                'p.st_cust_order_num',
                'tbl_invoice.st_invoice_docs',
                'tbl_invoice.dt_created',
                'tbl_invoice.st_invoice_no',
                'tbl_invoice.in_partorder_id',
                'p.in_cust_id',
                'p.st_courier_option',
                'p.flt_ord_total',
                'c.st_com_name'
            )
                ->join('tbl_partlyorder_gent as p', 'tbl_invoice.in_partorder_id', '=', 'p.in_partparaint_ord_id')
                ->join('tbl_customer as c', 'p.in_cust_id', '=', 'c.in_cust_id')
                ->join('tbl_order as o', 'o.in_order_id', '=', 'p.in_partlyorder_id')
                ->where('tbl_invoice.flg_deleted', 0)
                ->orderBy('tbl_invoice.in_invoice_id', 'DESC');

            // branch filter
            if (Auth::user()->branch_id != 1) {
                $query->where('o.in_branch_id', Auth::user()->branch_id);
            }

            // date range filter
            if (!empty($data['date_range'])) {
                $d = explode('|', $data['date_range']);
                $from_date = (new Carbon($d[0]))->format('Y-m-d');
                $to_date = (new Carbon($d[1]))->addDay()->format('Y-m-d');

                $query->whereBetween('tbl_invoice.dt_created', [$from_date, $to_date]);
            }

            // search filter
            if (!empty($data['search'])) {
                $search = $data['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('c.st_com_name', 'like', "%$search%")
                        ->orWhere('p.st_cust_order_num', 'like', "%$search%")
                        ->orWhere('tbl_invoice.st_invoice_no', 'like', "%$search%");
                });
            }

            // pagination (default 10 per page)
            $perPage = $request->get('per_page', 10);
            $invoices = $query->paginate($perPage);

            // format output
            $invoices->getCollection()->transform(function ($invoice) {
                $actions = [];
                if (!empty($invoice->st_invoice_docs)) {
                    $doc_list = array_filter(explode(',', $invoice->st_invoice_docs));
                    $key = 1;
                    foreach ($doc_list as $doc) {
                        $link = url('/orderinvoicedocs/' . $doc);
                        $actions[] = [
                            'index' => $key,
                            'doc' => $doc,
                            'url' => $link
                        ];
                        $key++;
                    }
                }
                $invoice->documents = $actions;
                unset($invoice->st_invoice_docs);

                return $invoice;
            });

            return response()->json([
                'success' => true,
                'data' => $invoices
            ]);

        } catch (Exception $ex) {
            Log::error($ex);
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong'
            ], 500);
        }
    }


    public function addUpdateInvoice(Request $request)
    {
        try {
            # Get specific fields 
            $data = $request->only(['partial_order_id', 'current_follow_date', 'docket_no', 'product_invoice_list', 'invoice_no']);

            # Validation rule
            $validator = Validator::make($request->all(), [
                'partial_order_id' => 'required|integer|exists:partial_orders,id',
                'invoice_no' => 'required|string|max:255',
                'docket_no' => 'required|string|max:255',
                'product_invoice_list'   => 'required|array',        
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
                        $extension = $row->getClientOriginalExtension();
                        $path = public_path('orderinvoicedocs/');
                        $rename_file = 'order_invoice_' . date('Y-m-d') . '_' . time() . '.' . $extension;
                        $file_list .= ',' . $rename_file;
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
