<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket {{ $transaction->transaction_code }}</title>
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

    {{-- Secuencia ESC/POS invisible en caso de driver con modo texto --}}
    <span style="display:none;">&#27;&#112;&#0;&#25;&#250;</span>

    <div class="center bold" style="font-size: 15px; letter-spacing: 1px;">ZYA</div>
    <div class="center" style="font-size: 11px;">CELULARES, ACCESORIOS Y FINANCIERA</div>
    <div class="center bold">Sucursal: {{ $transaction->branch?->name ?? 'Matriz' }}</div>
    <div class="center" style="font-size: 11px;">TICKET UNIFICADO DE PAGO / VENTA</div>
    <div class="divider"></div>

    <div class="row-flex">
        <span>Folio:</span>
        <span class="bold">{{ $transaction->transaction_code }}</span>
    </div>
    <div class="row-flex">
        <span>Fecha:</span>
        <span>{{ $transaction->created_at ? $transaction->created_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i') }}</span>
    </div>
    <div class="row-flex">
        <span>Cliente:</span>
        <span class="bold">{{ $transaction->customer_name ?: 'Cliente Mostrador' }}</span>
    </div>
    <div class="row-flex">
        <span>Cajero:</span>
        <span>{{ $transaction->user?->name ?? 'Sistema' }}</span>
    </div>
    <div class="divider"></div>

    <div class="bold" style="margin-bottom: 3px; font-size: 11px;">CONCEPTOS DEL MOVIMIENTO:</div>
    @foreach ($transaction->items_payload ?? [] as $item)
        @php
            $qty = max(1, (int) ($item['quantity'] ?? 1));
            $amt = ($item['section'] ?? '') === 'prestamo_apertura' ? 0 : ((float) ($item['amount'] ?? 0) * $qty);
            $comm = (float) ($item['commission'] ?? 0);
        @endphp
        <div style="margin-bottom: 4px;">
            <div class="bold">{{ $qty }}x {{ $item['title'] ?? 'Concepto' }}</div>
            @if ($amt > 0)
                <div class="row-flex">
                    <span>Importe:</span>
                    <span>${{ number_format($amt, 2) }}</span>
                </div>
            @endif
            @if ($comm > 0)
                <div class="row-flex">
                    <span>Comisión:</span>
                    <span>${{ number_format($comm, 2) }}</span>
                </div>
            @endif
            {{-- Privacidad garantizada: NO se imprime el saldo pendiente en abonos de préstamos --}}
        </div>
    @endforeach

    <div class="divider"></div>
    <div class="row-flex">
        <span>Subtotal:</span>
        <span>${{ number_format($transaction->subtotal, 2) }}</span>
    </div>
    @if ($transaction->commission_total > 0)
        <div class="row-flex">
            <span>Comisión:</span>
            <span>${{ number_format($transaction->commission_total, 2) }}</span>
        </div>
    @endif
    <div class="row-flex bold" style="font-size: 14px; margin: 4px 0;">
        <span>TOTAL PAGADO:</span>
        <span>${{ number_format($transaction->total_amount, 2) }} MXN</span>
    </div>
    <div class="divider"></div>

    <div class="row-flex">
        <span>Método de Pago:</span>
        <span class="bold">{{ strtoupper($transaction->payment_method) }}</span>
    </div>
    @if ($transaction->payment_method === 'mixto')
        @if ($transaction->cash_amount > 0)
            <div class="row-flex"><span>• Efectivo:</span><span>${{ number_format($transaction->cash_amount, 2) }}</span></div>
        @endif
        @if ($transaction->card_amount > 0)
            <div class="row-flex"><span>• Tarjeta:</span><span>${{ number_format($transaction->card_amount, 2) }}</span></div>
        @endif
        @if ($transaction->transfer_amount > 0)
            <div class="row-flex"><span>• Transferencia:</span><span>${{ number_format($transaction->transfer_amount, 2) }}</span></div>
        @endif
    @endif

    @if ($transaction->change_amount > 0)
        <div class="row-flex">
            <span>Cambio Entregado:</span>
            <span class="bold">${{ number_format($transaction->change_amount, 2) }}</span>
        </div>
    @endif

    <div class="divider"></div>
    <div class="center" style="font-size: 11px; margin-top: 6px;">
        ¡Gracias por su preferencia en ZYA!<br>
        Conserve este comprobante para cualquier aclaración.
    </div>

    {{-- Margen inferior para que la guillotina de la impresora térmica no corte el texto --}}
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
                $isCash = ($transaction->payment_method === 'efectivo' || $transaction->cash_amount > 0 || (bool) $transaction->drawer_opened);
            @endphp
            @if($isCash)
                // Disparo AUTOMÁTICO del cajón físico USB en ventas y cobros
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
