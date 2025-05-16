@extends('dashboard.body.main')

@section('container')
    <div class="container">
        <h2>Registrar Reparación</h2>

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

        <form action="{{ route('repairs.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="row g-3 mb-3">


                <div class="mb-6 col-md-6">
                    <label for="inputBusqueda" class="form-label">Buscar cliente <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('cliente') is-invalid @enderror" list="clientesList"
                        name="cliente" id="clientSearch" placeholder="Escribe nombre del cliente..." autocomplete="off"
                        required>

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
                    <input type="text" name="telefono" id="clientPhone" class="form-control" required>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label>IMEI</label>
                    <input type="text" name="imei" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label>Estado</label>
                    <select name="estado" class="form-control" required>
                        <option value="pendiente">Pendiente</option>
                        <option value="en reparación">En reparación</option>
                        <option value="reparado">Reparado</option>
                        <option value="entregado">Entregado</option>
                    </select>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label>Marca</label>
                    <input type="text" name="marca" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label>Modelo</label>
                    <input type="text" name="modelo" class="form-control" required>
                </div>
            </div>

            <div class="mb-3">
                <label>Problema Reportado</label>
                <textarea name="problema_reportado" class="form-control" required></textarea>
            </div>

            <div class="mb-3">
                <label>Diagnóstico</label>
                <textarea name="diagnostico" class="form-control"></textarea>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label>Precio </label>
                    <input type="number" name="precio" class="form-control" step="0.01">
                </div>
                <div class="col-md-4">
                    <label>Fecha de Ingreso</label>
                    <input type="date" name="fecha_ingreso" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label>Fecha de Entrega</label>
                    <input type="date" name="fecha_entrega" class="form-control" required>
                </div>

            </div>


    </div>

    <br>


    <h5>Evidencia Fotográfica - Al Recibir</h5>
    <div class="row g-3 mb-3">
        <div class="col-md-6">

            <label class="form-label fw-bold">Foto Frontal</label>

            <!-- Contenedor principal -->
            <div class="image-container text-center " style="border-style: dashed; min-height: 100px;">

                <!-- Input de archivo -->
                <input type="file" name="foto_recibido_frontal" class="form-control visually-hidden"
                    onchange="previewImage(this)">

                <!-- Área interactiva -->
                <label class="w-100 h-100 d-flex flex-column justify-content-center align-items-center">

                    <!-- Preview de imagen -->
                    <div class="profile-img-edit">
                        <div class="crm-profile-img-edit">
                            <img class="crm-profile-pic avatar-155 image-preview" id="foto_recibido_frontal"
                                src="{{ asset('assets/images/repair/phone_front.png') }}" alt="profile-pic">
                        </div>
                    </div>

                </label>
            </div>
        </div>
        <div class="col-md-6">

            <label class="form-label fw-bold">Foto Trasera</label>

            <!-- Contenedor principal -->
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
                                src="{{ asset('assets/images/repair/phone_back.png') }}" alt="profile-pic">
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

    <h5>Evidencia Fotográfica - Al Entregar</h5>
    <div class="row g-3 mb-3">
        <div class="col-md-6 mb-4">
            <label class="form-label fw-bold">Foto Frontal</label>

            <!-- Contenedor principal -->
            <div class="image-container text-center " style="border-style: dashed; min-height: 100px;">

                <!-- Input de archivo -->
                <input type="file" name="foto_entregado_frontal" class="form-control visually-hidden"
                    onchange="previewImage(this)">

                <!-- Área interactiva -->
                <label class="w-100 h-100 d-flex flex-column justify-content-center align-items-center">

                    <!-- Preview de imagen -->
                    <div class="profile-img-edit">
                        <div class="crm-profile-img-edit">
                            <img class="crm-profile-pic avatar-155 image-preview" id="foto_entregado_frontal"
                                src="{{ asset('assets/images/repair/phone_front.png') }}" alt="profile-pic">
                        </div>
                    </div>

                </label>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <label class="form-label fw-bold">Foto Trasera</label>

            <!-- Contenedor principal -->
            <div class="image-container text-center " style="border-style: dashed; min-height: 100px;">

                <!-- Input de archivo -->
                <input type="file" name="foto_entregado_trasera" class="form-control visually-hidden"
                    onchange="previewImage(this)">

                <!-- Área interactiva -->
                <label class="w-100 h-100 d-flex flex-column justify-content-center align-items-center">

                    <!-- Preview de imagen -->
                    <div class="profile-img-edit">
                        <div class="crm-profile-img-edit">
                            <img class="crm-profile-pic avatar-155 image-preview" id="foto_entregado_trasera"
                                src="{{ asset('assets/images/repair/phone_back.png') }}" alt="profile-pic">
                        </div>
                    </div>

                </label>
            </div>
        </div>
    </div>
    </br>
    </br>
    </br>

    <button type="submit" class="btn btn-primary">Guardar Reparación</button>
    <a href="{{ route('repairs.index') }}" class="btn btn-secondary">Cancelar</a>
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
