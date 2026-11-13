<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class StaffTenantController extends Controller
{   
    /**
     * GET /platform/staff/tenants
     * List all tenants with owner, subscription and app count.
     * 
     * @return JsonResponse 200, paginated tenant list
     */
    public function index(): JsonResponse
    {
        $tenants = Tenant::with(['owner', 'subscription.plan'])
            ->withCount('apps')
            ->orderByDesc('created_at')
            ->paginate(25);

        return response()->json($tenants);
    }

    /**
     * POST /platform/staff/tenants
     * Create a tenant on behalf of a platform user (staff action).
     * 
     * @param Request $request
     * @return JsonResponse 201, tenant created
     *                      409, user already has a tenant
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => 'required|integer|exists:mercatura.users,id',
            'name' => 'required|string|max:100',
            'slug' => 'required|string|max:50|unique:mercatura.tenants,slug|alpha_dash',
            'plan_slug' => 'nullable|string|exists:mercatura.plans,slug'
        ]);
        
        if (Tenant::where('user_id', $data['user_id'])->exists()) {
            return response()->json([
                'message' => 'This user already has a tenant account.'
            ], 409);
        }

        $plan = Plan::where('slug', $data['plan_slug'] ?? 'free')
            ->where('is_active', true)
            ->firstOrFail();

        $tenant = Tenant::create([
            'user_id' => $data['user_id'],
            'name' => $data['name'],
            'slug' => Str::slug($data['slug']),
            'status' => 'active',
            'trial_ends_at' => null,
        ]);

        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        // Assign tenant role to the platform user
        $tenant->owner->assignRole('tenant');

        return response()->json([
            'message' => 'Tenant created.',
            'tenant' => $tenant->load(['owner', 'subscription.plan']),
        ], 201);
    }

    /**
     * GET /platform/staff/tenants/{tenant}
     * Full tenant details include apps and domains.
     * 
     * @param Tenant $tenant
     * @return JsonResponse 200, tenant detail
     */
    public function show(Tenant $tenant): JsonResponse
    {   
        return response()->json(
            $tenant->load(['owner', 'subscription.plan', 'apps.appType', 'apps.theme', 'apps.domains'])
        );
    }

    /**
     * PATCH /platform/staff/tenants/{tenant}
     * Update tenant name or slug (staff action).
     * 
     * @param Request $request
     * @param Tenant $tenant
     * @return JsonResponse 200, tenant updated
     */
    public function update(Request $request, Tenant $tenant): JsonResponse 
    {
        $tenant->update($request->validate([
          'name' => 'sometimes|string|max:100',
          'slug' => 'sometimes|string|max:50|alpha_dash|unique:mercatura.tenants,slug,' . $tenant->id,
        ]));

        return response()->json($tenant->fresh()->load('owner'));
    }

    /**
     * DELETE /platform/staff/tenants/{tenant}
     * Hard-delete a tenant record (owner only).
     * 
     * @param Tenant $tenant
     * @return JsonResponse 200,
     */
    public function destroy(Tenant $tenant): JsonResponse 
    {
        $tenant->delete();

        return response()->json(['message' => 'Tenant deleted.']);
    }

    /**
     * PATCH /platform/staff/tenants/{tenant}/suspend 
     * Suspend an active or trial tenant.
     * 
     * @param Tenant $tenant
     * @return JsonResponse 200, tenant suspended
     *                      422, already suspended
     */
    public function suspend(Tenant $tenant): JsonResponse
    {
        if ($tenant->isSuspended()) {
            return response()->json(['message' => 'Tenant is already suspended.'], 422);
        }

        $tenant->suspend();

        return response()->json([
            'message' => 'Tenant suspended.',
            'tenant' => $tenant->fresh(),
        ]);
    }

    /**
     * PATCH /platform/staff/tenants/{tenant}/activate
     * Recativate a suspended tenant.
     * 
     * @param Tenant $tenant
     * @return JsonResponse 200, tenant activated
     *                      422, not suspended
     */
    public function activate(Tenant $tenant): JsonResponse
    {
        if (!$tenant->isSuspended()) {
            return response()->json(['message' => 'Tenant is not suspended.'], 422);
        }

        $tenant->activate();

        return response()->json([
            'message' => 'Tenant activated.',
            'tenant' => $tenant->fresh(),
        ]);
    }
}
