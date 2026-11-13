@extends('dashboard.body.main')

@section('container')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            @if (session()->has('success'))
                <div class="alert text-white bg-success" role="alert">
                    <div class="iq-alert-text">{{ session('success') }}</div>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <i class="ri-close-line"></i>
                    </button>
                </div>
            @endif
            <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="mb-3">Lista de pedidos pendientes</h4>
                </div>
                <div>
                    @if(auth()->user()->isSuperAdmin() || auth()->user()->can('orders.export_pdf'))
                        <a href="{{ route('order.exportPdf', array_merge(request()->query(), ['order_status' => 'pending'])) }}" class="btn btn-danger add-list mr-2"><i class="las la-file-pdf mr-1"></i>Exportar PDF</a>
                    @endif
                    @if(auth()->user()->isSuperAdmin() || auth()->user()->can('orders.export_excel'))
                        <a href="{{ route('order.exportExcel', array_merge(request()->query(), ['order_status' => 'pending'])) }}" class="btn btn-success add-list mr-2"><i class="las la-file-excel mr-1"></i>Exportar Excel</a>
                    @endif
                    <a href="{{ route('order.pendingOrders') }}" class="btn btn-danger add-list"><i class="fa-solid fa-trash mr-3"></i>Limpiar Busqueda</a>
                </div>
            </div>
        </div>

        <div class="col-lg-12">
            @php
                $ordFilterType = $dateFilter['filter_type'] ?? 'todos';
                $ordCanAllDates = $dateFilter['can_all_dates'] ?? false;
                $ordMinDate = $dateFilter['min_allowed_date'] ?? \Carbon\Carbon::today()->subDays(2)->toDateString();
                $ordSingleDate = $dateFilter['date'] ?? date('Y-m-d');
                $ordStartDate = $dateFilter['start_date'] ?? $ordSingleDate;
                $ordEndDate = $dateFilter['end_date'] ?? $ordSingleDate;
            @endphp
            @if(!empty($dateFilter['was_restricted']))
                <div class="alert alert-warning py-2 px-3 small mb-2">
                    <i class="las la-exclamation-triangle mr-1"></i>
                    Tu rol solo permite consultar o exportar órdenes de hasta <strong>2 días de anterioridad</strong>
                    (desde el <strong>{{ \Carbon\Carbon::parse($ordMinDate)->format('d/m/Y') }}</strong>).
                </div>
            @endif
            <form action="{{ route('order.pendingOrders') }}" method="get">
                <input type="hidden" name="order_status" value="pending">
                <div class="d-flex flex-wrap align-items-end mb-2" style="gap: 8px;">
                    <div>
                        <label class="small font-weight-bold mb-1 d-block">Modo Fecha:</label>
                        <select name="filter_type" id="ordPendFilterType" class="form-control form-control-sm">
                            <option value="dia" {{ $ordFilterType === 'dia' ? 'selected' : '' }}>Día en específico</option>
                            <option value="rango" {{ $ordFilterType === 'rango' ? 'selected' : '' }}>Entre fechas</option>
                            @if($ordCanAllDates)
                                <option value="todos" {{ $ordFilterType === 'todos' ? 'selected' : '' }}>Todos los registros</option>
                            @endif
                        </select>
                    </div>
                    <div id="ordPendDiaBox" style="{{ $ordFilterType === 'dia' ? '' : 'display:none;' }}">
                        <label class="small font-weight-bold mb-1 d-block">Fecha:</label>
                        <input type="date" name="date" value="{{ $ordSingleDate }}" @if(!$ordCanAllDates) min="{{ $ordMinDate }}" @endif class="form-control form-control-sm" {{ $ordFilterType === 'dia' ? '' : 'disabled' }}>
                    </div>
                    <div id="ordPendRangoBox" style="{{ $ordFilterType === 'rango' ? '' : 'display:none;' }}">
                        <label class="small font-weight-bold mb-1 d-block">Entre Fechas:</label>
                        <div class="d-flex align-items-center" style="gap: 4px;">
                            <input type="date" name="start_date" value="{{ $ordStartDate }}" @if(!$ordCanAllDates) min="{{ $ordMinDate }}" @endif class="form-control form-control-sm" {{ $ordFilterType === 'rango' ? '' : 'disabled' }}>
                            <span class="small text-muted">a</span>
                            <input type="date" name="end_date" value="{{ $ordEndDate }}" @if(!$ordCanAllDates) min="{{ $ordMinDate }}" @endif class="form-control form-control-sm" {{ $ordFilterType === 'rango' ? '' : 'disabled' }}>
                        </div>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary btn-sm"><i class="las la-filter"></i> Aplicar Fecha</button>
                    </div>
                    @if(!$ordCanAllDates)
                        <small class="text-muted align-self-center"><i class="las la-lock"></i> Máx. 2 días atrás ({{ \Carbon\Carbon::parse($ordMinDate)->format('d/m/Y') }})</small>
                    @endif
                </div>
                <script>
                (function() {
                    var sel = document.getElementById('ordPendFilterType');
                    if (!sel) return;
                    var dia = document.getElementById('ordPendDiaBox');
                    var rango = document.getElementById('ordPendRangoBox');
                    function toggle(el, active) {
                        if (!el) return;
                        el.style.display = active ? '' : 'none';
                        var inputs = el.querySelectorAll('input');
                        for (var i = 0; i < inputs.length; i++) inputs[i].disabled = !active;
                    }
                    function sync() {
                        toggle(dia, sel.value === 'dia');
                        toggle(rango, sel.value === 'rango');
                    }
                    sel.addEventListener('change', sync);
                    sync();
                })();
                </script>
                <div class="d-flex flex-wrap align-items-center justify-content-between">
                    <div class="form-group row">
                        <label for="row" class="col-sm-3 align-self-center">Row:</label>
                        <div class="col-sm-9">
                            <select class="form-control" name="row" onchange="this.form.submit()">
                                <option value="10" @if(request('row') == '10')selected="selected"@endif>10</option>
                                <option value="25" @if(request('row') == '25')selected="selected"@endif>25</option>
                                <option value="50" @if(request('row') == '50')selected="selected"@endif>50</option>
                                <option value="100" @if(request('row') == '100')selected="selected"@endif>100</option>
                                    <option value="100000" @if(request('row') == '100000') selected="selected" @endif>Todos</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="control-label col-sm-3 align-self-center" for="search">Buscar:</label>
                        <div class="col-sm-8">
                            <div class="input-group">
                                <input type="text" id="search" class="form-control" name="search" placeholder="Buscar" value="{{ request('search') }}">
                                <div class="input-group-append">
                                    <button type="submit" class="input-group-text bg-primary"><i class="fa-solid fa-magnifying-glass font-size-20"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-lg-12">
            <div class="table-responsive rounded mb-3">
                <table class="table mb-0 datatable-export" data-can-export="{{ auth()->user()?->can('orders.export') ? 'true' : 'false' }}">
                    <thead class="bg-white text-uppercase">
                        <tr class="ligth ligth-data">
                            <th>No.</th>
                            <th>Nota de Venta No</th>
                            <th>@sortablelink('customer.name', 'Nombre')</th>
                            <th>@sortablelink('order_date', 'Fecha')</th>
                            <th>Metodo de Pago</th>
                            <th>@sortablelink('total')</th>
                            <th>Status</th>
                            <th>Accion</th>
                        </tr>
                    </thead>
                    <tbody class="ligth-body">
                        @foreach ($orders as $order)
                        <tr>
                            <td>{{ (($orders->currentPage() * 10) - 10) + $loop->iteration  }}</td>
                            <td>{{ $order->invoice_no }}</td>
                            <td>{{ $order->customer ? $order->customer->tit_name : 'El cliente ha sido eliminado' }}</td>
                            <td>{{ $order->order_date }}</td>
                            <td>{{ $order->payment_status }}</td>
                            <td>{{ $order->total }}</td>
                            <td>
                                <span class="badge badge-danger">{{ $order->order_status }}</span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center list-action">
                                    <a class="btn btn-info mr-2" data-toggle="tooltip" data-placement="top" title="" data-original-title="Detalles"
                                        href="{{ route('order.orderDetails', $order->id) }}">Detalles
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $orders->links() }}
        </div>
    </div>
    <!-- Page end  -->
</div>

@endsection

