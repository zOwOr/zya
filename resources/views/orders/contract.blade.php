<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>ZYA CELULARES Y ACCESORIOS</title>
    <link type="text/css" rel="stylesheet" href="{{ asset('assets/invoice/css/bootstrap.min.css') }}">
    <link type="text/css" rel="stylesheet" href="{{ asset('assets/invoice/css/style.css') }}">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
        }

        .signature {
            margin-top: 80px;
            display: flex;
            justify-content: space-around;
        }

        [contenteditable="true"] {
            border-bottom: 1px dashed #000;
            min-width: 40px;
            display: inline-block;
            padding: 0 2px;
        }

        @media print {
            .no-print {
                display: none;
            }

            [contenteditable="true"] {
                border: none;
            }

            /* Opcional: quitar borde al imprimir */
        }
    </style>
</head>

<body>
    <div class="no-print d-print-none" style="background: #0f172a; padding: 12px 20px; border-bottom: 2px solid #1e293b; margin-bottom: 20px;">
        <div class="container d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <a href="{{ route('central-pos.index') }}" class="btn btn-sm btn-outline-light font-weight-bold" style="margin-right: 8px;">
                    ⬅️ Volver al Punto de Venta
                </a>
                <a href="{{ route('order.pendingDue') }}" class="btn btn-sm btn-outline-light font-weight-bold" style="margin-right: 8px;">
                    📋 Notas con Saldo Pendiente
                </a>
                <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-light font-weight-bold">
                    🏠 Menú Principal
                </a>
            </div>
            <div>
                <button onclick="window.print()" class="btn btn-sm btn-primary font-weight-bold">
                    🖨️ Imprimir Contrato
                </button>
            </div>
        </div>
    </div>
    <div class="container mt-2">
        <!-- ENCABEZADO -->
        <div class="d-flex justify-content-between mb-3">
            <img src="{{ asset('assets/images/logo.png') }}" alt="logo" style="max-height: 80px;">
            <div class="text-end text-uppercase">
                <p>VENDEDOR: <strong>ESMERALDA ZAPATA</strong></p>
                <p>ALLENDE N.L. FECHA:<strong contenteditable="true" class="bg-warning">{{ now()->format('d F Y') }}</strong> </p>
            </div>
        </div>

        <!-- TABLA DE PRÉSTAMO -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th><span contenteditable="true" class="bg-warning">PRESTAMO CONFIRMADO</span></th>
                    <th>COMISIÓN POR APERTURA</th>
                    <th>ABONO</th>
                    <th>PLAZO</th>
                    <th>RECARGO POR PAGO TARDÍO</th>
                    <th>DÍAS DE PAGO</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span contenteditable="true" class="bg-warning">${{ number_format($loan_amount, 2) }}</span></td>
                    <td><span contenteditable="true" class="bg-warning">$1000</span></td>
                    <td><span contenteditable="true" class="bg-warning">${{ number_format($installment_amount, 2) }}</span></td>
                    <td><span contenteditable="true"  class="bg-warning">{{ $installments }}</span></td>
                    <td><span contenteditable="true"  class="bg-warning">$200.00</span> </td>
                    <td><span contenteditable="true" class="bg-warning">12 Y 26 DE CADA MES</span></td>
                </tr>
            </tbody>
        </table>

        <!-- DATOS DEL CLIENTE -->
        <p><strong>CLIENTE/TITULAR:</strong> {{ $order->customer->tit_name }}</p>
        <p><strong>DIRECCIÓN:</strong> {{ $order->customer->tit_address }}</p>
        <p><strong>CELULAR:</strong> {{ $order->customer->tit_phone  }}</p>
        <p><strong>CORREO:</strong> {{ $order->customer->tit_email ?? 'SN' }}</p>
        <p><strong>FACEBOOK:</strong> {{ $order->customer->tit_facebook }}</p>
        <p><strong>LUGAR DE TRABAJO:</strong> {{ $order->customer->tit_work ?? 'ND' }}</p>

        <!-- AVAL -->
        <p><strong>AVAL:</strong> {{ $order->customer->aval_name }}</p>
        <p>DIRECCIÓN: {{ $order->customer->aval_address }}</p>
        <p>CELULAR: {{ $order->customer->aval_phone }}</p>

        <!-- REFERENCIAS -->
        <p><strong>REFERENCIA #1:</strong> {{ $order->customer->ref1_name }}</p>
        <p>DIRECCIÓN: {{ $order->customer->ref1_address }}</p>
        <p>CELULAR: {{ $order->customer->ref1_phone }}</p>

        <p><strong>REFERENCIA #2:</strong> {{ $order->customer->ref2_name }}</p>
        <p>DIRECCIÓN: {{ $order->customer->ref2_address }}</p>
        <p>CELULAR: {{ $order->customer->ref2_phone }}</p>

        <p><strong>REFERENCIA #3:</strong> {{ $order->customer->ref3_name }}</p>
        <p>DIRECCIÓN: {{ $order->customer->ref3_address }}</p>
        <p>CELULAR: {{ $order->customer->ref3_phone }}</p>

        <!-- FIRMAS -->
        <div class="signature mt-5 d-flex justify-content-between text-uppercase">
            <div class="text-center text-uppercase">
                ___________________________<br>
                Cliente<br>{{ $order->customer->tit_name }}
            </div>
            <div class="text-center text-uppercase">
                ___________________________<br>
                Aval Solidario<br>{{ $order->customer->aval_name }}
            </div>
            <div class="text-center text-uppercase">
                ___________________________<br>
                Vendedor<br> ESMERALDA ZAPATA
            </div>
        </div>
    </div>
    <div class="container mt-4">
        <div class="text-center mb-4">
            <img src="{{ asset('assets/images/logo.png') }}" alt="logo" style="max-height: 80px;">
            <h2 class="mt-2">Cláusulas de <span contenteditable="true" class="bg-warning">Préstamo</span></h2>
        </div>

        <h4>Cláusulas:</h4>
        <ol>
            <li>LOS DÍAS DE PAGO SON LOS DÍAS:
                <strong contenteditable="true"  class="bg-warning">12 Y 26 DE CADA MES</strong>
            </li>
            <li>SE PAGARÁ MULTA DE
                <strong contenteditable="true"  class="bg-warning">$200.00</strong> EN CASO DE RETRASO
            </li>
            <li>EL <span contenteditable="true"  class="bg-warning">PRÉSTAMO</span> FUE POR:
                <strong contenteditable="true"  class="bg-warning">${{ number_format($loan_amount, 2) }}</strong>
            </li>
            <li>A PAGARSE EN
                <strong contenteditable="true"  class="bg-warning">{{ $installments }}</strong> PAGOS DE
                <strong contenteditable="true"  class="bg-warning">${{ number_format($installment_amount, 2) }}</strong>
            </li>
        </ol>

        <p>
            TITULAR:
            </br>
            Yo <strong>{{ $order->customer->tit_name }}</strong> estoy recibiendo un
            <span contenteditable="true" class="bg-warning">préstamo</span> de
            <strong contenteditable="true" class="bg-warning">${{ number_format($loan_amount, 2) }}</strong> que me
            comprometo a pagar en <strong contenteditable="true" class="bg-warning">{{ $installments }}</strong> pagos
            por el monto de <strong contenteditable="true"
                class="bg-warning">${{ number_format($installment_amount, 2) }}</strong> cada uno todos los días <strong
                contenteditable="true" class="bg-warning">12 Y 26 DE CADA MES</strong>
            En caso de no cumplir con lo indicado, pagaré <strong contenteditable="true"
                class="bg-warning">$200.00</strong> extra mas mi pago por cada día de atraso.
            </br>

            Por lo cual estoy autorizando que mi información se comparta en redes sociales en caso de no cumplir con lo
            acordado.
            </br>
            </br>
            AVAL:
            Yo <strong>{{ $order->customer->aval_name }}</strong> me comprometo a pagar si mi titular <strong>{{ $order->customer->tit_name }}</strong>
            no paga el saldo de esta operación.
        </p>

        <div class="signature">
            <div class="text-center text-uppercase">
                ___________________________<br>
                Cliente<br>{{ $order->customer->tit_name }}
            </div>
            <div class="text-center text-uppercase">
                ___________________________<br>
                Aval<br>{{ $order->customer->aval_name }}
            </div>
            <div class="text-center text-uppercase">
                ___________________________<br>
                Vendedor<br> Esmeralda Zapata
            </div>
        </div>

        <div class="text-center mt-4 no-print d-print-none">
            <a href="{{ route('central-pos.index') }}" class="btn btn-outline-secondary mr-2">
                ⬅️ Punto de Venta
            </a>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary mr-2">
                🏠 Menú Principal
            </a>
            <button class="btn btn-primary" onclick="window.print()">🖨️ Imprimir Contrato</button>
        </div>
    </div>
</body>

</html>
