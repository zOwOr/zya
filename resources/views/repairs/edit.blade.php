@extends('dashboard.body.main')

@section('container')
    <div class="container">

        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>¡Ups!</strong> Hay algunos problemas con los datos ingresados:<br><br>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form action="{{ route('repairs.update', $repair->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <h2>Detalle de Reparación</h2>

            <div class="row g-3 mb-3">
                <div class="mb-6 col-md-6">
                    <label for="inputBusqueda" class="form-label">Buscar cliente <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('cliente') is-invalid @enderror" list="clientesList"
                        name="cliente" id="clientSearch" placeholder="Escribe nombre del cliente..." autocomplete="off"
                        required value="{{ $repair->cliente }}">

                    <datalist id="clientesList">
                        @foreach ($clientes as $cliente)
                            <option data-client-id="{{ $cliente->id }}" data-client-phone="{{ $cliente->tit_phone }}"
                                value="{{ $cliente->tit_name }}">
                        @endforeach
                    </datalist>

                    <!-- This hidden field MUST be named 'cliente' -->
                    <input type="hidden" id="selectedClientId" value="{{ old('cliente') }}">

                    @error('cliente')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label>Teléfono</label>
                    <input type="text" name="telefono" id="clientPhone" class="form-control"
                        value="{{ $repair->telefono }}" required>
                </div>
            </div>







    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label">IMEI</label>
            <input type="text" name="imei" class="form-control" value="{{ $repair->imei }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Estado</label>
            <select name="estado" class="form-control" required>
                <option value="pendiente" {{ $repair->estado == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                <option value="en reparación" {{ $repair->estado == 'en reparación' ? 'selected' : '' }}>En
                    reparación</option>
                <option value="reparado" {{ $repair->estado == 'reparado' ? 'selected' : '' }}>Reparado</option>
                <option value="entregado" {{ $repair->estado == 'entregado' ? 'selected' : '' }}>Entregado</option>
            </select>
        </div>

    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label">Marca</label>
            <input type="text" name="marca" class="form-control" value="{{ $repair->marca }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">Modelo</label>
            <input type="text" name="modelo" class="form-control" value="{{ $repair->modelo }}">
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Problema Reportado</label>
        <textarea class="form-control" name="problema_reportado">{{ $repair->problema_reportado }}</textarea>
    </div>

    <div class="mb-3">
        <label class="form-label">Diagnóstico</label>
        <textarea class="form-control" name="diagnostico">{{ $repair->diagnostico }}</textarea>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <label class="form-label">Precio</label>
            <input type="number" name="precio" class="form-control" value="{{ $repair->precio }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Fecha de Ingreso</label>
            <input type="date" name="fecha_ingreso" class="form-control" value="{{ $repair->fecha_ingreso }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Fecha de Ingreso</label>
            <input type="date" name="fecha_entrega" class="form-control" value="{{ $repair->fecha_entrega }}">
        </div>

    </div>

    <hr>
    <h5>Evidencia Fotográfica - Al Recibir</h5>
    <div class="row g-3 mb-3">
        <div class="col-md-6 text-center">

            <label class="form-label fw-bold">Foto Frontal</label>

            <div class="image-container text-center " style="border-style: dashed; min-height: 200px;">

                <!-- Input de archivo -->
                <input type="file" name="foto_recibido_frontal" class="form-control visually-hidden"
                    onchange="previewImage(this)">

                <!-- Área interactiva -->
                <label class="w-100 h-100 d-flex flex-column justify-content-center align-items-center">

                    <!-- Preview de imagen -->
                    <div class="profile-img-edit">
                        <div class="crm-profile-img-edit">
                            <img class="crm-profile-pic  avatar-155 image-preview" id="foto_recibido_frontal"
                                src="{{ asset('storage/' . $repair->foto_recibido_frontal) }}" alt="profile-pic">
                        </div>
                    </div>

                </label>
            </div>
        </div>
        <div class="col-md-6 text-center">
            <label class="form-label fw-bold">Foto Trasera</label>

            <div class="image-container text-center " style="border-style: dashed; min-height: 200px;">

                <!-- Input de archivo -->
                <input type="file" name="foto_recibido_trasera" class="form-control visually-hidden"
                    onchange="previewImage(this)">

                <!-- Área interactiva -->
                <label class="w-100 h-100 d-flex flex-column justify-content-center align-items-center">

                    <!-- Preview de imagen -->
                    <div class="profile-img-edit">
                        <div class="crm-profile-img-edit">
                            <img class="crm-profile-pic  avatar-155 image-preview" id="foto_recibido_trasera"
                                src="{{ asset('storage/' . $repair->foto_recibido_trasera) }}" alt="profile-pic">
                        </div>
                    </div>

                </label>
            </div>
        </div>
    </div>
    <hr>
    <br>
    <br>
    <br>

    <hr>
    <h5>Evidencia Fotográfica - Al Entregar</h5>
    <div class="row g-3 mb-3">
        <div class="col-md-6 text-center">
            <label class="form-label fw-bold">Foto Frontal</label>

            <div class="image-container text-center " style="border-style: dashed; min-height: 200px;">

                <!-- Input de archivo -->
                <input type="file" name="foto_entregado_frontal" class="form-control visually-hidden"
                    onchange="previewImage(this)">

                <!-- Área interactiva -->
                <label class="w-100 h-100 d-flex flex-column justify-content-center align-items-center">

                    <!-- Preview de imagen -->
                    <div class="profile-img-edit">
                        <div class="crm-profile-img-edit">
                            <img class="crm-profile-pic  avatar-155 image-preview" id="foto_entregado_frontal"
                                src="{{ asset('storage/' . $repair->foto_entregado_frontal) }}" alt="profile-pic">
                        </div>
                    </div>

                </label>
            </div>

        </div>
        <div class="col-md-6 text-center">
            <label class="form-label fw-bold">Foto Trasera</label>

            <div class="image-container text-center " style="border-style: dashed; min-height: 200px;">

                <!-- Input de archivo -->
                <input type="file" name="foto_entregado_trasera" class="form-control visually-hidden"
                    onchange="previewImage(this)">

                <!-- Área interactiva -->
                <label class="w-100 h-100 d-flex flex-column justify-content-center align-items-center">

                    <!-- Preview de imagen -->
                    <div class="profile-img-edit">
                        <div class="crm-profile-img-edit">
                            <img class="crm-profile-pic  avatar-155 image-preview" id="foto_entregado_trasera"
                                src="{{ asset('storage/' . $repair->foto_entregado_trasera) }}" alt="profile-pic">
                        </div>
                    </div>

                </label>
            </div>
        </div>
    </div>

    <hr>
    <br>
    <br>
    <br>

    <a href="{{ route('repairs.index') }}" class="btn btn-secondary mt-3">Volver</a>
    <button type="submit" class="btn btn-primary mt-3">Guardar cambios</button>

    </form>

    </div>
@endsection
@include('components.preview-img-form')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('clientSearch');
        const clientIdInput = document.getElementById('selectedClientId');
        const phoneInput = document.getElementById('clientPhone');
        const datalist = document.getElementById('clientesList');

        searchInput.addEventListener('input', function() {
            const inputValue = this.value.trim();
            const options = Array.from(datalist.options);

            const foundOption = options.find(option => option.value === inputValue);

            if (foundOption) {
                clientIdInput.value = foundOption.dataset.clientId;
                phoneInput.value = foundOption.dataset.clientPhone || '';
                searchInput.classList.remove('is-invalid');
            } else {
                clientIdInput.value = '';
                phoneInput.value = '';
            }
        });

        document.querySelector('form').addEventListener('submit', function(e) {
            if (!clientIdInput.value) {
                e.preventDefault();
                alert('Debe seleccionar un cliente válido de la lista');
                searchInput.focus();
                searchInput.classList.add('is-invalid');
            }
        });
    });
</script>
