<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Models\AppType;
use App\Models\DomainMapping;
use App\Models\Tenant;
use App\Models\TenantApp;
use App\Models\Theme;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class AppController extends Controller
{
    /**
     * GET /platform/apps
     * List tenant's apps.
     * 
     * @param  Request      $request, user request apps list
     * @return JsonResponse 200, list apps
     */
    public function index(Request $request): JsonResponse
    {
        $tenant = Tenant::where('user_id', $request->user()->id)->firstOrFail();

        return response()->json(
            $tenant->apps()->with(['appType', 'theme', 'domains'])->get()
        );
    }

    /**
     * POST /platform/apps
     * Create a new app for the tenant.
     * 
     * @param  Request      $request, user request to create app
     * @return JsonResponse 201, app successfully created
     */
    public function store(Request $request): JsonResponse 
    {
        $user = $request->user();

        $tenant = Tenant::where('user_id', $user->id)
            ->with('subscription.plan')
            ->firstOrFail();
        
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'app_type_slug' => 'required|exists:app_types,slug',
            'theme_slug' => 'nullable|exists:themes,slug',
        ]);

        // Check plan limits
        $plan = $tenant->subscription->plan;
        $appCount = $tenant->apps()->count();

        if ($appCount >= $plan->max_apps) {
            return response()->json([
                'message' => "Your plan allows max {$plan->max_apps} app(s). Upgrade to create more.",
            ], 403);
        }

        $appType = AppType::where('slug', $data['app_type_slug'])
            ->where('is_active', true)
            ->firstOrFail();
        
        // Get theme - defualt if not specified
        $theme = $data['theme_slug']
            ? Theme::where('slug', $data['theme_slug'])->where('app_type_id', $appType->id)->firstOrFail()
            : Theme::where('app_type_id', $appType->id)->where('is_default', true)->firstOrFail();

        // Generate unique DB name
        $dbName = 'mercatura_tenant_' . $tenant->id . '_' . Str::random(6);
        $slug = Str::slug($data['name']) . '_' . Str::random(4);

        $tenantApp = TenantApp::create([
            'tenant_id' => $tenant->id,
            'app_type_id' => $appType->id,
            'theme_id' => $theme->id,   
            'name' => $data['name'],
            'slug' => $slug,
            'db_name' => $dbName,
            'status' => 'provisioning',
        ]);

        // Create subdomain automatically
        $subdomain = $tenant->slug . '-' . $slug . '.mercatura.devitor.local';
        DomainMapping::create([
            'tenant_app_id' => $tenantApp->id,
            'domain' => $subdomain,
            'type' => 'subdomain',
            'is_primary' => true,
            'is_verified' => true,
            'verified_at' => now(),
        ]);

        // Provision tenant DB
        $this->provisionDatabase($dbName);
        
        // Mark app as active
        $tenantApp->update(['status' => 'active']);

        return response()->json([
            'message' => 'App created and provisioned.',
            'app' => $tenantApp->fresh()->load(['appType', 'theme', 'domains']),
            'subdomain' => $subdomain,
        ], 201);
    }

    /**
     * PATCH /platform/apps/{tenantApp}/theme
     * Switch theme for an existing app.
     * 
     * @param  Request      $request
     * @param  TenantApp    $tenantApp
     * @return JsonResponse 200, theme successfully switched
     *                      403, unauthorized.
     */
    public function switchTheme(Request $request, TenantApp $tenantApp): JsonResponse
    {
        $tenant = Tenant::where('user_id', $request->user()->id)->firstOrFail();

        if ($tenantApp->tenant_id !== $tenant->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $data = $request->validate([
            'theme_slug' => 'required|exists:themes,slug',
        ]);

        $theme = Theme::where('slug', $data['theme_slug'])
            ->where('app_type_id', $tenantApp->app_type_id)
            ->firstOrFail();

        $tenantApp->update(['theme_id' => $theme->id]);

        return response()->json([
            'message' => 'Theme switched successfully.',
            'app' => $tenantApp->fresh()->load(['appType', 'theme']),
        ]);
    }

    // Private

    /**
     * Give necessary database
     * 
     * @param  string $dbName
     * @return void
     */
    private function provisionDatabase(string $dbName): void
    {
        // Use root connection for DB creation and grants
        $rootPdo = new \PDO(
            'mysql:host=' . config('database.connections.mercatura.host') .
            ';port=' . config('database.connections.mercatura.port'),
            'root',
            env('DB_ROOT_PASSWORD', 'root')
        );

        // Grant permssions to new DB
        $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        $rootPdo->exec("GRANT ALL PRIVILEGES ON `{$dbName}`.* TO 'devitor'@'%'");
        $rootPdo->exec("FLUSH PRIVILEGES");

        // Run tenant migration on new DB
        Artisan::call('migrate:tenant', [
            'db' => $dbName,
        ]);
    }
}
