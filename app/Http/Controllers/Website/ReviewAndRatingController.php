<?php

namespace App\Http\Controllers\Website;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Models\Website\ReviewModel;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ReviewAndRatingController extends Controller
{
    public function addOrUpdate(Request $request)
    {
        try {
            $rules = [
                'product_id' => 'required|integer|exists:products,id',
                'rating' => 'required|integer|min:1|max:5',
                'title' => 'nullable|string|max:191',
                'body' => 'nullable|string',
                'images' => 'nullable|array',
                'images.*' => 'nullable|string',
                'id' => 'nullable|integer|exists:product_reviews,id',
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            $userId = Auth::id() ?? null;
            $isUpdate = $request->filled('id');

            if ($isUpdate) {
                $review = ReviewModel::find($request->id);
                if (! $review) {
                    return Utility::apiError('Review not found', [], 404);
                }

                // only owner or admin can update
                $canEdit = ($review->user_id && $userId && $review->user_id == $userId)
                    || ($this->currentUserIsAdmin());
                if (! $canEdit) {
                    return Utility::apiError('Unauthorized to update review', [], 403);
                }

                $previouslyApproved = $review->status === 'approved';
            } else {
                $review = new ReviewModel;
                $previouslyApproved = false;
            }

            // fill fields (status preserved on update unless admin changes via adminApprove)
            $review->product_id = $request->product_id;
            $review->user_id = $userId;
            $review->rating = $request->rating;
            $review->title = $request->title ?? null;
            $review->body = $request->body ?? null;
            $review->images = $request->images ?? null;

            // on create, keep default status 'pending'; on update keep existing status
            if (! $isUpdate) {
                $review->status = config('reviews.auto_approve', false) && $this->currentUserIsAdmin() ? 'approved' : 'pending';
            }

            $review->save();

            // If review is approved now (either created approved or remained approved after update), recompute aggregates
            if ($review->status === 'approved' && ! $previouslyApproved) {
                $this->recomputeProductRating($review->product_id);
            } elseif ($previouslyApproved && $review->status !== 'approved') {
                // moved from approved -> something else
                $this->recomputeProductRating($review->product_id);
            } elseif ($previouslyApproved && $review->status === 'approved' && $isUpdate && $review->wasChanged('rating')) {
                // rating changed while approved -> recompute
                $this->recomputeProductRating($review->product_id);
            }

            return Utility::apiSuccess('Review saved', $review, 200);
        } catch (Exception $ex) {
            Log::error('ProductReview addOrUpdate error: '.$ex->getMessage());

            return Utility::apiError('Something went wrong', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function list(Request $request)
    {
        try {
            $data = $request->only(['product_id', 'page', 'per_page', 'status', 'sort']);
            $validator = Validator::make($data, [
                'product_id' => 'required|integer|exists:products,id',
                'page' => 'nullable|integer',
                'per_page' => 'nullable|integer',
                'status' => 'nullable|string|in:approved,pending,rejected,all',
                'sort' => 'nullable|string|in:helpful,newest',
            ]);
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            $page = max(1, (int) ($data['page'] ?? 1));
            $perPage = (int) ($data['per_page'] ?? config('constant.per_page', 15));
            $status = $data['status'] ?? 'approved';
            $sort = $data['sort'] ?? 'helpful';

            $query = ReviewModel::query()->where('product_id', $data['product_id'])->whereNull('deleted_at');

            if ($status !== 'all') {
                $query->where('status', $status);
            }

            if ($sort === 'helpful') {
                $query->orderByDesc('helpful_count')->orderByDesc('created_at');
            } else {
                $query->orderByDesc('created_at');
            }

            $paginator = $query->paginate($perPage, ['*'], 'page', $page);

            return Utility::apiSuccess('Reviews fetched', $paginator, 200);
        } catch (Exception $ex) {
            Log::error('ProductReview list error: '.$ex->getMessage());

            return Utility::apiError('Failed to fetch reviews', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function delete(Request $request)
    {
        try {
            $validator = Validator::make($request->only('id'), [
                'id' => 'required|integer|exists:product_reviews,id',
            ]);
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            $review = ReviewModel::find($request->id);
            if (! $review) {
                return Utility::apiError('Review not found', [], 404);
            }

            $userId = Auth::id() ?? null;
            $canDelete = ($review->user_id && $userId && $review->user_id == $userId) || $this->currentUserIsAdmin();
            if (! $canDelete) {
                return Utility::apiError('Unauthorized to delete review', [], 403);
            }

            $wasApproved = $review->status === 'approved';
            $productId = $review->product_id;

            $review->delete(); // soft delete

            if ($wasApproved) {
                $this->recomputeProductRating($productId);
            }

            return Utility::apiSuccess('Deleted successfully', [], 200);
        } catch (Exception $ex) {
            Log::error('ProductReview delete error: '.$ex->getMessage());

            return Utility::apiError('Failed to delete', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function markHelpful(Request $request)
    {
        try {
            $validator = Validator::make($request->only('id', 'vote'), [
                'id' => 'required|integer|exists:product_reviews,id',
                'vote' => 'required|string|in:up,down',
            ]);
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            $review = ReviewModel::find($request->id);
            if (! $review) {
                return Utility::apiError('Review not found', [], 404);
            }

            if ($request->vote === 'up') {
                $review->increment('helpful_count');
            } else {
                // ensure not negative
                if ($review->helpful_count > 0) {
                    $review->decrement('helpful_count');
                }
            }

            return Utility::apiSuccess('Vote recorded', $review, 200);
        } catch (Exception $ex) {
            Log::error('ProductReview markHelpful error: '.$ex->getMessage());

            return Utility::apiError('Failed to mark helpful', ['exception' => $ex->getMessage()], 500);
        }
    }

    public function adminApprove(Request $request)
    {
        try {
            $validator = Validator::make($request->only('id', 'status'), [
                'id' => 'required|integer|exists:product_reviews,id',
                'status' => 'required|string|in:approved,rejected,pending',
            ]);
            if ($validator->fails()) {
                return Utility::apiError('Validation failed', $validator->errors(), 221);
            }

            // simple admin guard - replace with middleware in production
            if (! $this->currentUserIsAdmin()) {
                return Utility::apiError('Unauthorized (admin only)', [], 403);
            }

            $review = ReviewModel::find($request->id);
            if (! $review) {
                return Utility::apiError('Review not found', [], 404);
            }

            $prev = $review->status;
            $review->status = $request->status;
            $review->save();

            // recompute aggregates if approval state changed affecting counts
            if ($prev !== 'approved' && $request->status === 'approved') {
                $this->recomputeProductRating($review->product_id);
            } elseif ($prev === 'approved' && $request->status !== 'approved') {
                $this->recomputeProductRating($review->product_id);
            }

            return Utility::apiSuccess('Review status updated', $review, 200);
        } catch (Exception $ex) {
            Log::error('ProductReview adminApprove error: '.$ex->getMessage());

            return Utility::apiError('Failed to update status', ['exception' => $ex->getMessage()], 500);
        }
    }

    /**
     * Recompute aggregated product rating row and persist to product_ratings table.
     * Uses DB aggregation to avoid race conditions.
     */
    protected function recomputeProductRating(int $productId)
    {
        // Use DB aggregation for accuracy and performance
        $rows = DB::table('product_reviews')
            ->selectRaw('COUNT(*) as total,
                         AVG(rating) as avg_rating,
                         SUM(CASE WHEN rating=5 THEN 1 ELSE 0 END) as r5,
                         SUM(CASE WHEN rating=4 THEN 1 ELSE 0 END) as r4,
                         SUM(CASE WHEN rating=3 THEN 1 ELSE 0 END) as r3,
                         SUM(CASE WHEN rating=2 THEN 1 ELSE 0 END) as r2,
                         SUM(CASE WHEN rating=1 THEN 1 ELSE 0 END) as r1')
            ->where('product_id', $productId)
            ->where('status', 'approved')
            ->whereNull('deleted_at')
            ->first();

        $data = [
            'avg_rating' => $rows->avg_rating ? round($rows->avg_rating, 2) : 0,
            'total_reviews' => (int) $rows->total,
            'rating_5' => (int) $rows->r5,
            'rating_4' => (int) $rows->r4,
            'rating_3' => (int) $rows->r3,
            'rating_2' => (int) $rows->r2,
            'rating_1' => (int) $rows->r1,
            'updated_at' => now(),
        ];

        // upsert into product_ratings (use DB query for race-safety)
        $exists = DB::table('product_ratings')->where('product_id', $productId)->exists();

        if ($exists) {
            DB::table('product_ratings')->where('product_id', $productId)->update($data);
        } else {
            DB::table('product_ratings')->insert(array_merge(['product_id' => $productId], $data));
        }
    }

    /**
     * Helper: check if current user is admin.
     * Replace this with your project's proper admin-check (roles/permissions).
     */
    protected function currentUserIsAdmin(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        // common property; change to your implementation (roles/permissions)
        return property_exists($user, 'is_admin') ? (bool) $user->is_admin : ($user->hasRole ? ($user->hasRole('admin') ?? false) : false);
    }

    /**
     * GET /api/product/{id}/rating
     * returns the rating summary + optional top reviews (approved)
     */
    public function getProductRating(Request $request, $productId)
    {
        try {
            // fetch rating summary (fast)
            $summary = \DB::table('product_ratings')->where('product_id', $productId)->first();

            // fallback if no row exists yet (zeroed)
            if (! $summary) {
                $summary = (object) [
                    'product_id' => (int) $productId,
                    'avg_rating' => 0.0,
                    'total_reviews' => 0,
                    'rating_5' => 0,
                    'rating_4' => 0,
                    'rating_3' => 0,
                    'rating_2' => 0,
                    'rating_1' => 0,
                    'updated_at' => null,
                ];
            }

            // fetch top 3 approved reviews sorted by helpful_count (or newest)
            $topReviews = ReviewModel::where('product_id', $productId)
                ->where('status', 'approved')
                ->whereNull('deleted_at')
                ->orderByDesc('helpful_count')
                ->orderByDesc('created_at')
                ->limit(3)
                ->get(['id', 'user_id', 'rating', 'title', 'body', 'images', 'helpful_count', 'created_at']);

            return Utility::apiSuccess('Product rating fetched', [
                'summary' => $summary,
                'top_reviews' => $topReviews,
            ], 200);
        } catch (Exception $ex) {
            Log::error('getProductRating error: '.$ex->getMessage());

            return Utility::apiError('Failed to fetch product rating', ['exception' => $ex->getMessage()], 500);
        }
    }
}
