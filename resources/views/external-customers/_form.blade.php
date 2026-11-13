@php
    $editing = isset($externalCustomer) && $externalCustomer->exists;
    $statusOptions = $statusOptions ?? \App\Models\ExternalCustomer::TIT_STATUS_OPTIONS;
@endphp

<div class="alert alert-info py-2 px-3 mb-3 font-size-13">
    <i class="fa-solid fa-circle-info mr-1"></i>
    Los campos marcados con <span class="text-danger font-weight-bold">*</span> coinciden con los campos obligatorios del catálogo estándar de Clientes para garantizar una promoción limpia a Cliente Interno cuando se requiera.
</div>

<div class="row">
    <div class="form-group col-md-6">
        <label for="tit_name" class="font-weight-bold">Nombre completo del titular <span class="text-danger">*</span></label>
        <input type="text" id="tit_name" name="tit_name"
            class="form-control @error('tit_name') is-invalid @enderror"
            value="{{ old('tit_name', $externalCustomer->tit_name ?? '') }}" required maxlength="100">
        @error('tit_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group col-md-3">
        <label for="tit_phone" class="font-weight-bold">Teléfono <span class="text-danger">*</span></label>
        <input type="text" id="tit_phone" name="tit_phone"
            class="form-control @error('tit_phone') is-invalid @enderror"
            value="{{ old('tit_phone', $externalCustomer->tit_phone ?? '') }}" required maxlength="15">
        @error('tit_phone')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group col-md-3">
        <label for="alternate_phone">Teléfono Alterno</label>
        <input type="text" id="alternate_phone" name="alternate_phone"
            class="form-control @error('alternate_phone') is-invalid @enderror"
            value="{{ old('alternate_phone', $externalCustomer->alternate_phone ?? '') }}" maxlength="15">
        @error('alternate_phone')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group col-md-4">
        <label for="tit_email" class="font-weight-bold">Email <span class="text-danger">*</span></label>
        <input type="email" id="tit_email" name="tit_email"
            class="form-control @error('tit_email') is-invalid @enderror"
            value="{{ old('tit_email', $externalCustomer->tit_email ?? '') }}" required maxlength="100">
        @error('tit_email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group col-md-4">
        <label for="tit_facebook" class="font-weight-bold">Facebook <span class="text-danger">*</span></label>
        <input type="text" id="tit_facebook" name="tit_facebook"
            class="form-control @error('tit_facebook') is-invalid @enderror"
            value="{{ old('tit_facebook', $externalCustomer->tit_facebook ?? '') }}" required maxlength="255">
        @error('tit_facebook')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group col-md-4">
        <label for="tit_status" class="font-weight-bold">Status <span class="text-danger">*</span></label>
        <select id="tit_status" name="tit_status"
            class="form-control @error('tit_status') is-invalid @enderror" required>
            @foreach ($statusOptions as $val => $label)
                <option value="{{ $val }}" {{ old('tit_status', $externalCustomer->tit_status ?? 'aprobado') === $val ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('tit_status')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group col-md-4">
        <label for="tit_city" class="font-weight-bold">Ciudad <span class="text-danger">*</span></label>
        <input type="text" id="tit_city" name="tit_city"
            class="form-control @error('tit_city') is-invalid @enderror"
            value="{{ old('tit_city', $externalCustomer->tit_city ?? '') }}" required maxlength="120">
        @error('tit_city')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group col-md-8">
        <label for="tit_address" class="font-weight-bold">Dirección <span class="text-danger">*</span></label>
        <input type="text" id="tit_address" name="tit_address"
            class="form-control @error('tit_address') is-invalid @enderror"
            value="{{ old('tit_address', $externalCustomer->tit_address ?? '') }}" required maxlength="999">
        @error('tit_address')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group col-md-4">
        <label for="tit_work">Lugar de Trabajo</label>
        <input type="text" id="tit_work" name="tit_work"
            class="form-control @error('tit_work') is-invalid @enderror"
            value="{{ old('tit_work', $externalCustomer->tit_work ?? '') }}" maxlength="255">
        @error('tit_work')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group col-md-4">
        <label for="position">Puesto / Ocupación</label>
        <input type="text" id="position" name="position"
            class="form-control @error('position') is-invalid @enderror"
            value="{{ old('position', $externalCustomer->position ?? '') }}" maxlength="255">
        @error('position')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group col-md-4">
        <label for="monthly_income">Ingreso Mensual ($)</label>
        <input type="number" step="0.01" min="0" id="monthly_income" name="monthly_income"
            class="form-control @error('monthly_income') is-invalid @enderror"
            value="{{ old('monthly_income', $externalCustomer->monthly_income ?? '') }}">
        @error('monthly_income')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group col-md-6">
        <label for="tit_link_location">Link de Ubicación (Google Maps)</label>
        <input type="text" id="tit_link_location" name="tit_link_location"
            class="form-control @error('tit_link_location') is-invalid @enderror"
            value="{{ old('tit_link_location', $externalCustomer->tit_link_location ?? '') }}" maxlength="999">
        @error('tit_link_location')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    @if (!$editing && isset($branches) && (auth()->user()->isSuperAdmin() || auth()->user()->can('cobros.all_branches')))
        <div class="form-group col-md-3">
            <label for="branch_id">Sucursal de Origen</label>
            <select id="branch_id" name="branch_id" class="form-control">
                @foreach ($branches as $br)
                    <option value="{{ $br->id }}" {{ (int) old('branch_id', auth()->user()->branch_id) === (int) $br->id ? 'selected' : '' }}>
                        {{ $br->name }}
                    </option>
                @endforeach
            </select>
        </div>
    @elseif ($editing && !$externalCustomer->isConverted())
        <div class="form-group col-md-3">
            <label for="status">Estado del Registro Externo</label>
            <select id="status" name="status" class="form-control">
                <option value="activo" {{ old('status', $externalCustomer->status) === 'activo' ? 'selected' : '' }}>Activo (disponible en POS)</option>
                <option value="inactivo" {{ old('status', $externalCustomer->status) === 'inactivo' ? 'selected' : '' }}>Inactivo</option>
            </select>
        </div>
    @endif

    <div class="form-group col-12">
        <label for="notes">Notas / Observaciones</label>
        <textarea id="notes" name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror"
            placeholder="Financiera habitual, referencia del equipo externo, comentarios de caja...">{{ old('notes', $externalCustomer->notes ?? '') }}</textarea>
        @error('notes')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

