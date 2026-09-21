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
                <th>Estado</th>
                <th class="text-right">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($devices as $device)
                <tr>
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
                        <br>
                        <small class="text-muted">{{ $device->created_at->format('H:i') }}</small>
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
                    <td colspan="{{ auth()->user()->can('financieras.inventario.delete') ? 11 : 10 }}" class="text-center py-4 text-muted">
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
