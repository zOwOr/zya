<div class="table-responsive">
    <table class="table table-striped table-hover mb-0">
        <thead class="bg-white text-uppercase font-size-12">
            <tr>
                <th>IMEI</th>
                <th>Marca / Modelo</th>
                <th>Color</th>
                <th>Sucursal Actual</th>
                <th>Estado</th>
                <th>Venta Vinculada</th>
                <th>Fecha Registro</th>
                <th class="text-right">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($devices as $device)
                <tr>
                    <td>
                        <a href="{{ route('financieras.inventario.history', $device->id) }}" class="font-weight-bold font-size-15 text-dark" title="Ver historial de movimientos">
                            {{ $device->imei }}
                        </a>
                    </td>
                    <td>
                        <span class="font-weight-bold">{{ $device->brand?->name ?? 'Sin Marca' }}</span>
                        <br>
                        <small class="text-muted">{{ $device->model }}</small>
                    </td>
                    <td>{{ $device->color ?: '-' }}</td>
                    <td>
                        <span class="badge badge-light border font-size-12">
                            <i class="fa-solid fa-store mr-1 text-primary"></i>{{ $device->branch?->name ?? 'N/A' }}
                        </span>
                    </td>
                    <td>
                        @switch($device->status)
                            @case('disponible')
                                <span class="badge badge-success px-2 py-1">Disponible</span>
                                @break
                            @case('vendido')
                                <span class="badge badge-secondary px-2 py-1">Vendido</span>
                                @break
                            @case('en_garantia')
                                <span class="badge badge-warning text-white px-2 py-1">En Garantía</span>
                                @break
                            @case('robado')
                                <span class="badge badge-danger px-2 py-1">Robado</span>
                                @break
                            @default
                                <span class="badge badge-light">{{ $device->status }}</span>
                        @endswitch
                    </td>
                    <td>
                        @if ($device->latestSale)
                            <a href="{{ route('financieras.ventas.show', $device->latestSale->id) }}" class="btn btn-xs btn-outline-primary" title="Ver detalle de la venta">
                                <i class="fa-solid fa-file-invoice-dollar mr-1"></i>{{ $device->latestSale->sale_code }}
                            </a>
                        @else
                            <span class="text-muted font-size-12">Sin venta</span>
                        @endif
                    </td>
                    <td class="font-size-13 text-muted">
                        {{ $device->created_at->format('d/m/Y') }}
                    </td>
                    <td class="text-right">
                        <div class="d-inline-flex">
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
                    <td colspan="8" class="text-center py-4 text-muted">
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
