@extends('dashboard.body.main')

@section('container')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            @if (session('success'))
                <div class="alert text-white bg-success alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-check mr-2"></i>{{ session('success') }}
                    <button type="button" class="close text-white" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert text-white bg-danger alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-triangle-exclamation mr-2"></i>{{ session('error') }}
                    <button type="button" class="close text-white" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="mb-1 font-weight-bold">
                        <i class="fa-solid fa-layer-group text-primary mr-2"></i>Catálogos del Módulo de Financieras
                    </h4>
                    <p class="text-muted mb-0 font-size-13">Administre Financieras, Marcas propias y Etapas de Garantía.</p>
                </div>
                <div>
                    <a href="{{ route('financieras.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fa-solid fa-arrow-left mr-1"></i>Regresar al Módulo
                    </a>
                </div>
            </div>

            <!-- Tabs de Catálogos -->
            <ul class="nav nav-pills mb-4" id="pills-tab" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active font-weight-bold px-4" id="tab-cat-financieras" data-toggle="pill" href="#cat-financieras" role="tab">
                        <i class="fa-solid fa-building-columns mr-2"></i>Financieras ({{ $financieras->count() }})
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link font-weight-bold px-4" id="tab-cat-marcas" data-toggle="pill" href="#cat-marcas" role="tab">
                        <i class="fa-solid fa-tags mr-2"></i>Catálogo de Marcas ({{ $brands->count() }})
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link font-weight-bold px-4" id="tab-cat-etapas" data-toggle="pill" href="#cat-etapas" role="tab">
                        <i class="fa-solid fa-list-check mr-2"></i>Etapas de Garantía ({{ $stages->count() }})
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link font-weight-bold px-4" id="tab-cat-proveedores" data-toggle="pill" href="#cat-proveedores" role="tab">
                        <i class="fa-solid fa-truck-field mr-2"></i>Proveedores ({{ $suppliers->count() }})
                    </a>
                </li>
            </ul>

            <div class="tab-content" id="pills-tabContent">
                <!-- 1. FINANCIERAS -->
                <div class="tab-pane fade show active" id="cat-financieras" role="tabpanel">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                            <h6 class="font-weight-bold mb-0 text-primary">Listado de Financieras Registradas</h6>
                            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalCreateFinanciera">
                                <i class="fa-solid fa-plus mr-1"></i>Nueva Financiera
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Contacto</th>
                                            <th>Teléfono</th>
                                            <th>Email</th>
                                            <th>Ventas Totales</th>
                                            <th>Estado</th>
                                            <th class="text-right">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($financieras as $fin)
                                            <tr>
                                                <td class="font-weight-bold">{{ $fin->name }}</td>
                                                <td>{{ $fin->contact_name ?: '-' }}</td>
                                                <td>{{ $fin->contact_phone ?: '-' }}</td>
                                                <td>{{ $fin->contact_email ?: '-' }}</td>
                                                <td><span class="badge badge-light border">{{ $fin->sales_count }}</span></td>
                                                <td>
                                                    <span class="badge badge-{{ $fin->is_active ? 'success' : 'secondary' }}">
                                                        {{ $fin->is_active ? 'Activa' : 'Inactiva' }}
                                                    </span>
                                                </td>
                                                <td class="text-right">
                                                    @if ($fin->sales_count == 0)
                                                        <form action="{{ route('financieras.catalogos.financieras.destroy', $fin->id) }}" method="POST" class="d-inline"
                                                            onsubmit="return confirm('¿Eliminar financiera?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="btn btn-xs btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                                        </form>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. MARCAS -->
                <div class="tab-pane fade" id="cat-marcas" role="tabpanel">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="font-weight-bold mb-0 text-primary">Catálogo Propio de Marcas</h6>
                                <small class="text-muted">Las marcas registradas aquí alimentan el autocomplete en todas las secciones.</small>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalCreateBrand">
                                <i class="fa-solid fa-plus mr-1"></i>Nueva Marca
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Nombre de Marca</th>
                                            <th>Equipos en Inventario</th>
                                            <th>Estado</th>
                                            <th class="text-right">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($brands as $br)
                                            <tr>
                                                <td class="font-weight-bold">{{ $br->name }}</td>
                                                <td><span class="badge badge-light border">{{ $br->devices_count }} equipos</span></td>
                                                <td>
                                                    <span class="badge badge-{{ $br->is_active ? 'success' : 'secondary' }}">
                                                        {{ $br->is_active ? 'Activa' : 'Inactiva' }}
                                                    </span>
                                                </td>
                                                <td class="text-right">
                                                    @if ($br->devices_count == 0)
                                                        <form action="{{ route('financieras.catalogos.brands.destroy', $br->id) }}" method="POST" class="d-inline"
                                                            onsubmit="return confirm('¿Eliminar marca?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="btn btn-xs btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                                        </form>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. ETAPAS DE GARANTÍA -->
                <div class="tab-pane fade" id="cat-etapas" role="tabpanel">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="font-weight-bold mb-0 text-primary">Catálogo de Etapas de Garantía</h6>
                                <small class="text-muted">Define el flujo de estados técnicos que recorre una garantía.</small>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalCreateStage">
                                <i class="fa-solid fa-plus mr-1"></i>Nueva Etapa
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Orden</th>
                                            <th>Nombre de Etapa</th>
                                            <th>Color Distintivo</th>
                                            <th>¿Es Etapa Final?</th>
                                            <th>Garantías en esta etapa</th>
                                            <th class="text-right">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($stages as $st)
                                            <tr>
                                                <td class="font-weight-bold">{{ $st->order }}</td>
                                                <td class="font-weight-bold">{{ $st->name }}</td>
                                                <td>
                                                    <span class="badge text-white px-3 py-1" style="background-color: {{ $st->color }};">
                                                        {{ $st->color }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if ($st->is_final)
                                                        <span class="badge badge-success">Sí (Cierra caso)</span>
                                                    @else
                                                        <span class="badge badge-light border">No</span>
                                                    @endif
                                                </td>
                                                <td><span class="badge badge-light border">{{ $st->warranties_count }}</span></td>
                                                <td class="text-right">
                                                    @if ($st->warranties_count == 0)
                                                        <form action="{{ route('financieras.catalogos.warranty-stages.destroy', $st->id) }}" method="POST" class="d-inline"
                                                            onsubmit="return confirm('¿Eliminar etapa?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="btn btn-xs btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                                        </form>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. PROVEEDORES -->
                <div class="tab-pane fade" id="cat-proveedores" role="tabpanel">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="font-weight-bold mb-0 text-primary">Catálogo de Proveedores</h6>
                                <small class="text-muted">Proveedores de equipos celulares para inventario de financieras.</small>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalCreateSupplier">
                                <i class="fa-solid fa-plus mr-1"></i>Nuevo Proveedor
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Nombre del Proveedor</th>
                                            <th>Contacto</th>
                                            <th>Teléfono</th>
                                            <th>Email</th>
                                            <th>Equipos en Inventario</th>
                                            <th>Estado</th>
                                            <th class="text-right">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($suppliers as $sup)
                                            <tr>
                                                <td class="font-weight-bold">{{ $sup->name }}</td>
                                                <td>{{ $sup->contact ?: '-' }}</td>
                                                <td>{{ $sup->phone ?: '-' }}</td>
                                                <td>{{ $sup->email ?: '-' }}</td>
                                                <td><span class="badge badge-light border">{{ $sup->devices_count }} equipos</span></td>
                                                <td>
                                                    <span class="badge badge-{{ $sup->is_active ? 'success' : 'secondary' }}">
                                                        {{ $sup->is_active ? 'Activo' : 'Inactivo' }}
                                                    </span>
                                                </td>
                                                <td class="text-right">
                                                    <button type="button" class="btn btn-xs btn-outline-primary mr-1"
                                                        data-toggle="modal"
                                                        data-target="#modalEditSupplier{{ $sup->id }}"
                                                        title="Editar Proveedor">
                                                        <i class="fa-solid fa-pen"></i>
                                                    </button>
                                                    @if ($sup->devices_count == 0)
                                                        <form action="{{ route('financieras.catalogos.suppliers.destroy', $sup->id) }}" method="POST" class="d-inline"
                                                            onsubmit="return confirm('¿Eliminar proveedor {{ $sup->name }}?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="btn btn-xs btn-outline-danger" title="Eliminar"><i class="fa-solid fa-trash"></i></button>
                                                        </form>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center py-4 text-muted">No hay proveedores registrados aún.</td>
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
    </div>
</div>

<!-- Modal Create Financiera -->
<div class="modal fade" id="modalCreateFinanciera" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form action="{{ route('financieras.catalogos.financieras.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white">Nueva Financiera</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label class="font-weight-bold">Nombre de la Financiera <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="Ej. PayJoy, Macropay">
                    </div>
                    <div class="form-group mb-3">
                        <label class="font-weight-bold">Nombre del Contacto / Enlace</label>
                        <input type="text" name="contact_name" class="form-control" placeholder="Ej. Lic. Juan Pérez">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold">Teléfono</label>
                            <input type="text" name="contact_phone" class="form-control" placeholder="Teléfono de mesa">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold">Email</label>
                            <input type="email" name="contact_email" class="form-control" placeholder="soporte@financiera.com">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Financiera</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Create Brand -->
<div class="modal fade" id="modalCreateBrand" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form action="{{ route('financieras.catalogos.brands.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white">Nueva Marca</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-0">
                        <label class="font-weight-bold">Nombre de la Marca <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="Ej. Honor, Motorola, Xiaomi">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Marca</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Create Stage -->
<div class="modal fade" id="modalCreateStage" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form action="{{ route('financieras.catalogos.warranty-stages.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white">Nueva Etapa de Garantía</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label class="font-weight-bold">Nombre de la Etapa <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="Ej. Esperando refacción">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold">Orden Numérico</label>
                            <input type="number" name="order" class="form-control" value="0" min="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold">Color Distintivo</label>
                            <input type="color" name="color" class="form-control" value="#17a2b8" style="height: 38px;">
                        </div>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_final" class="form-check-input" id="checkIsFinal" value="1">
                        <label class="form-check-label font-weight-bold" for="checkIsFinal">¿Esta etapa concluye la garantía? (Etapa Final)</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Etapa</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Create Supplier -->
<div class="modal fade" id="modalCreateSupplier" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form action="{{ route('financieras.catalogos.suppliers.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white"><i class="fa-solid fa-truck-field mr-2"></i>Nuevo Proveedor</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label class="font-weight-bold">Nombre de la Empresa / Proveedor <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="Ej. Telcel Mayorista, Distribuidora Móvil MX">
                    </div>
                    <div class="form-group mb-3">
                        <label class="font-weight-bold">Nombre del Contacto / Agente</label>
                        <input type="text" name="contact" class="form-control" placeholder="Ej. Carlos Mendoza">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold">Teléfono</label>
                            <input type="text" name="phone" class="form-control" placeholder="Ej. 5512345678">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="ventas@proveedor.com">
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label class="font-weight-bold">Notas adicionales</label>
                        <textarea name="notes" rows="2" class="form-control" placeholder="Condiciones de entrega, crédito, etc."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Proveedor</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modales Edit Supplier -->
@foreach ($suppliers as $sup)
<div class="modal fade" id="modalEditSupplier{{ $sup->id }}" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form action="{{ route('financieras.catalogos.suppliers.update', $sup->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white"><i class="fa-solid fa-pen mr-2"></i>Editar Proveedor: {{ $sup->name }}</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label class="font-weight-bold">Nombre de la Empresa / Proveedor <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ $sup->name }}" required>
                    </div>
                    <div class="form-group mb-3">
                        <label class="font-weight-bold">Nombre del Contacto / Agente</label>
                        <input type="text" name="contact" class="form-control" value="{{ $sup->contact }}">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold">Teléfono</label>
                            <input type="text" name="phone" class="form-control" value="{{ $sup->phone }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ $sup->email }}">
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label class="font-weight-bold">Notas adicionales</label>
                        <textarea name="notes" rows="2" class="form-control">{{ $sup->notes }}</textarea>
                    </div>
                    <div class="form-group mb-0">
                        <label class="font-weight-bold">Estado</label>
                        <select name="is_active" class="form-control">
                            <option value="1" {{ $sup->is_active ? 'selected' : '' }}>Activo</option>
                            <option value="0" {{ !$sup->is_active ? 'selected' : '' }}>Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Actualizar Proveedor</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endforeach

@endsection
