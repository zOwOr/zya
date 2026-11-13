@extends('dashboard.body.main')

@section('container')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="header-title">
                        <h4 class="card-title mb-0"><i class="fa-solid fa-user-plus text-warning mr-2"></i>Nuevo Cliente Externo</h4>
                    </div>
                    <a href="{{ route('external-customers.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-arrow-left mr-1"></i>Volver al Catálogo
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('external-customers.store') }}" method="POST">
                        @csrf
                        @include('external-customers._form')
                        <div class="mt-3 d-flex justify-content-end" style="gap: 8px;">
                            <a href="{{ route('external-customers.index') }}" class="btn btn-light">Cancelar</a>
                            <button type="submit" class="btn btn-primary font-weight-bold">
                                <i class="fa-solid fa-floppy-disk mr-1"></i>Guardar Cliente Externo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

