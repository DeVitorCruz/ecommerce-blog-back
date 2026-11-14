<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformUser;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class PlatformUserController extends Controller
{   
    /**
     * GET /platform/staff/users
     * List all platform users with their roles.
     * 
     * @return JsonResponse 200, paginated user list
     */
    public function index(): JsonResponse
    {
        $users = PlatformUser::with('tenant')
            ->withCount('tenant')
            ->orderByDesc('created_at')
            ->paginate(25);

        return response()->json($users);
    }

    /**
     * GET /platform/staff/users/{user}
     * Full user detail with roles, permissions and tenant.
     * 
     * @param PlatformUser $user
     * @return JsonResponse 200, user detail
     */
    public function show(PlatformUser $user): JsonResponse
    {
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),            
            'tenant' => $user->tenant,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ]);
    }

    /**
     * POST /platform/staff/users
     * Create a new platform user (staff action).
     * 
     * @param Request $request
     * @return JsonResponse 201, user created
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
           'name' => 'required|string|max:191',
           'email' => 'required|email|max:191|unique:mercatura.users,email',
           'password' => 'required|string|min:8|confirmed',
           'role' => 'nullable|string|in:owner,admin,editor,support,tenant'
        ]);

        $user = PlatformUser::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),            
        ]);

        if (!empty($data['role'])) {
            $user->assignRole($data['role']);
        }

        return response()->json([
            'message' => 'User created.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ],
        ], 201);
    }

    /**
     * PATCH /platform/staff/users/{user}
     * Update user name, email or password (staff action).
     * 
     * @param Request $request
     * @param PlatformUser $user
     * @return JsonResponse 200, user updated
     */
    public function update(Request $request, PlatformUser $user): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:191',
            'email' => 'sometimes|email|max:191|unique:mercatura.users,email,' . $user->id,
            'password' => 'sometimes|string|min:8|confirmed',
        ]);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames(),
        ]);
    }

    /**
     * DELETE /platform/staff/users/{user}
     * Hard-delete a platform user.
     * 
     * @param PlatformUser $user
     * @return JsonResponse 200, user deleted
     */
    public function destroy(PlatformUser $user): JsonResponse
    {
        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'User deleted.']);
    }

    /**
     * 
     * @param Request $request
     * @param PlatformUser $user
     * @return JsonResponse
     */
    public function syncRoles(Request $request, PlatformUser $user): JsonResponse 
    {
        $data = $request->validate([
            'roles' => 'required|array|min:1',
            'roles.*' => 'string|in:owner,admin,editor,support,tenant',
        ]);

        $user->syncRoles($data['roles']);

        return response()->json([
            'message' => 'Roles updated.',
            'id' => $user->id,
            'name' => $user->name,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }
}
