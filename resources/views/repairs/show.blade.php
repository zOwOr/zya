@extends('dashboard.body.main')

@section('container')
    <div class="container">
        <h2>Detalle de Reparación</h2>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">Cliente</label>
                <input type="text" class="form-control" value="{{ $repair->cliente }}" readonly>
            </div>
            <div class="col-md-6">
                <label class="form-label">Teléfono</label>
                <input type="text" class="form-control" value="{{ $repair->telefono }}" readonly>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">IMEI</label>
                <input type="text" class="form-control" value="{{ $repair->imei }}" readonly>
            </div>
            <div class="col-md-4">
                <label class="form-label">Estado</label>
                <input type="text" class="form-control" value="{{ ucfirst($repair->estado) }}" readonly>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">Marca</label>
                <input type="text" class="form-control" value="{{ $repair->marca }}" readonly>
            </div>
            <div class="col-md-6">
                <label class="form-label">Modelo</label>
                <input type="text" class="form-control" value="{{ $repair->modelo }}" readonly>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Problema Reportado</label>
            <textarea class="form-control" readonly>{{ $repair->problema_reportado }}</textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Diagnóstico</label>
            <textarea class="form-control" readonly>{{ $repair->diagnostico }}</textarea>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label">Precio</label>
                <input type="text" class="form-control" value="${{ number_format($repair->precio, 2) }}" readonly>
            </div>
            <div class="col-md-4">
                <label class="form-label">Fecha de Ingreso</label>
                <input type="date" class="form-control" value="{{ $repair->fecha_ingreso }}" readonly>
            </div>
            <div class="col-md-4">
                <label class="form-label">Fecha de Entrega</label>
                <input type="date" class="form-control" value="{{ $repair->fecha_entrega }}" readonly>
            </div>
            
        </div>

        <hr>
        <h5>Evidencia Fotográfica - Al Recibir</h5>
        <div class="row g-3 mb-3">
            <div class="col-md-6 text-center">
                
                <label class="form-label fw-bold">Foto Frontal</label>
                <div class="border p-2">
                    <img src="{{ asset('storage/' . $repair->foto_recibido_frontal) }}" class="img-fluid" style="max-width: 300px; max-height: 200px; object-fit: cover;"
                        alt="Frontal Recibido">
                </div>
            </div>
            <div class="col-md-6 text-center">
                <label class="form-label fw-bold">Foto Trasera</label>
                <div class="border p-2">
                    <img src="{{ asset('storage/' . $repair->foto_recibido_trasera) }}" class="img-fluid " style="max-width: 300px; max-height: 200px; object-fit: cover;"
                        alt="Trasera Recibido">
                </div>
            </div>
        </div>

        <hr>
        <h5>Evidencia Fotográfica - Al Entregar</h5>
        <div class="row g-3 mb-3">
            <div class="col-md-6 text-center">
                <label class="form-label fw-bold">Foto Frontal</label>
                <div class="border p-2">
                    <img src="{{ asset('storage/' . $repair->foto_entregado_frontal) }}" class="img-fluid"
                        alt="Frontal Entregado">
                </div>
            </div>
            <div class="col-md-6 text-center">
                <label class="form-label fw-bold">Foto Trasera</label>
                <div class="border p-2">
                    <img src="{{ asset('storage/' . $repair->foto_entregado_trasera) }}" class="img-fluid"
                        alt="Trasera Entregado">
                </div>
            </div>
        </div>

        <a href="{{ route('repairs.index') }}" class="btn btn-secondary mt-3">Volver</a>
    </div>
@endsection
