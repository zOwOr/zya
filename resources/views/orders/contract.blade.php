<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Contrato de Préstamo</title>
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
    <div class="container mt-4">
        <div class="text-center mb-4">
            <img src="{{ asset('assets/images/logo.png') }}" alt="logo" style="max-height: 80px;">
            <h2 class="mt-2">Contrato de <span contenteditable="true">Préstamo</span></h2>
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

        <div class="text-center mt-4 no-print">
            <button class="btn btn-primary" onclick="window.print()">🖨️ Imprimir Contrato</button>
        </div>
    </div>
</body>

</html>
