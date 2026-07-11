<?php

namespace App\Http\Controllers\Traits;

trait ModulePermissionTrait
{
    protected function getPermissionResource(): ?string
    {
        return property_exists($this, 'permissionResource') ? $this->permissionResource : null;
    }

    protected function getPermissionMapping(): array
    {
        if (property_exists($this, 'permissionMapping')) {
            return $this->permissionMapping;
        }

        return [
            'index' => 'read',
            'show' => 'read',
            'create' => 'create',
            'store' => 'create',
            'edit' => 'edit',
            'update' => 'edit',
            'destroy' => 'delete',
        ];
    }

    protected function initializeModulePermission(): void
    {
        $this->middleware(function ($request, $next) {
            $resource = $this->getPermissionResource();
            if (!$resource) {
                return $next($request);
            }

            $action = $request->route()->getActionMethod();
            $permission = $this->permissionForAction($action);

            if ($permission && !auth()->user()?->can($permission)) {
                abort(403);
            }

            return $next($request);
        });
    }

    protected function permissionForAction(string $action): ?string
    {
        $mapping = $this->getPermissionMapping();
        $resource = $this->getPermissionResource();

        if (isset($mapping[$action]) && $resource) {
            return sprintf('%s.%s', $resource, $mapping[$action]);
        }

        return null;
    }
}
