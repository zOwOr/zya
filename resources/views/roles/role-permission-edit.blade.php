@extends('dashboard.body.main')

@section('specificpagestyles')
    <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
@endsection

@section('container')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <div class="header-title">
                        <h4 class="card-title">Editar Rol en la Lista de Permiso</h4>
                    </div>
                </div>

                <div class="card-body">
                    <form action="{{ route('rolePermission.update', $role->id) }}" method="POST">
                    @csrf
                    @method('put')
                        <!-- begin: Input Data -->
                        <div class=" row align-items-center mb-2">
                            <div class="form-group col-md-6">
                                <label for="role_id">Rol</label>
                                <h4>{{ $role->name }}</h4>
                                {{-- <input type="text" class="form-control" value="{{ $role->name }}" readonly> --}}
                            </div>
                        </div>

                        <div class="row align-items-center">
                            <div class="form-group col-md-6">
                                <label for="name">Permiso <span class="text-danger">*</span></label>
                            </div>
                        </div>


                        <div class="row">
                            <div class="form-group col-md-3">
                                <div class="custom-control custom-checkbox custom-control-inline">
                                    <input type="checkbox" class="custom-control-input" id="check-all">
                                    <label class="custom-control-label" for="check-all">Seleccionar todo</label>
                                </div>
                            </div>
                        </div>

                        <hr>

                        @foreach ($permission_groups as $permission_group)
                        @php
                            $permissions = App\Models\User::getPermissionByGroupName($permission_group->group_name);
                        @endphp

                        <div class="row permission-group-row">
                            <div class="form-group col-md-3">
                                <div class="custom-control custom-checkbox custom-control-inline">
                                    <input
                                        type="checkbox"
                                        class="custom-control-input permission-group-checkbox"
                                        id="permission_group_id[{{ $loop->iteration }}]"
                                        name="permission_group_id[]"
                                        {{ App\Models\User::roleHasPermission($role, $permissions) ? 'checked' : '' }}
                                    >
                                    <label
                                        for="permission_group_id[{{ $loop->iteration }}]"
                                        class="custom-control-label font-weight-bold"
                                    >
                                        {{ App\Models\Permission::groupLabel($permission_group->group_name) }}
                                    </label>
                                </div>
                            </div>

                            <div class="form-group col-md-9">
                                @foreach ($permissions as $permission)
                                    <div class="custom-control custom-checkbox custom-control-inline my-2 mr-3">
                                        <input
                                            type="checkbox"
                                            class="custom-control-input permission-item-checkbox"
                                            id="permission_id[{{ $permission->id }}]"
                                            name="permission_id[]"
                                            value="{{ $permission->id }}"
                                            {{ $role->hasPermissionTo($permission->name) ? 'checked' : '' }}
                                        >
                                        <label
                                            for="permission_id[{{ $permission->id }}]"
                                            class="custom-control-label"
                                            title="{{ $permission->name }}"
                                        >
                                            {{ $permission->display_name ?? $permission->name }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <hr>
                        @endforeach

                        <!-- end: Input Data -->
                        <div class="mt-2">
                            <button type="submit" class="btn btn-primary mr-2">Guardar</button>
                            <a class="btn bg-danger" href="{{ route('rolePermission.index') }}">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Page end  -->
</div>

<script>
    (function () {
        var checkAll = document.getElementById('check-all');
        var groupBoxes = document.querySelectorAll('.permission-group-checkbox');
        var itemBoxes = document.querySelectorAll('.permission-item-checkbox');

        function syncCheckAll() {
            if (!checkAll) return;
            var allItems = Array.prototype.slice.call(itemBoxes);
            checkAll.checked = allItems.length > 0 && allItems.every(function (cb) { return cb.checked; });
        }

        function syncGroupBox(row) {
            if (!row) return;
            var groupCb = row.querySelector('.permission-group-checkbox');
            var rowItems = Array.prototype.slice.call(row.querySelectorAll('.permission-item-checkbox'));
            if (groupCb) {
                groupCb.checked = rowItems.length > 0 && rowItems.every(function (cb) { return cb.checked; });
            }
        }

        if (checkAll) {
            checkAll.addEventListener('change', function () {
                var checked = checkAll.checked;
                groupBoxes.forEach(function (cb) { cb.checked = checked; });
                itemBoxes.forEach(function (cb) { cb.checked = checked; });
            });
        }

        groupBoxes.forEach(function (groupCb) {
            groupCb.addEventListener('change', function () {
                var row = groupCb.closest('.permission-group-row');
                if (!row) return;
                row.querySelectorAll('.permission-item-checkbox').forEach(function (cb) {
                    cb.checked = groupCb.checked;
                });
                syncCheckAll();
            });
        });

        itemBoxes.forEach(function (itemCb) {
            itemCb.addEventListener('change', function () {
                syncGroupBox(itemCb.closest('.permission-group-row'));
                syncCheckAll();
            });
        });

        syncCheckAll();
    })();
</script>


@endsection
