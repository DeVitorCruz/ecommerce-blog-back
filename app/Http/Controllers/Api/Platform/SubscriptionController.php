<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * GET /platform/staff/subscriptions
     * List all subscriptions with tenant and plan.
     * 
     * @return JsonResponse 200, paginated subscription list 
     */
    public function index(): JsonResponse
    {
        $subscriptions = Subscription::with(['tenant.owner', 'plan'])
            ->orderByDesc('created_at')
            ->paginate(25);

        return response()->json($subscriptions);
    }
    /**
     * GET /platform/staff/subscriptions/{subscription}
     * Full subscription detail.
     * 
     * @param Subscription $subscription
     * @return JsonResponse 200, subscription detail
     */
    public function show(Subscription $subscription): JsonResponse
    {
        return response()->json($subscription->load(['tenant.owner', 'plan']));
    }

    /**
     * PATCH /platform/staff/subscriptions/{subscription}
     * Update subscription plan or status (staff action).
     * 
     * @param Request $request
     * @param Subscription $subscription
     * @return JsonResponse 200, subscription updated
     */
    public function update(Request $request, Subscription $subscription): JsonResponse
    {
        $data = $request->validate([
            'plan_id' => 'sometimes|integer|exists:mercatura.plans,id',
            'status' => 'sometimes|string|in:active,cancelled,past_due,trialing',
            'current_period_start' => 'sometimes|date',
            'current_period_end' => 'sometimes|date|after:current_period_start',
            'cancelled_at' => 'nullable|date',
        ]);

        $subscription->update($data);

        return response()->json($subscription->fresh()->load(['tenant.owner', 'plan'])); 
    }

    /**
     * PATCH /platform/staff/subscriptions/{subscription}/cancel
     * Cancel a subscription and stamp cancelled_at.
     * 
     * @param Subscription $subscription
     * @return JsonResponse 200, subscription cancelled
     *                      422, already cancelled
     */
    public function cancel(Subscription $subscription): JsonResponse
    {
        if ($subscription->status === 'cancelled') {
            return response()->json(['message' => 'Subscription is already cancelled.'], 422);
        }

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return response()->json([
            'message' => 'Subscription cancelled.',
            'subscription' => $subscription->fresh()->load(['tenant.owner', 'plan']),
        ]); 
    }

    /**
     * PATCH /platform/staff/subscriptions/{subscription}/change-plan
     * Move a subscription to a different plan and reset the billing period.
     * 
     * @param Request $request
     * @param Subscription $subscription
     * @return JsonResponse 200, plan changed
     */
    public function changePlan(Request $request, Subscription $subscription): JsonResponse
    {
        $data = $request->validate([
            'plan_slug' => 'required|string|exists:mercatura.plans,slug',
        ]);

        $plan = Plan::where('slug', $data['plan_slug'])
            ->where('is_active', true)
            ->firstOrFail();
        
        $subscription->update([
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'cancelled_at' => null,
        ]);

        return response()->json([
            'message' => 'Plan changed.',
            'subscription' => $subscription->fresh()->load(['tenant.owner', 'plan']),
        ]);
    }
}
