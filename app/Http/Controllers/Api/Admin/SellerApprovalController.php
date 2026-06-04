<?php
 namespace App\Http\Controllers\Api\Admin;

 use App\Http\Controllers\Controller;
 use App\Http\Resources\SellerResource;
 use App\Models\Seller;
 use Illuminate\Http\JsonResponse;

 /**
  * Handles admin approval and rejection of seller onboarding requests.
  * 
  * All endpoints require authentication and the 'admin' role via
  * the EnsureUserIsAdmin middleware applied at the route level.
  */
 class SellerApprovalController extends Controller
 {
    /**
     * List all seller profiles pending admin review.
     * 
     * Returns sellers in ascending order of submission date
     * so the oldest request are reviewed first (FIFO).
     * 
     * @return JsonResponse 200 with a collection of pending SellerResource.
     */
    public function index(): JsonResponse
    {
        $sellers = Seller::with('user')
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'sellers' => SellerResource::collection($sellers),
        ]);
    }

    /**
     * Approve a pending seller onboarding request.
     * 
     * Sets the seller status to 'approved', allowing them to list
     * products on the marketplace.
     * Only sellers with 'pending' status can be approved.
     * 
     * @param Seller $seller The seller to approve (route model binding).	
     * @return JsonResponse 200 with updated SellerResource,
     *                      422 if the seller is not in pending state.
     */
    public function approve(Seller $seller): JsonResponse
    {
        if ($seller->status !== 'pending') {
            return response()->json([
                'message' => 'Seller is not pending approval.',
            ], 422);
        }

        $seller->update(['status' => 'approved']);

        return response()->json([
            'message' => 'Seller approved successfully.',
            'seller' => new SellerResource($seller),
        ]);
    }

    /**
     * Reject a pending seller onboarding request.
     * 
     * Sets the seller status to 'rejected', preventing them from
     * listing products on the marketplace.
     * Only sellers with 'pending' status can be rejected.
     * 
     * @param Seller $seller The seller to reject (route model binding).
     * @return JsonResponse 200 with updated SellerResource,
     *                      422 if the seller is not in pending state.
     */
    public function reject(Seller $seller): JsonResponse
    {
        if ($seller->status !== 'pending') {
            return response()->json([
                'message' => 'Seller is not pending approval.',
            ], 422);
        }

        $seller->update(['status' => 'rejected']);

        return response()->json([
            'message' => 'Seller rejected.',
            'seller' => new SellerResource($seller),
        ]);
    }
}
