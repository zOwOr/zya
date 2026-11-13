@extends('dashboard.body.main')

@section('container')
@php
    $canManageCatalog = auth()->user()->isSuperAdmin() || auth()->user()->can('cash.edit');
@endphp
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            @if (session('success'))
                <div class="alert text-white bg-success alert-dismissible fade show" role="alert">
                    <div class="iq-alert-text"><i class="fa-solid fa-circle-check mr-2"></i>{{ session('success') }}</div>
                    <button type="button" class="close text-white" data-dismiss="alert" aria-label="Close">
                        <i class="ri-close-line"></i>
                    </button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert text-white bg-danger alert-dismissible fade show" role="alert">
                    <div class="iq-alert-text"><i class="fa-solid fa-triangle-exclamation mr-2"></i>{{ session('error') }}</div>
                    <button type="button" class="close text-white" data-dismiss="alert" aria-label="Close">
                        <i class="ri-close-line"></i>
                    </button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="mb-1 font-weight-bold">
                        <i class="fa-solid fa-tags text-danger mr-2"></i>Catálogo de Tipos de Egreso / Salidas de Caja
                    </h4>
                    <p class="mb-0 text-muted font-size-13">
                        Administra los tipos de salida disponibles al capturar egresos manuales en Caja. Cada sucursal muestra arriba sus <strong>Más usados</strong> y deja <strong>Otro</strong> siempre al final.
                    </p>
                </div>
                <div class="d-flex flex-wrap">
                    <a href="{{ route('cash.index') }}" class="btn btn-outline-secondary mr-2 mb-2">
                        <i class="fa-solid fa-arrow-left mr-1"></i>Volver a Corte y Caja
                    </a>
                    <a href="{{ route('cash.expenses') }}" class="btn btn-outline-danger mr-2 mb-2">
                        <i class="fa-solid fa-file-invoice-dollar mr-1"></i>Reporte de Gastos
                    </a>
                    @if ($canManageCatalog)
                        <button type="button" class="btn btn-primary mb-2" data-toggle="modal" data-target="#createExpenseTypeModal">
                            <i class="fa-solid fa-plus mr-1"></i>Nuevo Tipo de Egreso
                        </button>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body py-3">
                    <form method="GET" action="{{ route('cash.expenseTypes.index') }}" class="row align-items-center">
                        <div class="col-md-8 mb-2 mb-md-0">
                            <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm" placeholder="Buscar por nombre o ejemplos (ej. Limpieza, luz, gasolina)...">
                        </div>
                        <div class="col-md-4 d-flex">
                            <button type="submit" class="btn btn-primary btn-sm mr-2 flex-grow-1">
                                <i class="fa-solid fa-magnifying-glass mr-1"></i>Buscar
                            </button>
                            <a href="{{ route('cash.expenseTypes.index') }}" class="btn btn-light btn-sm">Limpiar</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th style="width: 70px;" class="text-center">Orden</th>
                                    <th>Tipo de Egreso / Ejemplos</th>
                                    <th class="text-center">Motivo</th>
                                    <th class="text-center">Registros</th>
                                    <th class="text-center">Estado</th>
                                    @if ($canManageCatalog)
                                        <th class="text-right">Acciones</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($expenseTypes as $et)
                                    <tr>
                                        <td class="text-center align-middle">
                                            @if ($et->is_other)
                                                <span class="badge badge-dark">Al final</span>
                                            @else
                                                <span class="badge badge-light border">{{ $et->sort_order }}</span>
                                            @endif
                                        </td>
                                        <td class="align-middle">
                                            <div class="font-weight-bold text-dark">{{ $et->name }}</div>
                                            @if ($et->examples)
                                                <small class="text-muted d-block">({{ $et->examples }})</small>
                                            @endif
                                            <code class="font-size-11">{{ $et->slug }}</code>
                                        </td>
                                        <td class="text-center align-middle">
                                            @if ($et->is_other || $et->requires_reason)
                                                <span class="badge badge-danger">Obligatorio</span>
                                            @else
                                                <span class="badge badge-light border text-muted">Opcional</span>
                                            @endif
                                        </td>
                                        <td class="text-center align-middle">
                                            <span class="badge badge-info">{{ $et->cash_flows_count }}</span>
                                        </td>
                                        <td class="text-center align-middle">
                                            @if ($canManageCatalog && !$et->is_other)
                                                <form action="{{ route('cash.expenseTypes.toggle', $et->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-xs {{ $et->is_active ? 'btn-success' : 'btn-secondary' }}" title="Clic para activar/desactivar">
                                                        {{ $et->is_active ? 'Activo' : 'Inactivo' }}
                                                    </button>
                                                </form>
                                            @else
                                                <span class="badge {{ $et->is_active ? 'badge-success' : 'badge-secondary' }}">
                                                    {{ $et->is_active ? 'Activo' : 'Inactivo' }}
                                                </span>
                                            @endif
                                        </td>
                                        @if ($canManageCatalog)
                                            <td class="text-right align-middle text-nowrap">
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-primary mr-1 btn-edit-expense-type"
                                                    data-update-url="{{ route('cash.expenseTypes.update', $et->id) }}"
                                                    data-name="{{ $et->name }}"
                                                    data-examples="{{ $et->examples }}"
                                                    data-sort-order="{{ $et->sort_order }}"
                                                    data-requires-reason="{{ $et->requires_reason ? '1' : '0' }}"
                                                    data-is-other="{{ $et->is_other ? '1' : '0' }}"
                                                    data-is-active="{{ $et->is_active ? '1' : '0' }}">
                                                    <i class="fa-solid fa-pen-to-square"></i> Editar
                                                </button>
                                                @if (!$et->is_other)
                                                    <form action="{{ route('cash.expenseTypes.destroy', $et->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar el tipo de egreso &quot;{{ addslashes($et->name) }}&quot;? Los registros históricos en Corte Z conservarán su nombre.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $canManageCatalog ? 6 : 5 }}" class="text-center text-muted py-4">
                                            No se encontraron tipos de egreso con ese criterio.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if ($canManageCatalog)
    <!-- Modal Crear Tipo de Egreso -->
    <div class="modal fade" id="createExpenseTypeModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST" action="{{ route('cash.expenseTypes.store') }}">
                    @csrf
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title text-white"><i class="fa-solid fa-plus-circle mr-1"></i> Nuevo Tipo de Egreso</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="font-weight-bold small">Nombre del Tipo *</label>
                            <input type="text" name="name" class="form-control" required maxlength="160" placeholder="Ej. Comisiones bancarias">
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold small">Ejemplos / Referencia rápida</label>
                            <input type="text" name="examples" class="form-control" maxlength="255" placeholder="Ej. terminal, SPEI, anualidad">
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label class="font-weight-bold small">Orden en lista</label>
                                <input type="number" name="sort_order" class="form-control" min="1" max="9998" placeholder="Automático">
                            </div>
                            <div class="form-group col-md-6 d-flex flex-column justify-content-end">
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" class="custom-control-input" id="createRequiresReason" name="requires_reason" value="1">
                                    <label class="custom-control-label small font-weight-bold" for="createRequiresReason">Motivo obligatorio</label>
                                </div>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="createIsActive" name="is_active" value="1" checked>
                                    <label class="custom-control-label small font-weight-bold" for="createIsActive">Activo en Caja</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary font-weight-bold">Guardar Tipo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Tipo de Egreso -->
    <div class="modal fade" id="editExpenseTypeModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST" id="editExpenseTypeForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-header bg-dark text-white">
                        <h5 class="modal-title text-white"><i class="fa-solid fa-pen mr-1"></i> Editar Tipo de Egreso</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="font-weight-bold small">Nombre del Tipo *</label>
                            <input type="text" name="name" id="editExpName" class="form-control" required maxlength="160">
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold small">Ejemplos / Referencia rápida</label>
                            <input type="text" name="examples" id="editExpExamples" class="form-control" maxlength="255">
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6" id="editExpOrderCol">
                                <label class="font-weight-bold small">Orden en lista</label>
                                <input type="number" name="sort_order" id="editExpOrder" class="form-control" min="1" max="9998">
                            </div>
                            <div class="form-group col-md-6 d-flex flex-column justify-content-end">
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" class="custom-control-input" id="editRequiresReason" name="requires_reason" value="1">
                                    <label class="custom-control-label small font-weight-bold" for="editRequiresReason">Motivo obligatorio</label>
                                </div>
                                <div class="custom-control custom-checkbox" id="editActiveGroup">
                                    <input type="checkbox" class="custom-control-input" id="editIsActive" name="is_active" value="1">
                                    <label class="custom-control-label small font-weight-bold" for="editIsActive">Activo en Caja</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary font-weight-bold">Actualizar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.btn-edit-expense-type').forEach(btn => {
            btn.addEventListener('click', function () {
                const isOther = this.dataset.isOther === '1';
                document.getElementById('editExpenseTypeForm').action = this.dataset.updateUrl;
                document.getElementById('editExpName').value = this.dataset.name || '';
                document.getElementById('editExpExamples').value = this.dataset.examples || '';
                document.getElementById('editExpOrder').value = isOther ? 9999 : (this.dataset.sortOrder || 100);
                document.getElementById('editRequiresReason').checked = isOther || this.dataset.requiresReason === '1';
                document.getElementById('editRequiresReason').disabled = isOther;
                document.getElementById('editIsActive').checked = isOther || this.dataset.isActive === '1';
                document.getElementById('editExpOrderCol').style.display = isOther ? 'none' : '';
                document.getElementById('editActiveGroup').style.display = isOther ? 'none' : '';
                $('#editExpenseTypeModal').modal('show');
            });
        });
    });
    </script>
@endif
@endsection

