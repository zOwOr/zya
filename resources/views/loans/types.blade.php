@extends('dashboard.body.main')

@section('container')
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
                        <i class="fa-solid fa-sliders text-primary mr-2"></i>Catálogo de Tipos de Préstamo (CRUD Completo)
                    </h4>
                    <p class="mb-0 text-muted font-size-13">
                        Administra los distintos tipos de préstamos, sus tasas, comisiones por apertura y los campos de información que se deben guardar en cada uno.
                    </p>
                </div>
                <div class="d-flex flex-wrap">
                    <a href="{{ route('loans.index') }}" class="btn btn-outline-secondary mr-2 mb-2">
                        <i class="fa-solid fa-arrow-left mr-1"></i>Volver a Préstamos
                    </a>
                    <button type="button" class="btn btn-primary mb-2" data-toggle="modal" data-target="#createLoanTypeModal">
                        <i class="fa-solid fa-plus mr-1"></i>Nuevo Tipo de Préstamo
                    </button>
                </div>
            </div>

            <!-- Barra de Búsqueda -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body py-3">
                    <form method="GET" action="{{ route('loans.types.index') }}" class="row align-items-center">
                        <div class="col-md-8 mb-2 mb-md-0">
                            <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm" placeholder="Buscar tipo de préstamo por nombre, código o descripción...">
                        </div>
                        <div class="col-md-4 d-flex">
                            <button type="submit" class="btn btn-primary btn-sm mr-2 flex-grow-1">
                                <i class="fa-solid fa-magnifying-glass mr-1"></i>Buscar
                            </button>
                            <a href="{{ route('loans.types.index') }}" class="btn btn-light btn-sm">
                                Limpiar
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabla CRUD de Tipos de Préstamo -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 datatable-export" data-can-export="{{ auth()->user()->can('prestamos.export') ? 'true' : 'false' }}">
                            <thead class="bg-light">
                                <tr>
                                    <th>Nombre / Descripción</th>
                                    <th>Tasa Interés Default</th>
                                    <th>Comisión Apertura Default</th>
                                    <th>Frecuencia y Plazo</th>
                                    <th>Garantía</th>
                                    <th>Información / Campos que Guarda</th>
                                    <th class="text-center">Préstamos</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($loanTypes as $lt)
                                    @php
                                        $fieldsArray = is_array($lt->required_fields) ? $lt->required_fields : [];
                                        $typeLabels = [
                                            'text' => 'Texto',
                                            'number' => 'Número',
                                            'date' => 'Fecha',
                                            'textarea' => 'Texto largo',
                                        ];
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="font-weight-bold text-dark">{{ $lt->name }}</div>
                                            <small class="text-muted d-block">{{ $lt->description ?: 'Sin descripción' }}</small>
                                            <code class="font-size-11">{{ $lt->slug }}</code>
                                        </td>
                                        <td>
                                            <span class="font-weight-bold text-primary">{{ number_format($lt->default_interest_rate, 2) }}%</span>
                                        </td>
                                        <td>
                                            <span class="font-weight-bold text-success">${{ number_format($lt->default_opening_fee, 2) }}</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-light border">{{ \App\Models\Loan::frequencyLabel($lt->default_frequency) }}</span>
                                            <small class="d-block text-muted">{{ $lt->default_periods }} pagos</small>
                                        </td>
                                        <td>
                                            @if ($lt->requires_collateral)
                                                <span class="badge badge-warning">Sí requiere</span>
                                            @else
                                                <span class="badge badge-light border">No</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if (count($fieldsArray) > 0)
                                                <div class="d-flex flex-wrap">
                                                    @foreach ($fieldsArray as $f)
                                                        <span class="badge badge-light border mr-1 mb-1">
                                                            <strong>{{ $f['label'] ?? $f['key'] }}</strong>
                                                            <small class="text-muted">({{ $typeLabels[$f['type'] ?? 'text'] ?? 'Texto' }}{{ !empty($f['required']) ? ' *' : '' }})</small>
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-muted font-size-12">Sin campos extra</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-info">{{ $lt->loans_count }}</span>
                                        </td>
                                        <td class="text-center">
                                            <form action="{{ route('loans.types.toggle', $lt->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-xs {{ $lt->is_active ? 'btn-success' : 'btn-secondary' }}" title="Clic para cambiar estado">
                                                    {{ $lt->is_active ? 'Activo' : 'Inactivo' }}
                                                </button>
                                            </form>
                                        </td>
                                        <td class="text-right">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-primary mr-1 btn-edit-loan-type"
                                                data-id="{{ $lt->id }}"
                                                data-update-url="{{ route('loans.types.update', $lt->id) }}"
                                                data-name="{{ $lt->name }}"
                                                data-description="{{ $lt->description }}"
                                                data-rate="{{ $lt->default_interest_rate }}"
                                                data-fee="{{ $lt->default_opening_fee }}"
                                                data-frequency="{{ $lt->default_frequency }}"
                                                data-periods="{{ $lt->default_periods }}"
                                                data-collateral="{{ $lt->requires_collateral ? '1' : '0' }}"
                                                data-active="{{ $lt->is_active ? '1' : '0' }}"
                                                data-fields='@json($fieldsArray)'
                                                title="Editar Tipo de Préstamo">
                                                <i class="fa-solid fa-pen-to-square"></i> Editar
                                            </button>

                                            <form action="{{ route('loans.types.destroy', $lt->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Seguro que deseas eliminar este tipo de préstamo del catálogo?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar Tipo de Préstamo">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-4 text-muted">No hay tipos de préstamo registrados.</td>
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

<!-- ==================== MODAL CREAR NUEVO TIPO DE PRÉSTAMO ==================== -->
<div class="modal fade" id="createLoanTypeModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title text-white"><i class="fa-solid fa-plus-circle mr-2"></i>Crear Nuevo Tipo de Préstamo</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('loans.types.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Nombre del Tipo de Préstamo <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="Ej. Préstamo Personal, Prendario, Celular..." required>
                        </div>
                        <div class="col-md-3 form-group">
                            <label class="font-weight-bold">Tasa de Interés (%) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="default_interest_rate" class="form-control" value="15.00" required>
                        </div>
                        <div class="col-md-3 form-group">
                            <label class="font-weight-bold">Comisión Apertura ($) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="default_opening_fee" class="form-control" value="100.00" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold">Frecuencia Default <span class="text-danger">*</span></label>
                            <select name="default_frequency" class="form-control" required>
                                <option value="12_26">12 y 26 (Días 12 y 26 de cada mes)</option>
                                <option value="quincenal">Quincenal (Días 15 y 30)</option>
                                <option value="semanal">Semanal</option>
                                <option value="mensual">Mensual</option>
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold">No. de Pagos Default <span class="text-danger">*</span></label>
                            <input type="number" min="1" name="default_periods" class="form-control" value="8" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold">Opciones</label>
                            <div class="custom-control custom-checkbox mt-1">
                                <input type="checkbox" class="custom-control-input" id="createCollateralCheck" name="requires_collateral" value="1">
                                <label class="custom-control-label font-size-13" for="createCollateralCheck">Requiere Garantía / Prenda</label>
                            </div>
                            <div class="custom-control custom-checkbox mt-1">
                                <input type="checkbox" class="custom-control-input" id="createActiveCheck" name="is_active" value="1" checked>
                                <label class="custom-control-label font-size-13" for="createActiveCheck">Activo en el catálogo</label>
                            </div>
                        </div>
                        <div class="col-md-12 form-group">
                            <label class="font-weight-bold">Descripción</label>
                            <input type="text" name="description" class="form-control" placeholder="Descripción breve de las condiciones o uso de este tipo de préstamo...">
                        </div>
                    </div>

                    <hr class="my-2">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <h6 class="font-weight-bold mb-0 text-primary">
                                <i class="fa-solid fa-list-check mr-1"></i>Campos de Información que Solicita este Tipo de Préstamo
                            </h6>
                            <small class="text-muted">Define qué datos específicos se deben capturar cuando se cree un préstamo de este tipo.</small>
                        </div>
                        <button type="button" class="btn btn-xs btn-outline-primary" id="btnAddCreateField">
                            <i class="fa-solid fa-plus mr-1"></i>Agregar Campo
                        </button>
                    </div>

                    <div id="createFieldsContainer"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save mr-1"></i>Guardar Tipo de Préstamo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================== MODAL EDITAR TIPO DE PRÉSTAMO ==================== -->
<div class="modal fade" id="editLoanTypeModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title text-white"><i class="fa-solid fa-pen-to-square mr-2"></i>Editar Tipo de Préstamo</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editLoanTypeForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Nombre del Tipo de Préstamo <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="editNameInput" class="form-control" required>
                        </div>
                        <div class="col-md-3 form-group">
                            <label class="font-weight-bold">Tasa de Interés (%) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="default_interest_rate" id="editRateInput" class="form-control" required>
                        </div>
                        <div class="col-md-3 form-group">
                            <label class="font-weight-bold">Comisión Apertura ($) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="default_opening_fee" id="editFeeInput" class="form-control" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold">Frecuencia Default <span class="text-danger">*</span></label>
                            <select name="default_frequency" id="editFreqInput" class="form-control" required>
                                <option value="12_26">12 y 26 (Días 12 y 26 de cada mes)</option>
                                <option value="quincenal">Quincenal (Días 15 y 30)</option>
                                <option value="semanal">Semanal</option>
                                <option value="mensual">Mensual</option>
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold">No. de Pagos Default <span class="text-danger">*</span></label>
                            <input type="number" min="1" name="default_periods" id="editPeriodsInput" class="form-control" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold">Opciones</label>
                            <div class="custom-control custom-checkbox mt-1">
                                <input type="checkbox" class="custom-control-input" id="editCollateralCheck" name="requires_collateral" value="1">
                                <label class="custom-control-label font-size-13" for="editCollateralCheck">Requiere Garantía / Prenda</label>
                            </div>
                            <div class="custom-control custom-checkbox mt-1">
                                <input type="checkbox" class="custom-control-input" id="editActiveCheck" name="is_active" value="1">
                                <label class="custom-control-label font-size-13" for="editActiveCheck">Activo en el catálogo</label>
                            </div>
                        </div>
                        <div class="col-md-12 form-group">
                            <label class="font-weight-bold">Descripción</label>
                            <input type="text" name="description" id="editDescInput" class="form-control">
                        </div>
                    </div>

                    <hr class="my-2">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <h6 class="font-weight-bold mb-0 text-primary">
                                <i class="fa-solid fa-list-check mr-1"></i>Campos de Información que Solicita este Tipo de Préstamo
                            </h6>
                            <small class="text-muted">Agrega, modifica o elimina los campos requeridos para este tipo de préstamo.</small>
                        </div>
                        <button type="button" class="btn btn-xs btn-outline-primary" id="btnAddEditField">
                            <i class="fa-solid fa-plus mr-1"></i>Agregar Campo
                        </button>
                    </div>

                    <div id="editFieldsContainer"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-dark"><i class="fa-solid fa-check mr-1"></i>Actualizar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    let fieldCounter = 1;

    function buildFieldRowHtml(index, field = {}) {
        const label = field.label || '';
        const type = field.type || 'text';
        const required = field.required ? 'checked' : '';
        const key = field.key || '';

        return `
            <div class="row align-items-center bg-light p-2 rounded border mb-2 field-builder-row">
                <input type="hidden" name="fields[${index}][key]" value="${key}">
                <div class="col-md-5 mb-1 mb-md-0">
                    <label class="font-size-11 text-muted mb-0">Nombre del Campo *</label>
                    <input type="text" name="fields[${index}][label]" class="form-control form-control-sm" value="${label.replace(/"/g, '&quot;')}" placeholder="Ej. IMEI / Serie, Aval, Valor Prenda..." required>
                </div>
                <div class="col-md-3 mb-1 mb-md-0">
                    <label class="font-size-11 text-muted mb-0">Tipo de Dato</label>
                    <select name="fields[${index}][type]" class="form-control form-control-sm">
                        <option value="text" ${type === 'text' ? 'selected' : ''}>Texto</option>
                        <option value="number" ${type === 'number' ? 'selected' : ''}>Número / Monto</option>
                        <option value="date" ${type === 'date' ? 'selected' : ''}>Fecha</option>
                        <option value="textarea" ${type === 'textarea' ? 'selected' : ''}>Texto Largo</option>
                    </select>
                </div>
                <div class="col-md-2 mb-1 mb-md-0 pt-3">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="fldReq_${index}" name="fields[${index}][required]" value="1" ${required}>
                        <label class="custom-control-label font-size-12" for="fldReq_${index}">Obligatorio</label>
                    </div>
                </div>
                <div class="col-md-2 text-right pt-3">
                    <button type="button" class="btn btn-xs btn-outline-danger btn-remove-field-row" title="Quitar campo">
                        <i class="fa-solid fa-xmark"></i> Quitar
                    </button>
                </div>
            </div>
        `;
    }

    const createContainer = document.getElementById('createFieldsContainer');
    const editContainer = document.getElementById('editFieldsContainer');

    // Inicializar modal de creación con 2 campos sugeridos en blanco
    createContainer.insertAdjacentHTML('beforeend', buildFieldRowHtml(fieldCounter++, { label: '', type: 'text', required: false }));

    document.getElementById('btnAddCreateField').addEventListener('click', function () {
        createContainer.insertAdjacentHTML('beforeend', buildFieldRowHtml(fieldCounter++, { label: '', type: 'text', required: false }));
    });

    document.getElementById('btnAddEditField').addEventListener('click', function () {
        editContainer.insertAdjacentHTML('beforeend', buildFieldRowHtml(fieldCounter++, { label: '', type: 'text', required: false }));
    });

    document.addEventListener('click', function (e) {
        const removeBtn = e.target.closest('.btn-remove-field-row');
        if (removeBtn) {
            removeBtn.closest('.field-builder-row').remove();
        }
    });

    // Abrir Modal de Edición con todos los datos precargados
    document.querySelectorAll('.btn-edit-loan-type').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('editLoanTypeForm').action = this.dataset.updateUrl;
            document.getElementById('editNameInput').value = this.dataset.name || '';
            document.getElementById('editDescInput').value = this.dataset.description || '';
            document.getElementById('editRateInput').value = this.dataset.rate || '0';
            document.getElementById('editFeeInput').value = this.dataset.fee || '0';
            document.getElementById('editFreqInput').value = this.dataset.frequency || 'semanal';
            document.getElementById('editPeriodsInput').value = this.dataset.periods || '4';
            document.getElementById('editCollateralCheck').checked = this.dataset.collateral === '1';
            document.getElementById('editActiveCheck').checked = this.dataset.active === '1';

            editContainer.innerHTML = '';
            let fields = [];
            try {
                fields = JSON.parse(this.dataset.fields || '[]');
            } catch (e) {
                fields = [];
            }

            if (fields.length === 0) {
                editContainer.insertAdjacentHTML('beforeend', buildFieldRowHtml(fieldCounter++, { label: '', type: 'text', required: false }));
            } else {
                fields.forEach(function (f) {
                    editContainer.insertAdjacentHTML('beforeend', buildFieldRowHtml(fieldCounter++, f));
                });
            }

            $('#editLoanTypeModal').modal('show');
        });
    });
});
</script>
@endsection

