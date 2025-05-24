@extends('dashboard.body.main')

@section('container')
    <style>
        .table-responsive {
            max-height: 400px;
            overflow-x: auto;
            overflow-y: auto;
            position: relative;
        }

        .table thead th {
            position: sticky;
            top: 0;
            background-color: white;
            z-index: 10;
            border-bottom: 2px solid #dee2e6;
        }

        .sticky-column {
            position: sticky;
            left: 0;
            background-color: white;
            z-index: 5;
            border-right: 2px solid #dee2e6;
        }

        .sticky-column.sticky-header {
            z-index: 15;
        }

        .input-green {
            background-color: #d4edda !important;
        }

        .input-yellow {
            background-color: #fff3cd !important;
        }

        .input-white {
            background-color: #ffffff !important;
        }
    </style>

    <div class="container">
        <h2>Detalle de Tanda</h2>

        <p><strong>Descripción:</strong> {{ $tanda->description }}</p>
        <p><strong>Monto total:</strong> ${{ number_format($tanda->total_amount, 2) }}</p>
        <p><strong>Periodo:</strong> {{ ucfirst($tanda->payment_period) }}</p>
        <p><strong>Monto por periodo:</strong> ${{ number_format($tanda->payment_amount, 2) }}</p>

        <h4>Clientes Participantes</h4>

        <div class="table-responsive">
            <table class="table table-bordered" style="min-width: max-content;">
                <thead>
                    <tr>
                        <th class="sticky-column sticky-header bg-white text-center" style="left: 0;">
                            Periodo <br>(Vence)
                        </th>
                        @foreach ($tanda->clients->sortBy('pivot.position') as $client)
                            <th class="sticky-header bg-white text-center">
                                #{{ $client->pivot->position }}<br>
                                {{ $client->tit_name }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tanda->periods as $period)
                        <tr>
                            <td class="sticky-column bg-white text-nowrap text-center fw-bold" style="left: 0;">
                                Periodo {{ $period->period_number }}<br>
                                ({{ $period->due_date }})
                            </td>
                            @foreach ($tanda->clients->sortBy('pivot.position') as $client)
                                @php
                                    $payments = json_decode($client->pivot->payments ?? '{}', true);
                                    $paymentValue = $payments[$period->period_number] ?? '';
                                @endphp
                                <td>
                                    <input type="number" class="form-control payment-input text-end fw-semibold"
                                        data-client="{{ $client->id }}" data-period="{{ $period->period_number }}"
                                        value="{{ $paymentValue }}" min="0" step="0.01" style="min-width: 80px;">
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <button id="savePayments" class="btn btn-primary mt-3">Guardar Pagos</button>
        <a href="{{ route('tandas.index') }}" class="btn btn-secondary mt-3">Volver</a>
    </div>
@endsection
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
   const paymentAmount = parseFloat({{ $tanda->payment_amount }});

    function updateInputColor(input) {
        const value = parseFloat($(input).val());

        $(input).removeClass('input-green input-yellow input-white');

        if ($(input).val() === '') {
            $(input).addClass('input-white');
        } else if (value === paymentAmount) {
            $(input).addClass('input-green');
        } else {
            $(input).addClass('input-yellow');
        }
    }

    $(document).ready(function() {
        // Aplica colores al cargar la página
        $('.payment-input').each(function() {
            updateInputColor(this);
        });

        // Aplica colores al cambiar valores
        $('.payment-input').on('input', function() {
            updateInputColor(this);
        });

        $('#savePayments').click(function() {
            let paymentsData = [];

            $('.payment-input').each(function() {
                paymentsData.push({
                    client_id: $(this).data('client'),
                    period: $(this).data('period'),
                    amount: $(this).val()
                });
            });

            $.ajax({
                url: "{{ route('tandas.payments.update', $tanda->id) }}",
                method: 'POST',
                data: {
                    payments: paymentsData,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    alert(response.message);
                },
                error: function() {
                    alert('Error al guardar pagos.');
                }
            });
        });
    });
</script>
</script>
