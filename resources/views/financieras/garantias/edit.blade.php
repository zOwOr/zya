@extends('dashboard.body.main')

@section('container')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8 offset-lg-2">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="mb-1 font-weight-bold">
                        <i class="fa-solid fa-pen-to-square text-warning mr-2"></i>Editar Garantía {{ $warranty->warranty_code }}
                    </h4>
                    <p class="text-muted mb-0 font-size-13">Actualice la descripción de la falla o notas de resolución.</p>
                </div>
                <div>
                    <a href="{{ route('financieras.garantias.show', $warranty->id) }}" class="btn btn-outline-secondary btn-sm">
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
                    <form action="{{ route('financieras.garantias.update', $warranty->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="text-muted font-size-12">IMEI del Dispositivo</label>
                                <div class="font-weight-bold font-size-16">{{ $warranty->device?->imei ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted font-size-12">Equipo</label>
                                <div class="font-weight-bold">{{ $warranty->device?->brand?->name }} {{ $warranty->device?->model }}</div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label class="font-weight-bold">Sucursal Asignada <span class="text-danger">*</span></label>
                            <select name="branch_id" class="form-control @error('branch_id') is-invalid @enderror" required>
                                @foreach ($branches as $b)
                                    <option value="{{ $b->id }}" {{ old('branch_id', $warranty->branch_id) == $b->id ? 'selected' : '' }}>
                                        {{ $b->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('branch_id')
                                <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label class="font-weight-bold">Descripción de la Falla <span class="text-danger">*</span></label>
                            <textarea name="issue_description" class="form-control @error('issue_description') is-invalid @enderror" rows="4" required>{{ old('issue_description', $warranty->issue_description) }}</textarea>
                            @error('issue_description')
                                <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-4">
                            <label class="font-weight-bold">Notas de Resolución / Conclusiones</label>
                            <textarea name="resolution_notes" class="form-control @error('resolution_notes') is-invalid @enderror" rows="3">{{ old('resolution_notes', $warranty->resolution_notes) }}</textarea>
                            @error('resolution_notes')
                                <div class="text-danger font-size-12 mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('financieras.garantias.show', $warranty->id) }}" class="btn btn-light border mr-2">Cancelar</a>
                            <button type="submit" class="btn btn-warning px-4 font-weight-bold text-white">
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
