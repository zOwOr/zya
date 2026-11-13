@if(!empty($expenseDetails) && count($expenseDetails) > 0)
    <div class="cut-section-head">
        <span class="cut-section-num">E</span> Detalle de Egresos / Salidas de Caja ({{ count($expenseDetails) }})
    </div>
    <table class="table table-bordered cut-compact-table mb-2">
        <thead class="bg-light text-uppercase">
            <tr>
                <th style="width: 42px;" class="text-center">Hora</th>
                <th style="width: 155px;">Tipo de Egreso</th>
                <th>Motivo / Detalle</th>
                <th style="width: 78px;">Cajero</th>
                <th style="width: 75px;" class="text-right">Importe ($)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($expenseDetails as $expItem)
                <tr>
                    <td class="text-center text-muted font-weight-bold">{{ $expItem['time'] }}</td>
                    <td class="font-weight-bold text-danger">{{ $expItem['expense_type'] }}</td>
                    <td>{{ $expItem['reason'] }}</td>
                    <td class="text-muted">{{ $expItem['user_name'] }}</td>
                    <td class="text-right font-weight-bold text-danger">${{ number_format($expItem['amount'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot class="bg-light font-weight-bold">
            <tr>
                <th colspan="4" class="text-right">TOTAL EGRESOS EN EFECTIVO:</th>
                <th class="text-right text-danger">${{ number_format(collect($expenseDetails)->sum('amount'), 2) }}</th>
            </tr>
        </tfoot>
    </table>
@endif

