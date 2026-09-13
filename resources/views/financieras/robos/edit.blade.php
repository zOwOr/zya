@extends('dashboard.body.main')

@section('container')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8 offset-lg-2">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="mb-1 font-weight-bold">
                        <i class="fa-solid fa-pen-to-square text-warning mr-2"></i>Editar Reporte de Robo {{ $theftReport->report_code }}
                    </h4>
                    <p class="text-muted mb-0 font-size-13">Actualice la información del reporte o datos del acta de denuncia.</p>
                </div>
                <div>
                    <a href="{{ route('financieras.robos.show', $theftReport->id) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fa-solid fa-arrow-left mr-1"></i>Regresar al Detalle
                    </a>
                </div>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <form action="{{ route('financieras.robos.update', $theftReport->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="text-muted font-size-12">IMEI del Dispositivo</label>
                                <div class="font-weight-bold font-size-16">{{ $theftReport->device?->imei ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted font-size-12">Equipo</label>
                                <div class="font-weight-bold">{{ $theftReport->device?->brand?->name }} {{ $theftReport->device?->model }}</div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Sucursal <span class="text-danger">*</span></label>
                                <select name="branch_id" class="form-control @error('branch_id') is-invalid @enderror" required>
                                    @foreach ($branches as $b)
                                        <option value="{{ $b->id }}" {{ old('branch_id', $theftReport->branch_id) == $b->id ? 'selected' : '' }}>
                                            {{ $b->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('branch_id')
                                    <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">Fecha del Incidente <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="incident_date" class="form-control @error('incident_date') is-invalid @enderror" value="{{ old('incident_date', $theftReport->incident_date ? $theftReport->incident_date->format('Y-m-d\TH:i') : '') }}" required>
                                @error('incident_date')
                                    <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold">No. de Acta / Carpeta</label>
                                <input type="text" name="police_report_number" class="form-control" value="{{ old('police_report_number', $theftReport->police_report_number) }}">
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <label class="font-weight-bold">Narración de los Hechos <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="4" required>{{ old('description', $theftReport->description) }}</textarea>
                            @error('description')
                                <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('financieras.robos.show', $theftReport->id) }}" class="btn btn-light border mr-2">Cancelar</a>
                            <button type="submit" class="btn btn-warning px-4 font-weight-bold">
                                <i class="fa-solid fa-save mr-2"></i>Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
