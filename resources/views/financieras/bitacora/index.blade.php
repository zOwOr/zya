@extends('dashboard.body.main')

@section('container')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-lg-12 d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h4 class="mb-0 font-weight-bold text-primary">
                    <i class="fa-solid fa-book-open mr-2"></i> Bitácora de Abonos y Enganches a Financieras
                </h4>
                <small class="text-muted">Historial unificado de abonos a crédito, pagos externos y enganches registrados.</small>
            </div>
            <div class="mt-2 mt-md-0">
                <a href="{{ route('financieras.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="las la-arrow-left mr-1"></i> Volver a Financieras
                </a>
            </div>
        </div>
    </div>

    @include('financieras.bitacora.partial', [
        'bitacora' => $bitacora,
        'formAction' => route('financieras.bitacora.index'),
    ])
</div>
@endsection

