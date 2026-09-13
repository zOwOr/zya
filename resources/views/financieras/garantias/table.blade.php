<div class="table-responsive">
    <table class="table table-striped table-hover mb-0">
        <thead class="bg-white text-uppercase font-size-12">
            <tr>
                <th>Código</th>
                <th>IMEI / Equipo</th>
                <th>Sucursal</th>
                <th>Cliente</th>
                <th>Etapa Actual</th>
                <th>Estado</th>
                <th>Venta Vinculada</th>
                <th>Fecha Ingreso</th>
                <th class="text-right">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($warranties as $warranty)
                <tr>
                    <td>
                        <a href="{{ route('financieras.garantias.show', $warranty->id) }}" class="font-weight-bold text-warning font-size-15">
                            {{ $warranty->warranty_code }}
                        </a>
                    </td>
                    <td>
                        <span class="font-weight-bold text-dark">{{ $warranty->device?->imei ?? 'N/A' }}</span>
                        <br>
                        <small class="text-muted">{{ $warranty->device?->brand?->name }} {{ $warranty->device?->model }}</small>
                    </td>
                    <td>{{ $warranty->branch?->name ?? 'N/A' }}</td>
                    <td>
                        @if ($warranty->sale)
                            <div class="font-weight-bold">{{ $warranty->sale->customer_name ?: 'Sin registrar' }}</div>
                            <small class="text-muted">{{ $warranty->sale->customer_phone }}</small>
                        @else
                            <span class="text-muted font-size-12">Sin venta vinculada</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge text-white px-2 py-1" style="background-color: {{ $warranty->currentStage?->color ?? '#6c757d' }};">
                            {{ $warranty->currentStage?->name ?? 'Etapa Inicial' }}
                        </span>
                    </td>
                    <td>
                        @switch($warranty->status)
                            @case('abierta')
                                <span class="badge badge-info">Abierta</span>
                                @break
                            @case('en_proceso')
                                <span class="badge badge-warning text-white">En Proceso</span>
                                @break
                            @case('resuelta')
                                <span class="badge badge-success">Resuelta</span>
                                @break
                            @case('rechazada')
                                <span class="badge badge-danger">Rechazada</span>
                                @break
                        @endswitch
                    </td>
                    <td>
                        @if ($warranty->sale)
                            <a href="{{ route('financieras.ventas.show', $warranty->sale->id) }}" class="btn btn-xs btn-outline-primary" title="Ver detalle de la venta">
                                <i class="fa-solid fa-file-invoice-dollar mr-1"></i>{{ $warranty->sale->sale_code }}
                            </a>
                        @else
                            <span class="text-muted font-size-12">-</span>
                        @endif
                    </td>
                    <td class="font-size-13 text-muted">
                        {{ $warranty->opened_at ? $warranty->opened_at->format('d/m/Y') : '-' }}
                    </td>
                    <td class="text-right">
                        <div class="d-inline-flex">
                            <a href="{{ route('financieras.garantias.show', $warranty->id) }}" class="btn btn-sm btn-info mr-1" title="Ver Seguimiento">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="{{ route('financieras.inventario.history', $warranty->device_id) }}" class="btn btn-sm btn-outline-secondary mr-1" title="Historial IMEI">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                            </a>
                            @if (auth()->user()->can('financieras.garantias.delete'))
                                <form action="{{ route('financieras.garantias.destroy', $warranty->id) }}" method="POST" class="d-inline"
                                    onsubmit="return confirm('¿Seguro de eliminar este registro de garantía?');">
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
                    <td colspan="9" class="text-center py-4 text-muted">
                        <i class="fa-solid fa-screwdriver-wrench fa-3x mb-2 text-secondary"></i>
                        <p class="mb-0">No se encontraron garantías registradas.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-between align-items-center mt-3 px-3">
    <div class="text-muted font-size-12">
        Mostrando {{ $warranties->firstItem() ?? 0 }} a {{ $warranties->lastItem() ?? 0 }} de {{ $warranties->total() }} garantías
    </div>
    <div>
        {{ $warranties->links() }}
    </div>
</div>
