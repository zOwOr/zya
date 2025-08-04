@extends('dashboard.body.main')

@section('container')
    <div class="container-fluid mb-3">
        <div class="row">
            <div class="col-lg-12">
                <div class="card car-transparent">
                    <div class="card-body p-0">
                        <div class="profile-image position-relative">
                            <img src="{{ asset('assets/images/page-img/profile.png') }}" class="img-fluid rounded h-30 w-100"
                                alt="profile-image">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row px-3">
            <!-- begin: Left Detail Employee -->
            <div class="col-lg-4 card-profile mb-5 h-50">
                <div class="card card-block card-stretch card-height ">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="profile-img position-relative">
                                <img src="{{ $customer->tit_photo ? asset('storage/customers/' . $customer->tit_photo) : asset('assets/images/user/1.png') }}"
                                    class="img-fluid rounded avatar-110" alt="profile-image">
                            </div>
                            <div class="ml-3">
                                <h4 class="mb-1">{{ $customer->tit_name }}</h4>
                                <p class="mb-2">{{ $customer->tit_facebook }}</p>
                                
                                <a href="{{ route('customers.index') }}" class="btn btn-danger font-size-14">Regresar</a>
                            </div>
                        </div>
                        <ul class="list-inline p-0 m-0">
                            <li class="mb-2">
                                <div class="d-flex align-items-center">
                                    <svg class="svg-icon mr-3" height="16" width="16"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    <p class="mb-0">{{ $customer->tit_email }}</p>
                                </div>
                            </li>
                            <li class="mb-2">
                                <div class="d-flex align-items-center">
                                    <svg class="svg-icon mr-3" height="16" width="16"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
                                        </path>
                                    </svg>
                                    <p class="mb-0">{{ $customer->tit_phone }}</p>
                                </div>
                            </li>
                            <li class="mb-2">
                                <div class="d-flex align-items-center">
                                    <svg class="svg-icon mr-3" height="16" width="16"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <p class="mb-0">{{ $customer->tit_city ? $customer->tit_city : 'Desconocido' }}</p>
                                </div>
                            </li>
                            <li class="mb-2">
                                <div class="form-group col-md-6">
                                    <label for="tit_status">Status <span class="text-danger">*</span></label>
                                    <select class="form-control @error('tit_status') is-invalid @enderror" id="tit_status"
                                        name="tit_status" required onchange="updateSelectColor(this)" readonly>
                                        <option value="">Selecciona una opción</option>
                                        <option value="aprobado"
                                            {{ old('tit_status', $customer->tit_status) == 'aprobado' ? 'selected' : '' }}>
                                            Aprobado
                                        </option>
                                        <option value="riesgo"
                                            {{ old('tit_status', $customer->tit_status) == 'riesgo' ? 'selected' : '' }}>
                                            Riesgo
                                        </option>
                                        <option value="moroso"
                                            {{ old('tit_status', $customer->tit_status) == 'moroso' ? 'selected' : '' }}>
                                            Moroso
                                        </option>
                                        <option value="rescate"
                                            {{ old('tit_status', $customer->tit_status) == 'rescate' ? 'selected' : '' }}>
                                            Rescate
                                        </option>
                                    </select>
                                    @error('tit_status')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                            </li>
                        </ul>
                    </div>
                </div>


                <div class="card card-block card-stretch card-height">
                    <div class="card-body">

                        <h5 class="py-3 text-center bg-success">Datos de Referencia 1</h5>
                        <hr>

                        <ul class="list-inline p-0 m-0">
                            <li class="col-lg-12">
                                <div class="form-group row">
                                    <div class="col-sm-3 col-4">
                                        <label class="col-form-label">Nombre</label>
                                    </div>
                                    <div class="col-sm-9 col-8">
                                        <input type="text" class="form-control bg-white"
                                            value="{{ $customer->ref1_name }}" readonly>
                                    </div>
                                </div>
                            </li>
                            <li class="col-lg-12">
                                <div class="form-group row">
                                    <div class="col-sm-3 col-4">
                                        <label class="col-form-label">Teléfono</label>
                                    </div>
                                    <div class="col-sm-9 col-8">
                                        <input type="text" class="form-control bg-white"
                                            value="{{ $customer->ref1_phone }}" readonly>
                                    </div>
                                </div>
                            </li>
                            <li class="col-lg-12">
                                <div class="form-group row">
                                    <div class="col-sm-3 col-4">
                                        <label class="col-form-label">Dirección</label>
                                    </div>
                                    <div class="col-sm-9 col-8">
                                        <textarea class="form-control bg-white" readonly>{{ $customer->ref1_address }}</textarea>
                                    </div>
                                </div>
                            </li>
                        </ul>

                        <h5 class="py-3 text-center bg-success">Datos de Referencia 2</h5>
                        <hr>

                        <ul class="list-inline p-0 m-0">
                            <li class="col-lg-12">
                                <div class="form-group row">
                                    <div class="col-sm-3 col-4">
                                        <label class="col-form-label">Nombre</label>
                                    </div>
                                    <div class="col-sm-9 col-8">
                                        <input type="text" class="form-control bg-white"
                                            value="{{ $customer->ref2_name }}" readonly>
                                    </div>
                                </div>
                            </li>
                            <li class="col-lg-12">
                                <div class="form-group row">
                                    <div class="col-sm-3 col-4">
                                        <label class="col-form-label">Teléfono</label>
                                    </div>
                                    <div class="col-sm-9 col-8">
                                        <input type="text" class="form-control bg-white"
                                            value="{{ $customer->ref2_phone }}" readonly>
                                    </div>
                                </div>
                            </li>
                            <li class="col-lg-12">
                                <div class="form-group row">
                                    <div class="col-sm-3 col-4">
                                        <label class="col-form-label">Dirección</label>
                                    </div>
                                    <div class="col-sm-9 col-8">
                                        <textarea class="form-control bg-white" readonly>{{ $customer->ref2_address }}</textarea>
                                    </div>
                                </div>
                            </li>
                        </ul>

                        <h5 class="py-3 text-center bg-success">Datos de Referencia 3</h5>
                        <hr>

                        <ul class="list-inline p-0 m-0">
                            <li class="col-lg-12">
                                <div class="form-group row">
                                    <div class="col-sm-3 col-4">
                                        <label class="col-form-label">Nombre</label>
                                    </div>
                                    <div class="col-sm-9 col-8">
                                        <input type="text" class="form-control bg-white"
                                            value="{{ $customer->ref3_name }}" readonly>
                                    </div>
                                </div>
                            </li>
                            <li class="col-lg-12">
                                <div class="form-group row">
                                    <div class="col-sm-3 col-4">
                                        <label class="col-form-label">Teléfono</label>
                                    </div>
                                    <div class="col-sm-9 col-8">
                                        <input type="text" class="form-control bg-white"
                                            value="{{ $customer->ref3_phone }}" readonly>
                                    </div>
                                </div>
                            </li>
                            <li class="col-lg-12">
                                <div class="form-group row">
                                    <div class="col-sm-3 col-4">
                                        <label class="col-form-label">Dirección</label>
                                    </div>
                                    <div class="col-sm-9 col-8">
                                        <textarea class="form-control bg-white" readonly>{{ $customer->ref3_address }}</textarea>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>


            </div>

            <!-- begin: Right Detail Employee -->
            <div class="col-lg-8 card-profile">
                <div class="card card-block card-stretch mb-0">
                    <div class="card-header px-3 bg-primary">
                        <div class="header-title">
                            <h4 class="card-title text-center">Información del Cliente</h4>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <ul class="list-inline p-0 mb-0">
                            <li class="col-lg-12">
                                <div class="form-group row">
                                    <div class="col-sm-3 col-4">
                                        <label class="col-form-label">Nombre</label>
                                    </div>
                                    <div class="col-sm-9 col-8">
                                        <input type="text" class="form-control bg-white"
                                            value="{{ $customer->tit_name }}" readonly>
                                    </div>
                                </div>
                            </li>
                            <li class="col-lg-12">
                                <div class="form-group row">
                                    <div class="col-sm-3 col-4">
                                        <label class="col-form-label">Email</label>
                                    </div>
                                    <div class="col-sm-9 col-8">
                                        <input type="text" class="form-control bg-white"
                                            value="{{ $customer->tit_email }}" readonly>
                                    </div>
                                </div>
                            </li>
                            <li class="col-lg-12">
                                <div class="form-group row">
                                    <div class="col-sm-3 col-4">
                                        <label class="col-form-label">Teléfono</label>
                                    </div>
                                    <div class="col-sm-9 col-8">
                                        <input type="text" class="form-control bg-white"
                                            value="{{ $customer->tit_phone }}" readonly>
                                    </div>
                                </div>
                            </li>
                            <li class="col-lg-12">
                                <div class="form-group row">
                                    <div class="col-sm-3 col-4">
                                        <label class="col-form-label">Facebook</label>
                                    </div>
                                    <div class="col-sm-9 col-8">
                                        <input type="text" class="form-control bg-white"
                                            value="{{ $customer->tit_facebook }}" readonly>
                                    </div>
                                </div>
                            </li>
                            <li class="col-lg-12">
                                <div class="form-group row">
                                    <div class="col-sm-3 col-4 my-auto">
                                        <label class="col-form-label">Foto INE parte frontal</label>
                                    </div>
                                    <div class="col-sm-9 col-8">
                                        <img class="rounded" style="max-height: 150px; width: auto;"
                                            src="{{ $customer->tit_photo_ine_f ? asset('storage/customers/' . $customer->tit_photo_ine_f) : asset('assets/images/user/id_card-f.png') }}">
                                    </div>
                                </div>
                            </li>
                            <li class="col-lg-12">
                                <div class="form-group row">
                                    <div class="col-sm-3 col-4 my-auto">
                                        <label class="col-form-label">Foto INE parte trasera</label>
                                    </div>
                                    <div class="col-sm-9 col-8">
                                        <img class=" rounded" style="max-height: 150px; width: auto;"
                                            src="{{ $customer->tit_photo_ine_b ? asset('storage/customers/' . $customer->tit_photo_ine_b) : asset('assets/images/user/id_card-b.png') }}">
                                    </div>
                                </div>
                            </li>
                            <li class="col-lg-12">
                                <div class="form-group row">
                                    <div class="col-sm-3 col-4 my-auto">
                                        <label class="col-form-label">Foto comprobante de domicilio</label>
                                    </div>
                                    <div class="col-sm-9 col-8">
                                        <img class=" rounded" style="max-height: 150px; width: auto;"
                                            src="{{ $customer->tit_photo_proof_address ? asset('storage/customers/' . $customer->tit_photo_proof_address) : asset('assets/images/user/proof.png') }}">
                                    </div>
                                </div>
                            </li>
                            <li class="col-lg-12">
                                <div class="form-group row">
                                    <div class="col-sm-3 col-4 my-auto">
                                        <label class="col-form-label">Foto fachada de la casa</label>
                                    </div>
                                    <div class="col-sm-9 col-8">
                                        <img class=" rounded" style="max-height: 150px; width: auto;"
                                            src="{{ $customer->tit_photo_home ? asset('storage/customers/' . $customer->tit_photo_home) : asset('assets/images/user/home.jpg') }}">
                                    </div>
                                </div>
                            </li>
                            <li class="col-lg-12">
                                <div class="form-group row">
                                    <div class="col-sm-3 col-4">
                                        <label class="col-form-label">Link de ubicación</label>
                                    </div>
                                    <div class="col-sm-9 col-8">
                                        <input type="text" class="form-control bg-white"
                                            value="{{ $customer->tit_link_location }}" readonly>
                                    </div>
                                </div>
                            </li>
                            <li class="col-lg-12">
                                <div class="form-group row">
                                    <div class="col-sm-3 col-4">
                                        <label class="col-form-label">Lugar de trabajo</label>
                                    </div>
                                    <div class="col-sm-9 col-8">
                                        <input type="text" class="form-control bg-white"
                                            value="{{ $customer->tit_work }}" readonly>
                                    </div>
                                </div>
                            </li>

                            <li class="col-lg-12">
                                <div class="form-group row">
                                    <div class="col-sm-3 col-4">
                                        <label class="col-form-label">Ciudad</label>
                                    </div>
                                    <div class="col-sm-9 col-8">
                                        <input type="text" class="form-control bg-white"
                                            value="{{ $customer->tit_city }}" readonly>
                                    </div>
                                </div>
                            </li>
                            <li class="col-lg-12">
                                <div class="form-group row">
                                    <div class="col-sm-3 col-4">
                                        <label class="col-form-label">Dirección</label>
                                    </div>
                                    <div class="col-sm-9 col-8">
                                        <textarea class="form-control bg-white" readonly>{{ $customer->tit_address }}</textarea>
                                    </div>
                                </div>
                            </li>
                        </ul>

                    </div>
                </div>
            </div>

        </div>

        <div class="row px-3">
            <!-- begin: Right Detail Employee -->
            <div class="col-lg-12 "">
                <div class="card card-block card-stretch mb-0">
                    <div class="card-header px-3 bg-warning">
                        <div class="header-title ">
                            <h4 class="card-title text-center ">Aval del Cliente</h4>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <ul class="list-inline p-0 mb-0">
                            <li class="col-lg-12">
                                <div class="form-group row">
                                    <div class="col-sm-3 col-4">
                                        <label class="col-form-label">Nombre</label>
                                    </div>
                                    <div class="col-sm-9 col-8">
                                        <input type="text" class="form-control bg-white"
                                            value="{{ $customer->aval_name }}" readonly>
                                    </div>
                                </div>
                            </li>
                            <li class="col-lg-12">
                                <div class="form-group row">
                                    <div class="col-sm-3 col-4">
                                        <label class="col-form-label">Teléfono</label>
                                    </div>
                                    <div class="col-sm-9 col-8">
                                        <input type="text" class="form-control bg-white"
                                            value="{{ $customer->aval_phone }}" readonly>
                                    </div>
                                </div>
                            </li>
                            <li class="col-lg-12">
                                <div class="form-group row">
                                    <div class="col-sm-3 col-4 my-auto">
                                        <label class="col-form-label">Foto INE parte frontal</label>
                                    </div>
                                    <div class="col-sm-9 col-8">
                                        <img class="rounded" style="max-height: 150px; width: auto;"
                                            src="{{ $customer->aval_photo_ine_f ? asset('storage/customers/' . $customer->aval_photo_ine_f) : asset('assets/images/user/id_card-f.png') }}">
                                    </div>
                                </div>
                            </li>
                            <li class="col-lg-12">
                                <div class="form-group row">
                                    <div class="col-sm-3 col-4 my-auto">
                                        <label class="col-form-label">Foto INE parte trasera</label>
                                    </div>
                                    <div class="col-sm-9 col-8">
                                        <img class=" rounded" style="max-height: 150px; width: auto;"
                                            src="{{ $customer->aval_photo_ine_b ? asset('storage/customers/' . $customer->aval_photo_ine_b) : asset('assets/images/user/id_card-b.png') }}">
                                    </div>
                                </div>
                            </li>

                            <li class="col-lg-12">
                                <div class="form-group row">
                                    <div class="col-sm-3 col-4 my-auto">
                                        <label class="col-form-label">Foto comprobante de domicilio</label>
                                    </div>
                                    <div class="col-sm-9 col-8">
                                        <img class=" rounded" style="max-height: 150px; width: auto;"
                                            src="{{ $customer->aval_photo_home ? asset('storage/customers/' . $customer->aval_photo_home) : asset('assets/images/user/proof.pmg') }}">
                                    </div>
                                </div>
                            </li>
                            <li class="col-lg-12">
                                <div class="form-group row">
                                    <div class="col-sm-3 col-4">
                                        <label class="col-form-label">Dirección</label>
                                    </div>
                                    <div class="col-sm-9 col-8">
                                        <textarea class="form-control bg-white" readonly>{{ $customer->aval_address }}</textarea>
                                    </div>
                                </div>
                            </li>
                        </ul>

                    </div>
                </div>
            </div>
            <!-- end: Left Detail Employee -->

            <!-- begin: Right Detail Employee -->
            <!-- end: Right Detail Employee -->
        </div>
    </div>
@endsection
<script>
    function updateSelectColor(select) {
        // Quita clases anteriores
        select.classList.remove('bg-success', 'bg-warning', 'bg-danger', 'bg-info', 'text-white');

        switch (select.value) {
            case 'aprobado':
                select.classList.add('bg-success', 'text-white');
                break;
            case 'riesgo':
                select.classList.add('bg-warning', 'text-dark');
                break;
            case 'moroso':
                select.classList.add('bg-danger', 'text-white');
                break;
            case 'rescate':
                select.classList.add('bg-primary', 'text-white');
                break;
        }
    }

    // Aplicar color al cargar la página si ya hay un valor seleccionado
    document.addEventListener('DOMContentLoaded', function() {
        const select = document.getElementById('tit_status');
        updateSelectColor(select);
    });
</script>
