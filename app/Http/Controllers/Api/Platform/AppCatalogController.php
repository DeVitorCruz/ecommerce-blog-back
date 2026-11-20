<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Models\AppType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AppCatalogController extends Controller
{   
    /**
     * GET /platform/staff/app-types
     * List all app types with tenant app count.
     * 
     * @return JsonResponse 200, pagined app type list
     */
    public function index(): JsonResponse
    {
        $this->authorize('apps.catalog.view');

        $types = AppType::withCount('tenantApps')
            ->orderBy('name')
            ->get();

        return response()->json($types);
    }

    /**
     * GET /platform/staff/app-types/{app-type}
     * Show a single app type with its themes.
     * 
     * @param AppType $appType
     * @return JsonResponse 200, app type detail
     */
    public function show(AppType $appType): JsonResponse
    {   
        $this->authorize('apps.catalog.view');

        return response()->json(
            $appType->load('themes')->loadCount('tenantApps')
        );
    }

    /**
     * POST /platform/staff/app-types
     * Create a new app type.
     * 
     * @param Request $request
     * @return JsonResponse 201, app type created
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('apps.catalog.create');

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'required|string|max:50|alpha_dash|unique:mercatura.app_types,slug',
            'description' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:100',
            'db_template' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        $appType = AppType::create($data);

        return response()->json([
            'message' => 'App type created.',
            'app_type' => $appType,
        ], 201);
    }

    /**
     * PATCH /platform/staff/app-types/{app-type}
     * Update an existing app type.
     * 
     * @param Request $request
     * @param AppType $appType
     * @return JsonResponse 200, app type updated
     */
    public function update(Request $request, AppType $appType): JsonResponse
    {
        $this->authorize('apps.catalog.edit');

        $appType->update($request->validate([
            'name' => 'sometimes|string|max:100',
            'slug' => 'sometimes|string|max:50|alpha_dash|unique:mercatura.app_types,slug',
            'description' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:100',
            'db_template' => 'nullable|string|max:100',
            'is_active' => 'sometimes|boolean',
        ]));

        return response()->json([
            'message' => 'App type updated.',
            'app_type' => $appType->fresh()->loadCount('tenantApps'),
        ]);
    }

    /**
     * DELETE /platform/staff/app-types/{app-type}
     * Delete an app type. Blocked if tenants have apps of this type.
     * 
     * @param AppType $appType
     * @return JsonResponse 200, app type deleted
     *                      409, app type in use
     */
    public function destroy(AppType $appType): JsonResponse
    {
        $this->authorize('apps.catalog.delete');

        if ($appType->tenantApps()->exists()) {
            return response()->json([
                'message' => 'Cannot delete: tenants have apps of this type.',
            ], 409);
        }

        $appType->delete();

        return response()->json(['message' => 'App type deleted.']);
    }   
}
