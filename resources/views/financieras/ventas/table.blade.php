<div class="table-responsive">
    <table class="table table-striped table-hover mb-0">
        <thead class="bg-white text-uppercase font-size-12">
            <tr>
                <th>Código</th>
                <th>Fecha</th>
                <th>IMEI / Equipo</th>
                <th>Sucursal</th>
                <th>Financiera</th>
                <th>Cliente</th>
                <th>Vendedor</th>
                <th>Precio / Enganche</th>
                <th>Estado</th>
                <th class="text-right">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($sales as $sale)
                <tr>
                    <td>
                        <a href="{{ route('financieras.ventas.show', $sale->id) }}" class="font-weight-bold text-primary">
                            {{ $sale->sale_code }}
                        </a>
                    </td>
                    <td>{{ $sale->sale_date ? $sale->sale_date->format('d/m/Y') : '-' }}</td>
                    <td>
                        <span class="font-weight-bold text-dark">{{ $sale->device?->imei ?? 'N/A' }}</span>
                        <br>
                        <small class="text-muted">{{ $sale->device?->brand?->name }} {{ $sale->device?->model }}</small>
                    </td>
                    <td>{{ $sale->branch?->name ?? 'N/A' }}</td>
                    <td>
                        <span class="badge badge-light border font-size-12">
                            {{ $sale->financiera?->name ?? 'Directo' }}
                        </span>
                    </td>
                    <td>
                        <div class="font-weight-bold">{{ $sale->customer_name ?: 'Sin registrar' }}</div>
                        <small class="text-muted">{{ $sale->customer_phone }}</small>
                    </td>
                    <td>{{ $sale->seller_display_name }}</td>
                    <td>
                        <span class="font-weight-bold text-success">${{ number_format($sale->price, 2) }}</span>
                        <br>
                        <small class="text-muted">Eng: ${{ number_format($sale->down_payment, 2) }}</small>
                    </td>
                    <td>
                        @if ($sale->status === 'activa')
                            <span class="badge badge-success">Activa</span>
                        @else
                            <span class="badge badge-danger">Cancelada</span>
                        @endif
                    </td>
                    <td class="text-right">
                        <div class="d-inline-flex">
                            <a href="{{ route('financieras.ventas.show', $sale->id) }}" class="btn btn-sm btn-info mr-1" title="Ver Detalle">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="{{ route('financieras.ventas.pdf', $sale->id) }}" class="btn btn-sm btn-secondary mr-1" title="Descargar PDF" target="_blank">
                                <i class="fa-solid fa-file-pdf"></i>
                            </a>
                            @if ($sale->status === 'activa' && auth()->user()->can('financieras.ventas.edit'))
                                <a href="{{ route('financieras.ventas.edit', $sale->id) }}" class="btn btn-sm btn-warning mr-1" title="Editar">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                            @endif
                            @if ($sale->status === 'activa' && auth()->user()->can('financieras.ventas.delete'))
                                <button type="button" class="btn btn-sm btn-outline-danger" title="Cancelar Venta"
                                    onclick="openCancelModal('{{ $sale->id }}', '{{ $sale->sale_code }}', '{{ $sale->device?->imei }}')">
                                    <i class="fa-solid fa-ban"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center py-4 text-muted">
                        <i class="fa-solid fa-inbox fa-3x mb-2 text-secondary"></i>
                        <p class="mb-0">No se encontraron ventas registradas con los filtros seleccionados.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-between align-items-center mt-3 px-3">
    <div class="text-muted font-size-12">
        Mostrando {{ $sales->firstItem() ?? 0 }} a {{ $sales->lastItem() ?? 0 }} de {{ $sales->total() }} ventas
    </div>
    <div>
        {{ $sales->links() }}
    </div>
</div>
