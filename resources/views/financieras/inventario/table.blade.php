@php
    $isVendidoOrTodos = in_array(request('status'), ['todos', 'vendido']);
@endphp
<div class="table-responsive">
    <table class="table table-striped table-hover mb-0">
        <thead class="bg-white text-uppercase font-size-12">
            <tr>
                @if (auth()->user()->can('financieras.inventario.delete'))
                    <th style="width: 38px;" class="text-center align-middle">
                        <input type="checkbox" id="selectAllDevices" class="cursor-pointer" title="Seleccionar todos los visibles">
                    </th>
                @endif
                <th>Fecha Llegada</th>
                <th>Proveedor</th>
                <th>Ubicación</th>
                <th>Marca</th>
                <th>Modelo</th>
                <th>IMEI</th>
                <th>Color</th>
                <th>Capacidad</th>
                @if($isVendidoOrTodos)
                    <th>Fecha de Venta</th>
                    <th>Device ID / TAG</th>
                    <th>Observaciones</th>
                @endif
                <th class="text-right">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($devices as $device)
                @php
                    $sale = $device->activeSale ?? $device->latestSale;
                    $saleDate = $sale && $sale->sale_date ? ($sale->sale_date instanceof \Carbon\Carbon ? $sale->sale_date->format('d/m/Y') : date('d/m/Y', strtotime($sale->sale_date))) : '';
                    $contract = $sale ? ($sale->tag_contrato ?: $sale->sale_code) : '';
                    $obs = [];
                    if (!empty($device->notes)) { $obs[] = $device->notes; }
                    if ($sale && $sale->status === 'cancelada' && !empty($sale->cancellation_reason)) {
                        $obs[] = 'CANCELACION: ' . $sale->cancellation_reason;
                    } elseif ($sale && !empty($sale->cancellation_reason)) {
                        $obs[] = $sale->cancellation_reason;
                    }
                    $observaciones = mb_strtoupper(implode(' | ', $obs));
                    $isCancelled = ($sale && $sale->status === 'cancelada') || stripos($observaciones, 'CANCEL') !== false;
                @endphp
                <tr style="{{ $isCancelled && $isVendidoOrTodos ? 'background-color: #fce8e6;' : '' }}">
                    @if (auth()->user()->can('financieras.inventario.delete'))
                        <td class="text-center align-middle">
                            @if(!$device->sales()->where('status', 'activa')->exists())
                                <input type="checkbox" class="device-checkbox cursor-pointer" value="{{ $device->id }}" data-imei="{{ $device->imei }}">
                            @else
                                <span class="text-muted" title="No se puede eliminar: tiene venta activa vinculada">
                                    <i class="fa-solid fa-lock text-secondary font-size-11"></i>
                                </span>
                            @endif
                        </td>
                    @endif
                    <td class="font-size-13 text-muted">
                        <span class="font-weight-bold text-dark">{{ $device->created_at->format('d/m/Y') }}</span>
                    </td>
                    <td>
                        @if ($device->supplier)
                            <span class="badge badge-light border font-size-12" title="{{ $device->supplier->contact ? 'Contacto: ' . $device->supplier->contact : '' }}">
                                <i class="fa-solid fa-truck-field mr-1 text-secondary"></i>{{ $device->supplier->name }}
                            </span>
                        @else
                            <span class="text-muted font-size-12">-</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-light border font-size-12">
                            <i class="fa-solid fa-store mr-1 text-primary"></i>{{ $device->branch?->name ?? 'N/A' }}
                        </span>
                    </td>
                    <td class="font-weight-bold">
                        {{ $device->brand?->name ?? 'Sin Marca' }}
                    </td>
                    <td>
                        <span>{{ $device->model }}</span>
                    </td>
                    <td>
                        <a href="{{ route('financieras.inventario.history', $device->id) }}" class="font-weight-bold font-size-14 text-dark" title="Ver historial de movimientos">
                            {{ $device->imei }}
                        </a>
                    </td>
                    <td>{{ $device->color ?: '-' }}</td>
                    <td>
                        @if ($device->storage)
                            <span class="badge badge-info-light font-weight-bold border px-2 py-1">{{ $device->storage }}</span>
                        @else
                            <span class="text-muted font-size-12">-</span>
                        @endif
                    </td>
                    @if($isVendidoOrTodos)
                        <td>
                            @if($saleDate)
                                <span class="font-weight-bold text-dark font-size-13">{{ $saleDate }}</span>
                            @else
                                <span class="text-muted font-size-12">-</span>
                            @endif
                        </td>
                        <td>
                            @if($contract)
                                @if($sale)
                                    <a href="{{ route('financieras.ventas.show', $sale->id) }}" class="badge badge-primary font-size-12 font-weight-bold" title="Ver venta {{ $sale->sale_code }}">
                                        <i class="fa-solid fa-tag mr-1 font-size-10"></i>{{ $contract }}
                                    </a>
                                @else
                                    <span class="badge badge-primary font-size-12 font-weight-bold">{{ $contract }}</span>
                                @endif
                            @else
                                <span class="text-muted font-size-12">-</span>
                            @endif
                        </td>
                        <td>
                            @if($observaciones)
                                <span class="font-size-11 {{ $isCancelled ? 'text-danger font-weight-bold' : 'text-muted' }}">{{ $observaciones }}</span>
                            @else
                                <span class="text-muted font-size-12">-</span>
                            @endif
                        </td>
                    @endif
                    <td class="text-right">
                        <div class="d-inline-flex">
                            <a href="{{ route('financieras.inventario.show', $device->id) }}" class="btn btn-sm btn-outline-primary mr-1" title="Ver Detalle">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="{{ route('financieras.inventario.history', $device->id) }}" class="btn btn-sm btn-info mr-1" title="Historial Completo">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                            </a>

                            @if (auth()->user()->can('financieras.inventario.transfer') && $device->status !== 'vendido')
                                <button type="button" class="btn btn-sm btn-outline-primary mr-1" title="Transferir a otra sucursal"
                                    onclick="openTransferModal('{{ $device->id }}', '{{ $device->imei }}', '{{ $device->branch_id }}', '{{ $device->branch?->name }}')">
                                    <i class="fa-solid fa-right-left"></i>
                                </button>
                            @endif

                            @if (auth()->user()->can('financieras.inventario.edit'))
                                <a href="{{ route('financieras.inventario.edit', $device->id) }}" class="btn btn-sm btn-warning mr-1" title="Editar Dispositivo">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                            @endif

                            @if (auth()->user()->can('financieras.inventario.delete') && !$device->sales()->where('status', 'activa')->exists())
                                <form action="{{ route('financieras.inventario.destroy', $device->id) }}" method="POST" class="d-inline"
                                    onsubmit="return confirm('¿Seguro que desea eliminar este equipo del inventario?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ (auth()->user()->can('financieras.inventario.delete') ? 1 : 0) + ($isVendidoOrTodos ? 12 : 9) }}" class="text-center py-4 text-muted">
                        <i class="fa-solid fa-boxes-stacked fa-3x mb-2 text-secondary"></i>
                        <p class="mb-0">No se encontraron dispositivos registrados en inventario.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-between align-items-center mt-3 px-3">
    <div class="text-muted font-size-12">
        Mostrando {{ $devices->firstItem() ?? 0 }} a {{ $devices->lastItem() ?? 0 }} de {{ $devices->total() }} dispositivos
    </div>
    <div>
        {{ $devices->links() }}
    </div>
</div>
