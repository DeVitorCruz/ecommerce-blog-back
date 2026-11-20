<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BillingController extends Controller
{   

    /**
     * GET /platform/staff/billing
     * List all subscriptions with billing detail.
     * 
     * @return JsonResponse 200, paginated billing list
     */
    public function index(): JsonResponse
    {
        $this->authorize('billing.view');

        $subscriptions = Subscription::with(['tenant.owner', 'plan'])
            ->orderByDesc('created_at')
            ->paginate(25);

        return response()->json($subscriptions);
    }

    /**
     * GET /platform/staff/billing/{subscription}
     * Full billing detail for a single subscription
     * 
     * @param Subscription $subscription
     * @return JsonResponse 200, subscription billing detail
     */
    public function show(Subscription $subscription): JsonResponse
    {   
        $this->authorize('billing.view');

        return response()->json(
            $subscription->load(['tenant.owner', 'plan'])
        );
    }       

    /**
     * PATCH /platform/staff/billing/{subscription}
     * Update gateway binding or manually override subscription status.
     * Used by staff when processing manual payment or migrating gateways.
     * 
     * @param Request $request
     * @param Subscription $subscription
     * @return JsonResponse 200, subscription updated
     */
    public function update(Request $request, Subscription $subscription): JsonResponse
    {
        $this->authorize('billing.manage');

        $data = $request->validate([
            'gateway' => 'sometimes|nullable|string|max:50',
            'gateway_subscription_id' => 'sometimes|nullable|string|max:255',
            'status' => 'sometimes|string|in:active,cancelled,past_due,trialing',
            'current_period_start' => 'sometimes|date',
            'current_period_end' => 'sometimes|date|after:current_period_start',
        ]);

        $subscription->update($data);

        return response()->json([
            'message' => 'Billing updated.',
            'subscription' => $subscription->fresh()->load(['tenant.owner', 'plan']),
        ]); 
    }

    /**
     * GET /platform/staff/billing/tenant/{tenant}
     * Billing summary for a specific tenant.
     * Convenience endpoint for the tenant detail page.
     * 
     * @param Tenant $tenant
     * @return JsonResponse 200, tenant billing detail
     *                      404, tenant has no subscription
     */
    public function tenant(Tenant $tenant): JsonResponse 
    {
        $this->authorize('billing.view');

        $subscription = $tenant->subscription()->with('plan')->first();

        if (!$subscription) {
            return response()->json([
                'message' => 'Tenant has no subscription',
            ], 404);
        }

        return response()->json([
            'tenant' => $tenant->load('owner'),
            'subscription' => $subscription,
            'plan' => $subscription->plan,
            'is_active' => $subscription->isActive(),
            'days_left' => now()->diffInDays($subscription->current_period_end, false),
        ]);
    }
}
