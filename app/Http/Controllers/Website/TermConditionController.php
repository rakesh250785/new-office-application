<?php

namespace App\Http\Controllers\Website;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Website\TermConditionModel;
use Auth;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TermConditionController extends Controller
{
    public function addUpdateTermCondition(Request $request)
    {
        try {
            $rules = [
                'id' => 'nullable|integer|exists:blog,id',
                'content' => 'nullable|string',
            ];

            $validator = Validator::make($request->all(), $rules);

            if (! $request->filled('id') && ! $request->hasFile('image')) {
                $validator->after(function ($v) {
                    $v->errors()->add('image', 'Image is required for new gallery item.');
                });
            }

            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            $record = $request->filled('id') ? TermConditionModel::find($request->input('id')) : new TermConditionModel;

            if (! $record) {
                return Utility::apiError('Record not found', [], 404);
            }
            $record->content = $request->input('content');
            $record->user_id = Auth::id() ?? null;
            $record->save();

            $msg = $request->filled('id') ? 'Updated successfully' : 'Created successfully';

            return Utility::apiSuccess($msg, [], 200);

        } catch (Exception $ex) {
            Log::error('TermCondition add/update error: '.$ex->getMessage());

            return Utility::apiError('Something went wrong', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function getTermCondition(Request $request)
    {
        try {
            $rec = TermConditionModel::first();

            return Utility::apiSuccess('TermCondition list fetched successfully', $rec, 200);

        } catch (Exception $ex) {
            Log::error('TermCondition get error: '.$ex->getMessage());

            return Utility::apiError('Something went wrong', ['exception' => $ex->getMessage()], 500);
        }
    }
}
