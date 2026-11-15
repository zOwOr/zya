@extends('dashboard.body.main')

@section('container')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            @if (session()->has('success'))
                <div class="alert text-white bg-success alert-dismissible fade show" role="alert">
                    <div class="iq-alert-text">{{ session('success') }}</div>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><i class="ri-close-line"></i></button>
                </div>
            @endif

            @if (session()->has('error'))
                <div class="alert text-white bg-danger alert-dismissible fade show" role="alert">
                    <div class="iq-alert-text font-weight-bold">{{ session('error') }}</div>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><i class="ri-close-line"></i></button>
                </div>
            @endif

            @if (session()->has('import_errors') && is_array(session('import_errors')))
                <div class="card border-danger mb-4 shadow-sm">
                    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center py-2">
                        <h6 class="mb-0 text-white"><i class="fa-solid fa-triangle-exclamation mr-2"></i>Detalle de errores detectados ({{ count(session('import_errors')) }})</h6>
                    </div>
                    <div class="card-body py-3" style="max-height: 280px; overflow-y: auto;">
                        <ul class="mb-0 pl-3 text-danger">
                            @foreach (session('import_errors') as $err)
                                <li class="mb-1">{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @if ($categoriesCount === 0 || $suppliersCount === 0)
                <div class="alert alert-warning" role="alert">
                    <strong>Atención:</strong>
                    @if ($categoriesCount === 0) No hay categorías registradas. @endif
                    @if ($suppliersCount === 0) No hay proveedores registrados. @endif
                    Asegúrate de registrar al menos uno antes de importar productos.
                </div>
            @endif
        </div>

        <div class="col-lg-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center" style="gap: 10px;">
                    <div class="header-title">
                        <h4 class="card-title mb-0"><i class="fa-solid fa-file-excel text-success mr-2"></i>Importación Masiva de Productos</h4>
                    </div>
                    <div class="d-flex flex-wrap" style="gap: 8px;">
                        <a href="{{ route('products.downloadTemplate') }}" class="btn btn-success">
                            <i class="fa-solid fa-download mr-1"></i> Descargar Plantilla Excel (.xlsx)
                        </a>
                        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">
                            <i class="fa-solid fa-arrow-left mr-1"></i> Volver al Catálogo
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <form action="{{ route('products.importStore') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="form-group col-lg-5 col-md-12">
                                <label for="upload_file" class="font-weight-bold">Archivo Excel (.xlsx / .xls) <span class="text-danger">*</span></label>
                                <div class="custom-file">
                                    <input type="file"
                                           class="custom-file-input @error('upload_file') is-invalid @enderror"
                                           id="upload_file"
                                           name="upload_file"
                                           accept=".xlsx,.xls"
                                           onchange="showFileName();"
                                           required>
                                    <label class="custom-file-label" for="upload_file" id="label_input">Elegir Archivo (.xls / .xlsx)</label>
                                </div>
                                @error('upload_file')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group col-lg-4 col-md-6">
                                <label for="default_branch_id" class="font-weight-bold">Sucursal Predeterminada</label>
                                @if ($canAllBranches)
                                    <select name="default_branch_id" id="default_branch_id" class="form-control @error('default_branch_id') is-invalid @enderror">
                                        <option value="">-- Usar columna SUCURSAL del Excel --</option>
                                        @foreach ($branches as $branch)
                                            <option value="{{ $branch->id }}" {{ old('default_branch_id') == $branch->id ? 'selected' : '' }}>
                                                {{ $branch->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Se aplica solo a filas donde la columna <code>SUCURSAL</code> esté vacía.</small>
                                @else
                                    <input type="text" class="form-control bg-light" value="{{ $userBranch?->name ?? 'Sin sucursal asignada' }}" readonly>
                                    @if ($userBranch)
                                        <input type="hidden" name="default_branch_id" value="{{ $userBranch->id }}">
                                    @endif
                                    <small class="text-muted">Solo puedes importar productos a tu sucursal asignada.</small>
                                @endif
                                @error('default_branch_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group col-lg-3 col-md-6">
                                <label for="duplicate_mode" class="font-weight-bold">Si el Código / SKU ya existe</label>
                                <select name="duplicate_mode" id="duplicate_mode" class="form-control">
                                    <option value="reject" {{ old('duplicate_mode', 'reject') === 'reject' ? 'selected' : '' }}>Rechazar duplicados (Recomendado)</option>
                                    <option value="update" {{ old('duplicate_mode') === 'update' ? 'selected' : '' }}>Actualizar datos y stock existentes</option>
                                </select>
                                <small class="text-muted">Evaluado por código dentro de cada sucursal.</small>
                            </div>
                        </div>

                        <div class="alert alert-info py-2 mb-3">
                            <i class="fa-solid fa-shield-halved mr-1"></i>
                            <strong>Importación atómica:</strong> Si cualquier fila tiene datos inválidos, categorías/proveedores inexistentes o sucursal no autorizada, se cancelará todo el lote sin dejar datos a medias.
                        </div>

                        <div class="mt-2">
                            <button type="submit" class="btn btn-primary mr-2">
                                <i class="fa-solid fa-file-import mr-1"></i> Procesar Importación
                            </button>
                            <a class="btn btn-danger" href="{{ route('products.index') }}">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-0"><i class="fa-solid fa-table-columns text-primary mr-2"></i>Estructura Esperada de Columnas</h5>
                        <small class="text-muted">La plantilla Excel descargable ya incluye esta estructura, una fila de ejemplo editable y la pestaña <strong>"Guía y Catálogos"</strong> con tus sucursales, categorías y proveedores reales.</small>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover mb-0">
                            <thead class="thead-dark">
                                <tr>
                                    <th style="width: 55px;" class="text-center">#</th>
                                    <th>Encabezado Esperado en Excel</th>
                                    <th style="width: 120px;" class="text-center">Requerido</th>
                                    <th style="width: 135px;">Tipo de Dato</th>
                                    <th>Descripción</th>
                                    <th>Ejemplo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($columns as $idx => $col)
                                    <tr>
                                        <td class="text-center font-weight-bold">{{ $idx + 1 }}</td>
                                        <td><code>{{ $col['header'] }}</code></td>
                                        <td class="text-center">
                                            @if ($col['required'])
                                                <span class="badge badge-danger">Obligatorio</span>
                                            @else
                                                <span class="badge badge-secondary">Opcional</span>
                                            @endif
                                        </td>
                                        <td>{{ $col['type'] }}</td>
                                        <td>{{ $col['description'] }}</td>
                                        <td class="text-muted">{{ $col['example'] !== '' ? $col['example'] : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showFileName() {
    const input = document.getElementById('upload_file');
    const labelInput = document.getElementById('label_input');
    if (input.files && input.files.length > 0) {
        labelInput.textContent = input.files[0].name;
    }
}
</script>
@endsection
