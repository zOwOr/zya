@extends('dashboard.body.main')

@section('container')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <div class="header-title">
                        <h4 class="card-title">Nuevo Cliente</h4>
                    </div>
                </div>

                <div class="card-body">
                    <form action="{{ route('customers.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                        <!-- begin: Input Image -->
                        <div class="image-container">
                            <div class="form-group row align-items-center">
                                <div class="col-md-12">
                                    <div class="profile-img-edit">
                                        <div class="crm-profile-img-edit">
                                            <img class="crm-profile-pic rounded-circle avatar-100 image-preview" id="image-preview" src="{{ asset('assets/images/user/1.png') }}" alt="profile-pic">
                                        </div>
                                    </div>
                                </div>
                            </div>
    
                            <div class="row">
                                <div class="input-group mb-4 col-lg-6">
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input @error('tit_photo') is-invalid @enderror" id="image" name="tit_photo" accept="image/*" onchange="previewImage(this)">
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
                                <label for="name">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('tit_name') is-invalid @enderror" id="tit_name" name="tit_name" value="{{ old('tit_name') }}" required>
                                @error('tit_name')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label for="tit_facebook">Facebook <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('tit_facebook') is-invalid @enderror" id="tit_facebook" name="tit_facebook" value="{{ old('tit_facebook') }}" required>
                                @error('tit_facebook')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label for="tit_email">Email <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('tit_email') is-invalid @enderror" id="tit_email" name="tit_email" value="{{ old('tit_email') }}" required>
                                @error('tit_email')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label for="tit_phone">Teléfono <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('tit_phone') is-invalid @enderror" id="tit_phone" name="tit_phone" value="{{ old('tit_phone') }}" required>
                                @error('tit_phone')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>
                            <div class="form-group row align-items-center col-md-6 image-container">
                                <div class="col-md-2">
                                    <div class="profile-img-edit">
                                        <div class="crm-profile-img-edit">
                                            <img class="crm-profile-pic  avatar-100 image-preview" id="tit_photo_proof_address" src="{{ asset('assets/images/user/proof.png') }}" alt="profile-pic">
                                        </div>
                                    </div>

                                </div>
                                <div class="col-md-10">
                                    <label for="tit_photo_proof_address">Foto comprobante de domicilio</label>

                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input @error('tit_photo_proof_address') is-invalid @enderror" id="tit_photo_proof_address" name="tit_photo_proof_address" accept="image/*" onchange="previewImage(this)">
                                        <label class="custom-file-label" for="tit_photo_proof_address">Elegir Imagen</label>
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
                                <input type="text" class="form-control @error('tit_link_location') is-invalid @enderror" id="tit_link_location" name="tit_link_location" value="{{ old('tit_link_location') }}">
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
                                            <img class="crm-profile-pic  avatar-100 image-preview" id="image-preview" src="{{ asset('assets/images/user/id_card_f.png') }}" alt="profile-pic">
                                        </div>
                                    </div>

                                </div>
                                <div class="col-md-10">
                                    <label for="tit_photo_ine_f">Foto INE parte frontal</label>

                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input @error('tit_photo_ine_f') is-invalid @enderror" id="image" name="tit_photo_ine_f" accept="image/*" onchange="previewImage(this)">
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
                                <input type="text" class="form-control @error('tit_work') is-invalid @enderror" id="tit_work" name="tit_work" value="{{ old('tit_work') }}">
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
                                            <img class="crm-profile-pic  avatar-100 image-preview" id="tit_photo_ine_b" src="{{ asset('assets/images/user/id_card_b.png') }}" alt="profile-pic">
                                        </div>
                                    </div>

                                </div>
                                <div class="col-md-10">
                                    <label for="tit_photo_ine_b">Foto INE parte trasera</label>

                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input @error('tit_photo_ine_b') is-invalid @enderror" id="tit_photo_ine_b" name="tit_photo_ine_b" accept="image/*" onchange="previewImage(this)">
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
                                            <img class="crm-profile-pic  avatar-100 image-preview" id="tit_photo_home" src="{{ asset('assets/images/user/home.jpg') }}" alt="profile-pic">
                                        </div>
                                    </div>

                                </div>
                                <div class="col-md-10">
                                    <label for="tit_photo_home">Foto fachada de la casa</label>

                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input @error('tit_photo_home') is-invalid @enderror" id="tit_photo_home" name="tit_photo_home" accept="image/*" onchange="previewImage(this)">
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
                                <input type="text" class="form-control @error('tit_city') is-invalid @enderror" id="tit_city" name="tit_city" value="{{ old('tit_city') }}" required>
                                @error('tit_city')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>
                            <div class="form-group col-md-12">
                                <label for="tit_address">Dirección <span class="text-danger">*</span></label>
                                <textarea class="form-control @error('tit_address') is-invalid @enderror" name="tit_address" required>{{ old('tit_address') }}</textarea>
                                @error('tit_address')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>
                        </div>
                        <!-- end: Input Data -->
                        <div class="mt-2">
                            <button type="submit" class="btn btn-primary mr-2">Guardar</button>
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
