<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket {{ $payment->payment_code }}</title>
    <style>
        @page {
            size: 80mm auto;
            margin: 0mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Courier New', Courier, 'Lucida Console', Monaco, monospace;
            width: 76mm;
            max-width: 76mm;
            margin: 0 auto;
            padding: 8px 4px;
            font-size: 12px;
            line-height: 1.25;
            color: #000;
            background: #fff;
        }
        body.paper-58mm {
            width: 50mm;
            max-width: 50mm;
            font-size: 11px;
            padding: 4px 2px;
        }
        .center { text-align: center; }
        .bold { font-weight: 700; }
        .divider { border-top: 1px dashed #000; margin: 6px 0; }
        .row-flex { display: flex; justify-content: space-between; margin: 2px 0; }
        .toolbar {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 8px;
            margin-bottom: 12px;
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            justify-content: center;
        }
        .btn-tool {
            padding: 5px 10px;
            font-size: 11px;
            font-weight: bold;
            border: 1px solid #94a3b8;
            border-radius: 4px;
            background: #fff;
            cursor: pointer;
        }
        .btn-primary-tool {
            background: #2563eb;
            color: #fff;
            border-color: #1d4ed8;
        }
        .btn-success-tool {
            background: #16a34a;
            color: #fff;
            border-color: #15803d;
        }
        @media print {
            .no-print { display: none !important; }
            body {
                width: 76mm !important;
                max-width: 76mm !important;
                margin: 0 auto !important;
                padding: 1mm 2mm !important;
            }
            body.paper-58mm {
                width: 48mm !important;
                max-width: 48mm !important;
                padding: 1mm !important;
            }
        }
    </style>
</head>
<body id="ticketBody">
    <div class="no-print toolbar">
        <button class="btn-tool btn-primary-tool" onclick="window.print()">
            🖨️ Imprimir Ticket
        </button>
        <button class="btn-tool" onclick="togglePaperWidth()">
            📄 <span id="paperLabel">Modo: 80mm</span>
        </button>
    </div>

    {{-- Secuencia ESC/POS invisible en caso de modo texto --}}
    <span style="display:none;">&#27;&#112;&#0;&#25;&#250;</span>

    <div class="center bold" style="font-size: 15px; letter-spacing: 1px;">ZYA</div>
    <div class="center" style="font-size: 11px;">MÓDULO DE PRÉSTAMOS</div>
    <div class="center bold">Sucursal: {{ $payment->loan?->branch?->name ?? 'Matriz' }}</div>
    <div class="center" style="font-size: 11px;">COMPROBANTE DE PAGO DE PRÉSTAMO</div>
    <div class="divider"></div>

    <div class="row-flex">
        <span>Folio Recibo:</span>
        <span class="bold">{{ $payment->payment_code }}</span>
    </div>
    <div class="row-flex">
        <span>Préstamo:</span>
        <span class="bold">{{ $payment->loan?->loan_code }}</span>
    </div>
    <div class="row-flex">
        <span>Cliente:</span>
        <span class="bold">{{ $payment->loan?->customer?->tit_name }}</span>
    </div>
    @if ($payment->scheduled_date)
        <div class="row-flex">
            <span>Día Programado:</span>
            <span>{{ $payment->scheduled_date->format('d/m/Y') }}</span>
        </div>
    @endif
    <div class="row-flex">
        <span>Día de Pago:</span>
        <span>{{ $payment->paid_at ? $payment->paid_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i') }}</span>
    </div>
    <div class="divider"></div>

    <div class="row-flex">
        <span>Concepto:</span>
        <span class="bold">
            @if ($payment->payment_type === 'comision_apertura')
                Comisión por Apertura
            @elseif ($payment->payment_type === 'cierre_manual')
                Liquidación / Cierre
            @else
                Abono {{ $payment->installment ? 'Pago #' . $payment->installment->installment_number : '' }}
            @endif
        </span>
    </div>
    <div class="row-flex">
        <span>Método de Pago:</span>
        <span class="bold">{{ strtoupper($payment->payment_method) }}</span>
    </div>

    @if ($payment->payment_method === 'mixto' && is_array($payment->payment_breakdown))
        @foreach ($payment->payment_breakdown as $k => $v)
            @if ((float)$v > 0)
                <div class="row-flex" style="font-size: 11px;">
                    <span>• {{ ucfirst($k) }}:</span>
                    <span>${{ number_format((float)$v, 2) }}</span>
                </div>
            @endif
        @endforeach
    @endif

    <div class="divider"></div>
    {{-- REGLA DE NEGOCIO ESTRICTA: En el ticket NO mostrar el saldo pendiente del préstamo por privacidad --}}
    <div class="row-flex bold" style="font-size: 14px; margin: 4px 0;">
        <span>IMPORTE PAGADO:</span>
        <span>${{ number_format($payment->amount, 2) }} MXN</span>
    </div>
    <div class="divider"></div>

    <div class="center" style="font-size: 11px; margin-top: 6px;">
        Atendido por: {{ $payment->user?->name ?? 'Cajero' }}<br>
        ¡Gracias por su pago puntual!<br>
        Conserve este comprobante.
    </div>

    {{-- Margen inferior para guillotina automática de miniprinter --}}
    <div style="height: 18mm;"></div>

    <script src="{{ asset('assets/js/pos-hardware.js') }}"></script>
    <script>
        let is58mm = localStorage.getItem('zya_ticket_width') === '58';
        function applyPaperWidth() {
            const body = document.getElementById('ticketBody');
            const lbl = document.getElementById('paperLabel');
            if (is58mm) {
                body.classList.add('paper-58mm');
                lbl.textContent = 'Modo: 58mm';
            } else {
                body.classList.remove('paper-58mm');
                lbl.textContent = 'Modo: 80mm';
            }
        }
        function togglePaperWidth() {
            is58mm = !is58mm;
            localStorage.setItem('zya_ticket_width', is58mm ? '58' : '80');
            applyPaperWidth();
        }

        window.addEventListener('load', async function () {
            applyPaperWidth();

            @php
                $isCashPayment = in_array($payment->payment_method, ['efectivo', 'mixto']);
            @endphp
            @if($isCashPayment)
                // Disparo AUTOMÁTICO del cajón USB al registrar abono en efectivo
                if (window.ZyaPosHardware) {
                    try {
                        await ZyaPosHardware.autoOpenUsbDrawer();
                    } catch (e) {}
                }
            @endif

            // Disparar diálogo de impresión
            window.print();
        });
    </script>
</body>
</html>
