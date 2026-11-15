<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Plan;

class PlanController extends Controller
{
    /**
     * GET /platform/plans
     * Public - show on pricing page.
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return response()->json(
            Plan::where('is_active', true)
                ->orderBy('price_monthly')
                ->get()
        );
    }

    /**
     * POST /platform/staff/plans
     * Create a new plan (owner only).
     * 
     * @param Request $request
     * @return JsonResponse 201, plan created
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'required|string|max:50|unique:mercatura.plans,slug|alpha_dash',
            'description' => 'nullable|string|max:255',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly' => 'required|numeric|min:0',
            'max_apps' => 'required|integer|min:1',
            'max_products' => 'required|integer|min:1',
            'max_domains' => 'required|integer|min:1',
            'features' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $plan = Plan::create($data);

        return response()->json([
            'message' => 'Plan created.',
            'plan' => $plan,
        ], 201);
    }

    /**
     * PATCH /platform/staff/plans/{plan}
     * Update an existing plan (owner only).
     * 
     * @param Request $request
     * @param Plan $plan
     * @return JsonResponse 200, plan updated
     */
    public function update(Request $request, Plan $plan): JsonResponse 
    {
        $plan->update($request->validate([
            'name' => 'sometimes|string|max:100',
            'slug' => 'sometimes|string|max:50|unique:mercatura.plans,slug|alpha_dash',
            'description' => 'nullable|string|max:255',
            'price_monthly' => 'sometimes|numeric|min:0',
            'price_yearly' => 'sometimes|numeric|min:0',
            'max_apps' => 'sometimes|integer|min:1',
            'max_products' => 'sometimes|integer|min:1',
            'max_domains' => 'sometimes|integer|min:1',
            'features' => 'nullable|array',
            'is_active' => 'boolean',
        ]));
        
        return response()->json($plan->fresh());
    }

    /**
     * GET /platform/plans 
     * 
     * @param  Plan         $plan
     * @return JsonResponse 
     */
    public function show(Plan $plan): JsonResponse 
    {
        return response()->json($plan);
    }

    /**
     * DELETE /platform/staff/plans/{plan}
     * Hard-delete a plan. Blocked if any active subscriptions reference it.
     * 
     * @param Plan $plan
     * @return JsonResponse 
     */
    public function destroy(Plan $plan): JsonResponse
    {
        if ($plan->subscriptions()->where('status', 'active')->exists()) {
            return response()->json([
                'message' => 'Cannot delete a plan with active subscriptions.',
            ], 409);
        }

        $plan->delete();

        return response()->json(['message' => 'Plan deleted.']);
    }
}
