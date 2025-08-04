@extends('dashboard.body.main')

@section('container')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">Editar Cliente</h4>
                        </div>
                    </div>

                    <div class="card-body">
                        <form action="{{ route('customers.update', $customer->id) }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            @method('put')
                            <!-- begin: Input Image -->
                            <div class="image-container">
                                <div class="form-group row align-items-center">
                                    <div class="col-md-12">
                                        <div class="profile-img-edit">
                                            <div class="crm-profile-img-edit">
                                                <img class="crm-profile-pic rounded-circle avatar-100 image-preview"
                                                    id="image-preview"
                                                    src="{{ $customer->tit_photo ? asset('storage/customers/' . $customer->tit_photo) : asset('assets/images/user/1.png') }}"
                                                    alt="profile-pic">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="input-group mb-4 col-lg-6">
                                        <div class="custom-file">
                                            <input type="file"
                                                class="custom-file-input @error('tit_photo') is-invalid @enderror"
                                                id="image" name="tit_photo" accept="image/*"
                                                onchange="previewImage(this)">
                                            <label class="custom-file-label" for="tit_photo">Elegir Imagen</label>
                                        </div>
                                        @error('tit_photo')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <!-- end: Input Image -->
                            <!-- begin: Input Data -->
                            <div class=" row align-items-center">
                                <div class="form-group col-md-6">
                                    <label for="tit_name">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('tit_name') is-invalid @enderror"
                                        id="tit_name" name="tit_name" value="{{ old('tit_name', $customer->tit_name) }}"
                                        required>
                                    @error('tit_name')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="tit_facebook">Facebook <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('tit_facebook') is-invalid @enderror"
                                        id="tit_facebook" name="tit_facebook"
                                        value="{{ old('tit_facebook', $customer->tit_facebook) }}" required>
                                    @error('tit_facebook')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="tit_email">Email<span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('tit_email') is-invalid @enderror"
                                        id="tit_email" name="tit_email" value="{{ old('tit_email', $customer->tit_email) }}"
                                        required>
                                    @error('tit_email')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="tit_phone">Teléfono <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('tit_phone') is-invalid @enderror"
                                        id="tit_phone" name="tit_phone" value="{{ old('tit_phone', $customer->tit_phone) }}"
                                        required>
                                    @error('tit_phone')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="tit_status">Status <span class="text-danger">*</span></label>
                                    <select class="form-control @error('tit_status') is-invalid @enderror" id="tit_status"
                                        name="tit_status" required onchange="updateSelectColor(this)">
                                        <option value="">Selecciona una opción</option>
                                        <option value="aprobado"
                                            {{ old('tit_status', $customer->tit_status) == 'aprobado' ? 'selected' : '' }}>
                                            Aprobado</option>
                                        <option value="riesgo"
                                            {{ old('tit_status', $customer->tit_status) == 'riesgo' ? 'selected' : '' }}>
                                            Riesgo</option>
                                        <option value="moroso"
                                            {{ old('tit_status', $customer->tit_status) == 'moroso' ? 'selected' : '' }}>
                                            Moroso</option>
                                        <option value="rescate"
                                            {{ old('tit_status', $customer->tit_status) == 'rescate' ? 'selected' : '' }}>
                                            Rescate</option>
                                    </select>
                                    @error('tit_status')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="form-group row align-items-center col-md-6 image-container">
                                    <div class="col-md-2">
                                        <div class="profile-img-edit">
                                            <div class="crm-profile-img-edit">
                                                <img class="crm-profile-pic avatar-100 image-preview" id="image-preview"
                                                    src="{{ $customer->tit_photo_proof_address ? asset('storage/customers/' . $customer->tit_photo_proof_address) : asset('assets/images/user/proof.png') }}"
                                                    alt="profile-pic">
                                            </div>
                                        </div>

                                    </div>
                                    <div class="col-md-10">
                                        <label for="tit_photo_proof_address">Foto comprobante de domicilio</label>

                                        <div class="custom-file">
                                            <input type="file"
                                                class="custom-file-input @error('tit_photo_proof_address') is-invalid @enderror"
                                                id="image" name="tit_photo_proof_address" accept="image/*"
                                                onchange="previewImage(this)">
                                            <label class="custom-file-label" for="tit_photo_proof_address">Elegir
                                                Imagen</label>
                                        </div>
                                        @error('tit_photo_proof_address')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="tit_link_location">Link de ubicación</label>
                                    <input type="text"
                                        class="form-control @error('tit_link_location') is-invalid @enderror"
                                        id="tit_link_location" name="tit_link_location"
                                        value="{{ old('tit_link_location', $customer->tit_link_location) }}">
                                    @error('tit_link_location')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="form-group row align-items-center col-md-6 image-container">
                                    <div class="col-md-2">
                                        <div class="profile-img-edit">
                                            <div class="crm-profile-img-edit">
                                                <img class="crm-profile-pic avatar-100 image-preview" id="image-preview"
                                                    src="{{ $customer->tit_photo_ine_f ? asset('storage/customers/' . $customer->tit_photo_ine_f) : asset('assets/images/user/id_card_f.png') }}"
                                                    alt="profile-pic">
                                            </div>
                                        </div>

                                    </div>
                                    <div class="col-md-10">
                                        <label for="tit_photo_ine_f">Foto INE parte frontal</label>

                                        <div class="custom-file">
                                            <input type="file"
                                                class="custom-file-input @error('tit_photo_ine_f') is-invalid @enderror"
                                                id="image" name="tit_photo_ine_f" accept="image/*"
                                                onchange="previewImage(this)">
                                            <label class="custom-file-label" for="tit_photo_ine_f">Elegir Imagen</label>
                                        </div>
                                        @error('tit_photo_ine_f')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="tit_work">Lugar de trabajo</label>
                                    <input type="text" class="form-control @error('tit_work') is-invalid @enderror"
                                        id="tit_work" name="tit_work"
                                        value="{{ old('tit_work', $customer->tit_work) }}">
                                    @error('tit_work')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="form-group row align-items-center col-md-6 image-container">
                                    <div class="col-md-2">
                                        <div class="profile-img-edit">
                                            <div class="crm-profile-img-edit">
                                                <img class="crm-profile-pic avatar-100 image-preview" id="image-preview"
                                                    src="{{ $customer->tit_photo_ine_b ? asset('storage/customers/' . $customer->tit_photo_ine_b) : asset('assets/images/user/id_card_b.png') }}"
                                                    alt="profile-pic">
                                            </div>
                                        </div>

                                    </div>
                                    <div class="col-md-10">
                                        <label for="tit_photo_ine_b">Foto INE parte trasera</label>

                                        <div class="custom-file">
                                            <input type="file"
                                                class="custom-file-input @error('tit_photo_ine_b') is-invalid @enderror"
                                                id="image" name="tit_photo_ine_b" accept="image/*"
                                                onchange="previewImage(this)">
                                            <label class="custom-file-label" for="tit_photo_ine_b">Elegir Imagen</label>
                                        </div>
                                        @error('tit_photo_ine_b')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="form-group row align-items-center col-md-6 image-container">
                                    <div class="col-md-2">
                                        <div class="profile-img-edit">
                                            <div class="crm-profile-img-edit">
                                                <img class="crm-profile-pic avatar-100 image-preview" id="image-preview"
                                                    src="{{ $customer->tit_photo_home ? asset('storage/customers/' . $customer->tit_photo_home) : asset('assets/images/user/home.jpg') }}"
                                                    alt="profile-pic">
                                            </div>
                                        </div>

                                    </div>
                                    <div class="col-md-10">
                                        <label for="tit_photo_home">Foto fachada de la casa</label>

                                        <div class="custom-file">
                                            <input type="file"
                                                class="custom-file-input @error('tit_photo_home') is-invalid @enderror"
                                                id="image" name="tit_photo_home" accept="image/*"
                                                onchange="previewImage(this)">
                                            <label class="custom-file-label" for="tit_photo_home">Elegir Imagen</label>
                                        </div>
                                        @error('tit_photo_home')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="tit_city">Ciudad <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('tit_city') is-invalid @enderror"
                                        id="tit_city" name="tit_city"
                                        value="{{ old('tit_city', $customer->tit_city) }}" required>
                                    @error('tit_city')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                                <div class="form-group col-md-12">
                                    <label for="tit_address">Dirección<span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('tit_address') is-invalid @enderror" name="tit_address" required>{{ old('tit_address', $customer->tit_address) }}</textarea>
                                    @error('tit_address')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>


                            <h5 class="py-3 mt-4 text-center bg-success">Datos de Referencia 1</h5>
                            <hr>


                            <div class=" row align-items-center">

                                <div class="form-group col-md-6">
                                    <label for="ref1_name">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('ref1_name') is-invalid @enderror"
                                        id="ref1_name" name="ref1_name"
                                        value="{{ old('ref1_name', $customer->ref1_name) }}" required>
                                    @error('ref1_name')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="ref1_phone">Teléfono <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('ref1_phone') is-invalid @enderror"
                                        id="ref1_phone" name="ref1_phone"
                                        value="{{ old('ref1_phone', $customer->ref1_phone) }}" required>
                                    @error('ref1_phone')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="form-group col-md-12">
                                    <label for="ref1_address">Dirección<span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('ref1_address') is-invalid @enderror" name="ref1_address" required>{{ old('ref1_address', $customer->ref1_address) }}</textarea>
                                    @error('ref1_address')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                            </div>

                            <h5 class="py-3 mt-4 text-center bg-success">Datos de Referencia 2</h5>
                            <hr>


                            <div class=" row align-items-center">

                                <div class="form-group col-md-6">
                                    <label for="ref2_name">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('ref2_name') is-invalid @enderror"
                                        id="ref2_name" name="ref2_name"
                                        value="{{ old('ref2_name', $customer->ref2_name) }}" required>
                                    @error('ref2_name')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="ref2_phone">Teléfono <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('ref2_phone') is-invalid @enderror"
                                        id="ref2_phone" name="ref2_phone"
                                        value="{{ old('ref2_phone', $customer->ref2_phone) }}" required>
                                    @error('ref2_phone')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="form-group col-md-12">
                                    <label for="ref2_address">Dirección<span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('ref2_address') is-invalid @enderror" name="ref2_address" required>{{ old('ref2_address', $customer->ref2_address) }}</textarea>
                                    @error('ref2_address')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                            </div>

                            <h5 class="py-3 mt-4 text-center bg-success">Datos de Referencia 3</h5>
                            <hr>


                            <div class=" row align-items-center">

                                <div class="form-group col-md-6">
                                    <label for="ref3_name">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('ref3_name') is-invalid @enderror"
                                        id="ref3_name" name="ref3_name"
                                        value="{{ old('ref3_name', $customer->ref3_name) }}" required>
                                    @error('ref3_name')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="ref3_phone">Teléfono <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('ref3_phone') is-invalid @enderror"
                                        id="ref3_phone" name="ref3_phone"
                                        value="{{ old('ref3_phone', $customer->ref3_phone) }}" required>
                                    @error('ref3_phone')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="form-group col-md-12">
                                    <label for="ref3_address">Dirección<span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('ref3_address') is-invalid @enderror" name="ref3_address" required>{{ old('ref3_address', $customer->ref3_address) }}</textarea>
                                    @error('ref3_address')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                            </div>

                            <h5 class="py-3 mt-4 text-center bg-warning">Datos del Aval</h5>
                            <hr>


                            <div class=" row align-items-center">

                                <div class="form-group col-md-6">
                                    <label for="aval_name">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('aval_name') is-invalid @enderror"
                                        id="aval_name" name="aval_name"
                                        value="{{ old('aval_name', $customer->aval_name) }}" required>
                                    @error('aval_name')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="aval_phone">Teléfono <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('aval_phone') is-invalid @enderror"
                                        id="aval_phone" name="aval_phone"
                                        value="{{ old('aval_phone', $customer->aval_phone) }}" required>
                                    @error('aval_phone')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="form-group col-md-12">
                                    <label for="aval_address">Dirección<span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('aval_address') is-invalid @enderror" name="aval_address" required>{{ old('aval_address', $customer->aval_address) }}</textarea>
                                    @error('aval_address')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="form-group row align-items-center col-md-6 image-container">
                                    <div class="col-md-2">
                                        <div class="profile-img-edit">
                                            <div class="crm-profile-img-edit">
                                                <img class="crm-profile-pic avatar-100 image-preview" id="image-preview"
                                                    src="{{ $customer->aval_photo_ine_f ? asset('storage/customers/' . $customer->aval_photo_ine_f) : asset('assets/images/user/id_card_f.png') }}"
                                                    alt="profile-pic">
                                            </div>
                                        </div>

                                    </div>
                                    <div class="col-md-10">
                                        <label for="aval_photo_ine_f">Foto INE parte frontal</label>

                                        <div class="custom-file">
                                            <input type="file"
                                                class="custom-file-input @error('aval_photo_ine_f') is-invalid @enderror"
                                                id="image" name="aval_photo_ine_f" accept="image/*"
                                                onchange="previewImage(this)">
                                            <label class="custom-file-label" for="aval_photo_ine_f">Elegir Imagen</label>
                                        </div>
                                        @error('aval_photo_ine_f')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="form-group row align-items-center col-md-6 image-container">
                                    <div class="col-md-2">
                                        <div class="profile-img-edit">
                                            <div class="crm-profile-img-edit">
                                                <img class="crm-profile-pic avatar-100 image-preview" id="image-preview"
                                                    src="{{ $customer->aval_photo_ine_f ? asset('storage/customers/' . $customer->aval_photo_ine_f) : asset('assets/images/user/id_card_f.png') }}"
                                                    alt="profile-pic">
                                            </div>
                                        </div>

                                    </div>
                                    <div class="col-md-10">
                                        <label for="aval_photo_ine_b">Foto INE parte trasera</label>

                                        <div class="custom-file">
                                            <input type="file"
                                                class="custom-file-input @error('aval_photo_ine_b') is-invalid @enderror"
                                                id="image" name="aval_photo_ine_b" accept="image/*"
                                                onchange="previewImage(this)">
                                            <label class="custom-file-label" for="aval_photo_ine_b">Elegir Imagen</label>
                                        </div>
                                        @error('aval_photo_ine_b')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="form-group row align-items-center col-md-6 image-container">
                                    <div class="col-md-2">
                                        <div class="profile-img-edit">
                                            <div class="crm-profile-img-edit">
                                                <img class="crm-profile-pic avatar-100 image-preview" id="image-preview"
                                                    src="{{ $customer->aval_photo_home ? asset('storage/customers/' . $customer->aval_photo_home) : asset('assets/images/user/proof.jpg') }}"
                                                    alt="profile-pic">
                                            </div>
                                        </div>

                                    </div>
                                    <div class="col-md-10">
                                        <label for="aval_photo_home">Foto comprobante de domicilio</label>

                                        <div class="custom-file">
                                            <input type="file"
                                                class="custom-file-input @error('aval_photo_home') is-invalid @enderror"
                                                id="image" name="aval_photo_home" accept="image/*"
                                                onchange="previewImage(this)">
                                            <label class="custom-file-label" for="aval_photo_home">Elegir Imagen</label>
                                        </div>
                                        @error('aval_photo_home')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>


                            </div>

                            <!-- end: Input Data -->
                            <div class="mt-2">
                                <button type="submit" class="btn btn-primary mr-2">Actualizar</button>
                                <a class="btn bg-danger" href="{{ route('customers.index') }}">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- Page end  -->
    </div>

    @include('components.preview-img-form')
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
