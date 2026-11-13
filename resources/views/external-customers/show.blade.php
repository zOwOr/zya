@extends('dashboard.body.main')

@section('container')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            @if (session()->has('success'))
                <div class="alert text-white bg-success" role="alert">
                    <div class="iq-alert-text">{{ session('success') }}</div>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><i class="ri-close-line"></i></button>
                </div>
            @endif
            @if (session()->has('error'))
                <div class="alert text-white bg-danger" role="alert">
                    <div class="iq-alert-text">{{ session('error') }}</div>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><i class="ri-close-line"></i></button>
                </div>
            @endif
            @if (session()->has('info'))
                <div class="alert text-white bg-info" role="alert">
                    <div class="iq-alert-text">{{ session('info') }}</div>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><i class="ri-close-line"></i></button>
                </div>
            @endif

            @if ($duplicateInternal && !$externalCustomer->isConverted())
                <div class="alert alert-warning border-warning shadow-sm d-flex flex-wrap justify-content-between align-items-center">
                    <div class="mb-2 mb-md-0">
                        <strong><i class="fa-solid fa-triangle-exclamation mr-1"></i>Coincidencia detectada en Clientes Internos:</strong>
                        Ya existe un cliente interno con los datos de contacto de este registro:
                        <strong>#{{ $duplicateInternal->id }} — {{ $duplicateInternal->tit_name }} ({{ $duplicateInternal->tit_phone }})</strong>.
                        Para evitar duplicados puedes vincular este expediente externo con el cliente interno existente.
                    </div>
                    @can('external_customer.convert')
                        <form action="{{ route('external-customers.convert', $externalCustomer) }}" method="POST"
                            onsubmit="return confirm('¿Vincular este cliente externo con el Cliente Interno existente #{{ $duplicateInternal->id }} ({{ addslashes($duplicateInternal->tit_name) }}) sin crear duplicados?');">
                            @csrf
                            <input type="hidden" name="link_existing" value="1">
                            <button type="submit" class="btn btn-sm btn-dark font-weight-bold">
                                <i class="fa-solid fa-link mr-1"></i>Vincular con Cliente Interno Existente
                            </button>
                        </form>
                    @endcan
                </div>
            @endif

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title mb-1">
                            <i class="fa-solid fa-user-tag text-warning mr-2"></i>{{ $externalCustomer->tit_name }}
                            @if ($externalCustomer->isConverted())
                                <span class="badge badge-success ml-2 font-size-12">Promovido a Cliente Interno</span>
                            @elseif ($externalCustomer->status === 'inactivo')
                                <span class="badge badge-secondary ml-2 font-size-12">Inactivo</span>
                            @else
                                <span class="badge badge-warning text-dark ml-2 font-size-12">Externo Activo</span>
                            @endif
                        </h4>
                        <small class="text-muted">
                            Alta: {{ optional($externalCustomer->created_at)->format('d/m/Y H:i') }}
                            | Sucursal: <strong>{{ $externalCustomer->branch?->name ?? 'General' }}</strong>
                            | Registró: <strong>{{ $externalCustomer->creator?->name ?? 'Sistema' }}</strong>
                        </small>
                    </div>
                    <div class="d-flex flex-wrap align-items-center mt-2 mt-md-0" style="gap: 8px;">
                        <a href="{{ route('external-customers.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fa-solid fa-arrow-left mr-1"></i>Catálogo
                        </a>
                        @can('external_customer.edit')
                            <a href="{{ route('external-customers.edit', $externalCustomer) }}" class="btn btn-sm btn-outline-success">
                                <i class="fa-solid fa-pen mr-1"></i>Editar
                            </a>
                        @endcan
                        @if ($externalCustomer->converted_customer_id && auth()->user()->can('customer.read'))
                            <a href="{{ route('customers.show', $externalCustomer->converted_customer_id) }}" class="btn btn-sm btn-success font-weight-bold">
                                <i class="fa-solid fa-id-card mr-1"></i>Ver Ficha Cliente Interno #{{ $externalCustomer->converted_customer_id }}
                            </a>
                        @elseif (!$externalCustomer->isConverted())
                            @can('external_customer.convert')
                                <form action="{{ route('external-customers.convert', $externalCustomer) }}" method="POST" class="d-inline"
                                    onsubmit="return confirm('¿Convertir y habilitar a {{ addslashes($externalCustomer->tit_name) }} como Cliente Interno en el catálogo estándar? Su historial previo en caja y financieras se conservará intacto.');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary font-weight-bold shadow-sm">
                                        <i class="fa-solid fa-user-plus mr-1"></i>Convertir / Habilitar como Cliente Interno
                                    </button>
                                </form>
                            @endcan
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-2"><strong>Teléfono:</strong> {{ $externalCustomer->tit_phone }}</div>
                        <div class="col-md-4 mb-2"><strong>Email:</strong> {{ $externalCustomer->tit_email }}</div>
                        <div class="col-md-4 mb-2"><strong>Facebook:</strong> {{ $externalCustomer->tit_facebook }}</div>
                        <div class="col-md-4 mb-2"><strong>Estatus Titular:</strong> {{ ucfirst($externalCustomer->tit_status) }}</div>
                        <div class="col-md-4 mb-2"><strong>Ciudad:</strong> {{ $externalCustomer->tit_city }}</div>
                        <div class="col-md-4 mb-2"><strong>Tel. Alterno:</strong> {{ $externalCustomer->alternate_phone ?: '-' }}</div>
                        <div class="col-md-8 mb-2"><strong>Dirección:</strong> {{ $externalCustomer->tit_address }}</div>
                        <div class="col-md-4 mb-2"><strong>Trabajo / Puesto:</strong> {{ trim(($externalCustomer->tit_work ?: '') . ' ' . ($externalCustomer->position ? "({$externalCustomer->position})" : '')) ?: '-' }}</div>
                        @if ($externalCustomer->notes)
                            <div class="col-12 mt-1"><strong>Notas:</strong> {{ $externalCustomer->notes }}</div>
                        @endif
                        @if ($externalCustomer->isConverted())
                            <div class="col-12 mt-2">
                                <div class="alert alert-success py-2 px-3 mb-0 font-size-13">
                                    <i class="fa-solid fa-circle-check mr-1"></i>
                                    Promovido a Cliente Interno <strong>#{{ $externalCustomer->converted_customer_id }}</strong>
                                    el {{ optional($externalCustomer->converted_at)->format('d/m/Y H:i') }}
                                    por <strong>{{ $externalCustomer->convertedBy?->name ?? 'Sistema' }}</strong>.
                                    Todos sus cobros históricos permanecen vinculados a este registro externo para auditoría en caja y cortes.
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white font-weight-bold text-primary">
                            <i class="fa-solid fa-landmark mr-1"></i>Historial de Pagos de Financieras ({{ $financieraPayments->count() }})
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0 font-size-12">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Recibo</th>
                                            <th>Financiera / Ref</th>
                                            <th>Sucursal</th>
                                            <th class="text-right">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($financieraPayments as $fp)
                                            <tr>
                                                <td>{{ optional($fp->payment_date)->format('d/m/Y H:i') }}</td>
                                                <td><code>{{ $fp->receipt_code }}</code></td>
                                                <td>
                                                    <span class="badge badge-primary">{{ $fp->financiera?->name ?? 'Financiera' }}</span>
                                                    <small class="d-block text-muted">TAG/Ref: {{ $fp->tag_contrato ?: '-' }}</small>
                                                </td>
                                                <td>{{ $fp->branch?->name ?? 'General' }}</td>
                                                <td class="text-right font-weight-bold text-success">${{ number_format((float) $fp->total_amount, 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="text-center text-muted py-3">Sin pagos de financieras vinculados aún.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white font-weight-bold text-dark">
                            <i class="fa-solid fa-cash-register text-success mr-1"></i>Movimientos en Caja y POS ({{ $cashFlows->count() }} caja / {{ $transactions->count() }} POS)
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0 font-size-12">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Referencia</th>
                                            <th>Concepto / Descripción</th>
                                            <th>Método</th>
                                            <th class="text-right">Monto</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($cashFlows as $cf)
                                            <tr>
                                                <td>{{ optional($cf->created_at)->format('d/m/Y H:i') }}</td>
                                                <td>
                                                    <code>{{ $cf->reference ?: '-' }}</code>
                                                    @if ($cf->pos_transaction_id)
                                                        <a href="{{ route('central-pos.receipt', $cf->pos_transaction_id) }}" target="_blank" class="ml-1 text-primary" title="Ver Ticket">
                                                            <i class="fa-solid fa-receipt"></i>
                                                        </a>
                                                    @endif
                                                </td>
                                                <td>{{ $cf->description }}</td>
                                                <td><span class="badge badge-secondary text-uppercase">{{ $cf->payment_method }}</span></td>
                                                <td class="text-right font-weight-bold {{ $cf->type === 'income' ? 'text-success' : 'text-danger' }}">
                                                    {{ $cf->type === 'expense' ? '-' : '' }}${{ number_format((float) $cf->amount, 2) }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="text-center text-muted py-3">Sin movimientos registrados en caja.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

