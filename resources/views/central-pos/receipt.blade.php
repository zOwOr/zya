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
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            width: 70mm;
            max-width: 70mm;
            margin: 0 auto;
            padding: 6px 2px;
            font-size: 12px;
            font-weight: 600;
            line-height: 1.25;
            color: #000 !important;
            background: #fff;
            word-break: break-word;
            overflow-wrap: break-word;
        }
        body.paper-58mm {
            width: 48mm;
            max-width: 48mm;
            font-size: 11px;
            padding: 4px 1px;
        }
        .center { text-align: center; }
        .bold { font-weight: 800 !important; }
        .divider { border-top: 1px dashed #000; margin: 6px 0; }
        .row-flex { display: flex; justify-content: space-between; align-items: baseline; margin: 2px 0; }
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
            padding: 6px 12px;
            font-size: 11px;
            font-weight: bold;
            border: 1px solid #94a3b8;
            border-radius: 4px;
            background: #fff;
            cursor: pointer;
            text-decoration: none;
            color: #1e293b;
            display: inline-flex;
            align-items: center;
        }
        .btn-primary-tool {
            background: #2563eb;
            color: #fff !important;
            border-color: #1d4ed8;
        }
        .btn-success-tool {
            background: #16a34a;
            color: #fff !important;
            border-color: #15803d;
        }
        @media print {
            .no-print { display: none !important; }
            body {
                width: 70mm !important;
                max-width: 70mm !important;
                margin: 0 auto !important;
                padding: 0 !important;
            }
            body.paper-58mm {
                width: 48mm !important;
                max-width: 48mm !important;
                padding: 0 !important;
            }
        }
    </style>
</head>
<body id="ticketBody">

    <div class="no-print toolbar">
        <a href="{{ route('central-pos.index') }}" class="btn-tool" style="text-decoration: none; color: #1e293b; display: inline-flex; align-items: center;">
            ⬅️ Volver al Punto de Venta
        </a>
        <a href="{{ route('dashboard') }}" class="btn-tool" style="text-decoration: none; color: #1e293b; display: inline-flex; align-items: center;">
            🏠 Menú Principal
        </a>
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

    @php
        $financieraList = [];
        if ($transaction->relationLoaded('financieraPayments') && $transaction->financieraPayments->isNotEmpty()) {
            foreach ($transaction->financieraPayments as $fp) {
                if (!empty($fp->financiera?->name)) {
                    $financieraList[] = $fp->financiera->name;
                }
            }
        }
        if (is_array($transaction->items_payload)) {
            foreach ($transaction->items_payload as $item) {
                if (!empty($item['financiera_name'])) {
                    $financieraList[] = $item['financiera_name'];
                } elseif (!empty($item['title'])) {
                    foreach (['PayJoy', 'Credimás', 'Credimas', 'Creditienda', 'Contado'] as $known) {
                        if (stripos($item['title'], $known) !== false) {
                            $financieraList[] = $known;
                        }
                    }
                }
            }
        }
        $financieraList = array_values(array_unique(array_filter($financieraList)));
    @endphp

    <div class="row-flex">
        <span>Folio:</span>
        <span class="bold">{{ $transaction->transaction_code }}</span>
    </div>
    <div class="row-flex">
        <span>Fecha:</span>
        <span class="bold">{{ $transaction->created_at ? $transaction->created_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i') }}</span>
    </div>
    <div class="row-flex">
        <span class="bold">Cliente:</span>
        <span class="bold" style="font-size: 13px;">{{ strtoupper($transaction->customer_name ?: 'Cliente Mostrador') }}</span>
    </div>
    @if (!empty($financieraList))
        <div class="row-flex">
            <span class="bold">Financiera:</span>
            <span class="bold" style="font-size: 13px;">{{ strtoupper(implode(', ', $financieraList)) }}</span>
        </div>
    @endif
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
        <div style="margin-bottom: 5px;">
            <div class="bold">{{ $qty }}x {{ $item['title'] ?? 'Concepto' }}</div>
            @if (!empty($item['subtitle']))
                <div style="font-size: 10px; color: #222;">{{ $item['subtitle'] }}</div>
            @endif
            @if (($item['section'] ?? '') !== 'prestamo_apertura')
                <div class="row-flex">
                    <span class="bold">Importe:</span>
                    <span class="bold" style="font-size: 13px;">${{ number_format($amt, 2) }}</span>
                </div>
            @endif
            @if ($comm > 0)
                <div class="row-flex">
                    <span class="bold">Comisión:</span>
                    <span class="bold">${{ number_format($comm, 2) }}</span>
                </div>
            @endif
            {{-- Privacidad garantizada: NO se imprime el saldo pendiente en abonos de préstamos --}}
        </div>
    @endforeach

    <div class="divider"></div>
    <div class="row-flex">
        <span class="bold">Subtotal:</span>
        <span class="bold">${{ number_format($transaction->subtotal, 2) }}</span>
    </div>
    @if ($transaction->commission_total > 0)
        <div class="row-flex">
            <span class="bold">Comisión:</span>
            <span class="bold">${{ number_format($transaction->commission_total, 2) }}</span>
        </div>
    @endif
    @php
        $paidToday = round((float) $transaction->paid_amount - (float) $transaction->change_amount, 2);
        $dueAmount = max(0, round((float) $transaction->total_amount - $paidToday, 2));
    @endphp

    <div class="row-flex bold" style="font-size: 15px; margin: 5px 0; border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 4px 0;">
        <span class="bold">TOTAL DE LA NOTA:</span>
        <span class="bold" style="font-size: 16px;">${{ number_format($transaction->total_amount, 2) }} MXN</span>
    </div>

    @if ($dueAmount > 0)
        <div class="row-flex bold" style="font-size: 13px; color: #16a34a; margin: 2px 0;">
            <span class="bold">ABONADO HOY:</span>
            <span class="bold">${{ number_format($paidToday, 2) }} MXN</span>
        </div>
        <div class="row-flex bold" style="font-size: 13px; color: #dc2626; border: 1px dashed #dc2626; padding: 3px 5px; margin: 4px 0; background: #fff5f5;">
            <span class="bold">SALDO PENDIENTE:</span>
            <span class="bold">${{ number_format($dueAmount, 2) }} MXN</span>
        </div>
        <div class="center bold" style="font-size: 10px; color: #dc2626; margin-bottom: 4px;">
            *** NOTA CON SALDO PENDIENTE POR LIQUIDAR ***
        </div>
    @else
        <div class="row-flex bold" style="font-size: 12px; color: #16a34a;">
            <span class="bold">ESTADO:</span>
            <span class="bold">TOTALMENTE PAGADO</span>
        </div>
    @endif
    <div class="divider"></div>

    <div class="row-flex">
        <span>Método de Pago:</span>
        <span class="bold">{{ strtoupper($transaction->payment_method) }}</span>
    </div>
    @if ($transaction->payment_method === 'mixto')
        @if ($transaction->cash_amount > 0)
            <div class="row-flex"><span>• Efectivo:</span><span class="bold">${{ number_format($transaction->cash_amount, 2) }}</span></div>
        @endif
        @if ($transaction->card_amount > 0)
            <div class="row-flex"><span>• Tarjeta:</span><span class="bold">${{ number_format($transaction->card_amount, 2) }}</span></div>
        @endif
        @if ($transaction->transfer_amount > 0)
            <div class="row-flex"><span>• Transferencia:</span><span class="bold">${{ number_format($transaction->transfer_amount, 2) }}</span></div>
        @endif
    @else
        @if ($transaction->payment_method === 'efectivo' && $transaction->paid_amount > 0)
            <div class="row-flex">
                <span>Efectivo Recibido:</span>
                <span class="bold">${{ number_format($transaction->paid_amount, 2) }}</span>
            </div>
        @endif
    @endif

    @if ($transaction->change_amount > 0)
        <div class="row-flex">
            <span class="bold">Cambio Entregado:</span>
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

    <script>
        window.ZyaPosHardware = {
            KICK_BYTES: new Uint8Array([0x1B, 0x70, 0x00, 0x19, 0xFA, 0x1B, 0x70, 0x01, 0x19, 0xFA, 0x10, 0x14, 0x01, 0x00, 0x05, 0x07, 0x0A]),
            autoOpenUsbDrawer: async function () {
                if (!('serial' in navigator)) return false;
                try {
                    const ports = await navigator.serial.getPorts();
                    if (ports && ports.length > 0) {
                        const port = ports[0];
                        await port.open({ baudRate: 9600 });
                        try { await port.setSignals({ dataTerminalReady: true, requestToSend: true }); } catch (e) {}
                        const writer = port.writable.getWriter();
                        await writer.write(this.KICK_BYTES);
                        writer.releaseLock();
                        await new Promise(r => setTimeout(r, 120));
                        try { await port.setSignals({ dataTerminalReady: false, requestToSend: false }); } catch (e) {}
                        await port.close();
                        return true;
                    }
                } catch (e) {}
                return false;
            }
        };

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

            // Disparar diálogo de impresión solo si se carga como ventana independiente
            if (window.self === window.top) {
                window.print();
            }
        });
    </script>
</body>
</html>
