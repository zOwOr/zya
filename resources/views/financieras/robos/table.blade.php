<div class="table-responsive">
    <table class="table table-striped table-hover mb-0">
        <thead class="bg-white text-uppercase font-size-12">
            <tr>
                <th>Código Reporte</th>
                <th>IMEI / Equipo</th>
                <th>Sucursal</th>
                <th>Fecha Incidente</th>
                <th>Acta / Denuncia</th>
                <th>Cliente Afectado</th>
                <th>Estado</th>
                <th>Venta Vinculada</th>
                <th class="text-right">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($theftReports as $report)
                <tr>
                    <td>
                        <a href="{{ route('financieras.robos.show', $report->id) }}" class="font-weight-bold text-danger font-size-15">
                            {{ $report->report_code }}
                        </a>
                    </td>
                    <td>
                        <span class="font-weight-bold text-dark">{{ $report->device?->imei ?? 'N/A' }}</span>
                        <br>
                        <small class="text-muted">{{ $report->device?->brand?->name }} {{ $report->device?->model }}</small>
                    </td>
                    <td>{{ $report->branch?->name ?? 'N/A' }}</td>
                    <td>{{ $report->incident_date ? $report->incident_date->format('d/m/Y') : '-' }}</td>
                    <td>
                        <span class="badge badge-light border font-size-12">
                            {{ $report->police_report_number ?: 'Sin acta' }}
                        </span>
                    </td>
                    <td>
                        @if ($report->sale)
                            <div class="font-weight-bold">{{ $report->sale->customer_name ?: 'Sin registrar' }}</div>
                            <small class="text-muted">{{ $report->sale->customer_phone }}</small>
                        @else
                            <span class="text-muted font-size-12">Sin venta vinculada</span>
                        @endif
                    </td>
                    <td>
                        @switch($report->status)
                            @case('reportado')
                                <span class="badge badge-danger">Reportado</span>
                                @break
                            @case('en_investigacion')
                                <span class="badge badge-warning text-white">En Investigación</span>
                                @break
                            @case('recuperado')
                                <span class="badge badge-success">Recuperado</span>
                                @break
                            @case('cerrado')
                                <span class="badge badge-secondary">Cerrado</span>
                                @break
                        @endswitch
                    </td>
                    <td>
                        @if ($report->sale)
                            <a href="{{ route('financieras.ventas.show', $report->sale->id) }}" class="btn btn-xs btn-outline-primary" title="Ver detalle de la venta">
                                <i class="fa-solid fa-file-invoice-dollar mr-1"></i>{{ $report->sale->sale_code }}
                            </a>
                        @else
                            <span class="text-muted font-size-12">-</span>
                        @endif
                    </td>
                    <td class="text-right">
                        <div class="d-inline-flex">
                            <a href="{{ route('financieras.robos.show', $report->id) }}" class="btn btn-sm btn-info mr-1" title="Ver Detalle">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="{{ route('financieras.inventario.history', $report->device_id) }}" class="btn btn-sm btn-outline-secondary mr-1" title="Historial IMEI">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                            </a>
                            @if (auth()->user()->can('financieras.robos.delete'))
                                <form action="{{ route('financieras.robos.destroy', $report->id) }}" method="POST" class="d-inline"
                                    onsubmit="return confirm('¿Seguro de eliminar este reporte de robo?');">
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
                        <i class="fa-solid fa-shield-halved fa-3x mb-2 text-secondary"></i>
                        <p class="mb-0">No se encontraron reportes de robo registrados.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-between align-items-center mt-3 px-3">
    <div class="text-muted font-size-12">
        Mostrando {{ $theftReports->firstItem() ?? 0 }} a {{ $theftReports->lastItem() ?? 0 }} de {{ $theftReports->total() }} reportes
    </div>
    <div>
        {{ $theftReports->links() }}
    </div>
</div>
