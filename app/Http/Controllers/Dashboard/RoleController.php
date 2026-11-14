<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ModulePermissionTrait;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Support\Facades\Redirect;
use App\Models\Permission;

class RoleController extends Controller
{
    use ModulePermissionTrait;

    protected ?string $permissionResource = 'roles';

    protected array $permissionMapping = [
        'permissionIndex' => 'permissions.read',
        'permissionCreate' => 'permissions.create',
        'permissionStore' => 'permissions.create',
        'permissionEdit' => 'permissions.edit',
        'permissionUpdate' => 'permissions.edit',
        'permissionDestroy' => 'permissions.delete',
        'roleIndex' => 'roles.read',
        'roleCreate' => 'roles.create',
        'roleStore' => 'roles.create',
        'roleEdit' => 'roles.edit',
        'roleUpdate' => 'roles.edit',
        'roleDestroy' => 'roles.delete',
        'rolePermissionIndex' => 'roles.edit',
        'rolePermissionCreate' => 'roles.edit',
        'rolePermissionStore' => 'roles.edit',
        'rolePermissionEdit' => 'roles.edit',
        'rolePermissionUpdate' => 'roles.edit',
        'rolePermissionDestroy' => 'roles.edit',
    ];

    public function __construct()
    {
        $this->initializeModulePermission();
    }

    protected function permissionForAction(string $action): ?string
    {
        return $this->permissionMapping[$action] ?? null;
    }

    /**
     * Limpia la cache de permisos de Spatie tras cualquier cambio en roles o permisos.
     */
    protected function forgetPermissionCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Valida y normaliza los IDs de permisos enviados desde los formularios de roles.
     *
     * @return array<int, int>
     */
    protected function extractPermissionIds(Request $request): array
    {
        $validated = $request->validate([
            'permission_id' => 'nullable|array',
            'permission_id.*' => 'integer|exists:permissions,id',
        ]);

        return array_values(array_unique(array_map('intval', $validated['permission_id'] ?? [])));
    }
    // Permission Controller
    public function permissionIndex()
    {
        $permissions = QueryBuilder::for(Permission::class)->paginate();

        return view('roles.permission-index', [
            'permissions' => $permissions,
        ]);
    }

    public function permissionCreate()
    {
        return view('roles.permission-create');
    }

    public function permissionStore(Request $request)
    {
        $rules = [
            'name' => 'required|string',
            'display_name' => 'nullable|string|max:191',
            'group_name' => 'required|string',
        ];

        $validatedData = $request->validate($rules);

        Permission::create($validatedData);
        $this->forgetPermissionCache();

        return Redirect::route('permission.index')->with('success', 'Permission has been created!');
    }

    public function permissionEdit(Int $id)
    {
        $permission = Permission::findById($id);

        return view('roles.permission-edit', [
            'permission' => $permission,
        ]);
    }

    public function permissionUpdate(Request $request, Int $id)
    {
        $rules = [
            'name' => 'required|string',
            'display_name' => 'nullable|string|max:191',
            'group_name' => 'required|string',
        ];

        $validatedData = $request->validate($rules);

        Permission::findOrFail($id)->update($validatedData);
        $this->forgetPermissionCache();

        return Redirect::route('permission.index')->with('success', 'Permission has been updated!');
    }

    public function permissionDestroy(Int $id)
    {
        Permission::destroy($id);
        $this->forgetPermissionCache();

        return Redirect::route('permission.index')->with('success', 'Permission has been deleted!');
    }

    // Role Controller
    public function roleIndex()
    {
        $roles = QueryBuilder::for(Role::class)->paginate();

        return view('roles.role-index', [
            'roles' => $roles,
        ]);
    }

    public function roleCreate()
    {
        return view('roles.role-create');
    }

    public function roleStore(Request $request)
    {
        $rules = [
            'name' => 'required|string',
        ];

        $validatedData = $request->validate($rules);

        Role::create($validatedData);

        return Redirect::route('role.index')->with('success', 'Role has been created!');
    }

    public function roleEdit(Int $id)
    {
        $role = Role::findById($id);

        return view('roles.role-edit', [
            'role' => $role,
        ]);
    }

    public function roleUpdate(Request $request, Int $id)
    {
        $rules = [
            'name' => 'required|string',
        ];

        $validatedData = $request->validate($rules);

        Role::findOrFail($id)->update($validatedData);
        $this->forgetPermissionCache();

        return Redirect::route('role.index')->with('success', 'Role has been updated!');
    }

    public function roleDestroy(Int $id)
    {
        Role::destroy($id);
        $this->forgetPermissionCache();

        return Redirect::route('role.index')->with('success', 'Role has been deleted!');
    }

    public function rolePermissionIndex()
    {
        $roles = QueryBuilder::for(Role::class)->paginate();

        return view('roles.role-permission-index', [
            'roles' => $roles,
        ]);
    }


    // Role has Permissions
    public function rolePermissionCreate()
    {
        $roles = Role::all();
        $permissions = Permission::all();
        $permission_groups = User::getPermissionGroups();

        return view('roles.role-permission-create', [
            'roles' => $roles,
            'permissions' => $permissions,
            'permission_groups' => $permission_groups
        ]);
    }

    public function rolePermissionStore(Request $request)
    {
        $validated = $request->validate([
            'role_id' => 'required|integer|exists:roles,id',
        ]);

        $role = Role::findOrFail((int) $validated['role_id']);
        $role->syncPermissions($this->extractPermissionIds($request));
        $this->forgetPermissionCache();

        return Redirect::route('rolePermission.index')->with('success', 'Role Permission has been created!');
    }

    public function rolePermissionEdit(Int $id)
    {
        $role = Role::findOrFail($id);
        $permissions = Permission::all();
        $permission_groups = User::getPermissionGroups();

        return view('roles.role-permission-edit', [
            'role' => $role,
            'permissions' => $permissions,
            'permission_groups' => $permission_groups
        ]);
    }

    public function rolePermissionUpdate(Request $request, Int $id)
    {
        $role = Role::findOrFail($id);
        $role->syncPermissions($this->extractPermissionIds($request));
        $this->forgetPermissionCache();

        return Redirect::route('rolePermission.index')->with('success', 'Role Permission has been updated!');
    }

    public function rolePermissionDestroy(Int $id)
    {
        $role = Role::findOrFail($id);

        if (!is_null($role)) {
            $role->delete();
        }
        $this->forgetPermissionCache();

        return Redirect::route('rolePermission.index')->with('success', 'Role Permission has been deleted!');
    }
}
