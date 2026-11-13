@extends('dashboard.body.main')

@section('container')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="header-title">
                        <h4 class="card-title mb-0"><i class="fa-solid fa-user-pen text-success mr-2"></i>Editar Cliente Externo #{{ $externalCustomer->id }}</h4>
                    </div>
                    <a href="{{ route('external-customers.show', $externalCustomer) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-arrow-left mr-1"></i>Volver al Expediente
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('external-customers.update', $externalCustomer) }}" method="POST">
                        @csrf
                        @method('PUT')
                        @include('external-customers._form')
                        <div class="mt-3 d-flex justify-content-end" style="gap: 8px;">
                            <a href="{{ route('external-customers.show', $externalCustomer) }}" class="btn btn-light">Cancelar</a>
                            <button type="submit" class="btn btn-success font-weight-bold">
                                <i class="fa-solid fa-floppy-disk mr-1"></i>Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

