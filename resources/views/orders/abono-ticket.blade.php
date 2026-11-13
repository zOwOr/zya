<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Abono - {{ $order->invoice_no }}</title>
    <style>
        @page {
            size: portrait;
            margin: 0mm;
        }
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color: #000 !important;
        }
        body {
            font-family: Arial, "Helvetica Neue", Helvetica, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            width: 70mm;
            max-width: 70mm;
            margin: 0 auto;
            padding: 4px 6mm 4px 2mm;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.25;
            color: #000 !important;
            background: #fff;
            word-break: break-word;
            overflow-wrap: break-word;
            text-rendering: geometricPrecision;
            -webkit-font-smoothing: antialiased;
        }
        body.paper-58mm {
            width: 48mm;
            max-width: 48mm;
            font-size: 11px;
            padding: 3px 4mm 3px 1mm;
        }
        .center { text-align: center; }
        .bold, b, strong { font-weight: 900 !important; }
        .divider { border-top: 1.5px dashed #000 !important; margin: 5px 0; }
        .row-flex {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin: 2px 0;
            gap: 4px;
            break-inside: avoid;
            page-break-inside: avoid;
        }
        .row-flex > :first-child {
            flex-shrink: 0;
            white-space: nowrap;
        }
        .row-flex > :last-child {
            text-align: right;
            word-break: break-word;
        }
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
            -webkit-appearance: none;
            appearance: none;
            touch-action: manipulation;
        }
        .btn-primary-tool {
            background: #2563eb;
            color: #fff;
            border-color: #1d4ed8;
        }
        @media print {
            @page {
                size: portrait;
                margin: 0mm;
            }
            .no-print { display: none !important; }
            html {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
            }
            body {
                width: 70mm !important;
                max-width: 70mm !important;
                margin: 0 !important;
                padding: 0 6mm 0 2mm !important;
                box-sizing: border-box !important;
                background: #fff !important;
                height: auto !important;
                min-height: 0 !important;
                overflow-x: hidden !important;
                overflow-y: visible !important;
            }
            body.paper-58mm {
                width: 48mm !important;
                max-width: 48mm !important;
                margin: 0 !important;
                padding: 0 4mm 0 1mm !important;
                box-sizing: border-box !important;
                overflow-x: hidden !important;
                overflow-y: visible !important;
            }
        }
    </style>
</head>
@php
    $initialPaper = request('paper') === '58' ? 'paper-58mm' : '';
@endphp
<body id="ticketBody" class="{{ $initialPaper }}">

    <div class="no-print toolbar">
        <a href="{{ route('order.pendingDue') }}" class="btn-tool" style="text-decoration: none; color: #1e293b; display: inline-flex; align-items: center;">
            ⬅️ Volver a Notas Pendientes
        </a>
        <a href="{{ route('central-pos.index') }}" class="btn-tool" style="text-decoration: none; color: #1e293b; display: inline-flex; align-items: center;">
            🛒 Punto de Venta
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

    <div class="center bold" style="font-size: 16px; letter-spacing: 1px;">ZYA CELULARES</div>
    <div class="center" style="font-size: 11px;">Tecnología y Accesorios</div>
    <div class="center bold" style="font-size: 12px; margin-top: 2px;">
        Sucursal: {{ $branch->name ?? 'Matriz' }}
    </div>
    @if (!empty($branch->address))
        <div class="center" style="font-size: 10px;">{{ $branch->address }}</div>
    @endif
    @if (!empty($branch->phone))
        <div class="center" style="font-size: 10px;">Tel: {{ $branch->phone }}</div>
    @endif

    <div class="divider"></div>
    <div class="center bold" style="font-size: 13px; text-transform: uppercase;">
        COMPROBANTE DE ABONO
    </div>
    <div class="center" style="font-size: 11px;">
        Abono #{{ str_pad($history->id, 5, '0', STR_PAD_LEFT) }}
    </div>
    <div class="divider"></div>

    <div class="row-flex">
        <span>Folio Nota Venta:</span>
        <span class="bold">{{ $order->invoice_no }}</span>
    </div>
    <div class="row-flex">
        <span>Fecha Venta:</span>
        <span>{{ \Carbon\Carbon::parse($order->order_date)->format('d/m/Y') }}</span>
    </div>
    <div class="row-flex">
        <span>Fecha Abono:</span>
        <span>{{ $history->created_at ? $history->created_at->format('d/m/Y H:i') : date('d/m/Y H:i') }}</span>
    </div>
    <div class="row-flex">
        <span>Cliente:</span>
        <span class="bold">{{ $order->customer?->tit_name ?? 'Cliente Mostrador' }}</span>
    </div>
    @if ($order->customer?->phone)
        <div class="row-flex">
            <span>Teléfono:</span>
            <span>{{ $order->customer->phone }}</span>
        </div>
    @endif
    <div class="row-flex">
        <span>Cajero:</span>
        <span>{{ $history->user?->name ?? auth()->user()->name }}</span>
    </div>

    <div class="divider"></div>
    <div class="bold" style="margin-bottom: 3px;">RESUMEN DE CUENTA:</div>

    @php
        $prevDue = (float) $order->due + (float) $history->amount;
    @endphp

    <div class="row-flex">
        <span class="bold">Total Original de la Nota:</span>
        <span class="bold">${{ number_format($order->total, 2) }}</span>
    </div>
    <div class="row-flex">
        <span>Saldo Anterior:</span>
        <span class="bold">${{ number_format($prevDue, 2) }}</span>
    </div>
    <div class="divider"></div>

    <div class="row-flex bold" style="font-size: 15px; margin: 4px 0;">
        <span class="bold">MONTO ABONADO:</span>
        <span class="bold" style="font-size: 16px;">${{ number_format($history->amount, 2) }} MXN</span>
    </div>

    @php
        $methodLabel = match($history->method) {
            'HandCash' => 'Efectivo',
            'Cheque' => 'Transferencia',
            'Due' => 'Tarjeta',
            default => ucfirst($history->method)
        };
    @endphp
    <div class="row-flex">
        <span>Método de Pago:</span>
        <span class="bold">{{ strtoupper($methodLabel) }}</span>
    </div>

    @if ($history->cash_amount > 0 && ($history->card_amount > 0 || $history->transfer_amount > 0))
        <div class="row-flex"><span>• Efectivo:</span><span>${{ number_format($history->cash_amount, 2) }}</span></div>
    @endif
    @if ($history->card_amount > 0 && ($history->cash_amount > 0 || $history->transfer_amount > 0))
        <div class="row-flex"><span>• Tarjeta:</span><span>${{ number_format($history->card_amount, 2) }}</span></div>
    @endif
    @if ($history->transfer_amount > 0 && ($history->cash_amount > 0 || $history->card_amount > 0))
        <div class="row-flex"><span>• Transferencia:</span><span>${{ number_format($history->transfer_amount, 2) }}</span></div>
    @endif

    @if (!empty($history->note))
        <div class="row-flex" style="font-size: 11px;">
            <span>Nota:</span>
            <span>{{ $history->note }}</span>
        </div>
    @endif

    <div class="divider"></div>

    @if ((float) $order->due <= 0)
        <div class="center bold" style="font-size: 13px; color: #16a34a; border: 1px solid #16a34a; padding: 4px; margin: 6px 0; background: #f0fdf4;">
            🎉 ¡NOTA COMPLETAMENTE LIQUIDADA! 🎉
        </div>
        <div class="row-flex bold" style="font-size: 12px;">
            <span>SALDO PENDIENTE:</span>
            <span>$0.00 MXN</span>
        </div>
    @else
        <div class="row-flex bold" style="font-size: 13px; color: #dc2626; border: 1px dashed #dc2626; padding: 4px; margin: 4px 0; background: #fff5f5;">
            <span>NUEVO SALDO PENDIENTE:</span>
            <span>${{ number_format($order->due, 2) }} MXN</span>
        </div>
        <div class="center" style="font-size: 10px; color: #dc2626;">
            Favor de conservar su ticket para futuros abonos.
        </div>
    @endif

    <div class="divider"></div>
    <div class="center" style="font-size: 11px; margin-top: 6px;">
        ¡Gracias por su pago!<br>
        ZYA Celulares — Siempre contigo.
    </div>

    {{-- Margen inferior calibrado para el corte de guillotina térmica sin sobrante de papel --}}
    <div style="height: 8mm; line-height: 8mm; overflow: hidden; page-break-inside: avoid;">&nbsp;</div>

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

        function safeStorageGet(key) {
            try { return window.localStorage ? window.localStorage.getItem(key) : null; } catch (e) { return null; }
        }
        function safeStorageSet(key, val) {
            try { if (window.localStorage) window.localStorage.setItem(key, val); } catch (e) {}
        }

        let is58mm = (function () {
            try {
                const urlPaper = new URLSearchParams(window.location.search).get('paper');
                if (urlPaper === '58') return true;
                if (urlPaper === '80') return false;
            } catch (e) {}
            return safeStorageGet('zya_ticket_width') === '58';
        })();
        function applyPaperWidth() {
            const body = document.getElementById('ticketBody');
            const lbl = document.getElementById('paperLabel');
            if (is58mm) {
                body.classList.add('paper-58mm');
                if (lbl) lbl.textContent = 'Modo: 58mm';
            } else {
                body.classList.remove('paper-58mm');
                if (lbl) lbl.textContent = 'Modo: 80mm';
            }
        }
        function togglePaperWidth() {
            is58mm = !is58mm;
            safeStorageSet('zya_ticket_width', is58mm ? '58' : '80');
            applyPaperWidth();
        }

        window.addEventListener('load', async function () {
            applyPaperWidth();

            let skipDrawer = false;
            let isPreview = false;
            try {
                const q = new URLSearchParams(window.location.search);
                skipDrawer = q.get('skip_drawer') === '1';
                isPreview = q.get('preview') === '1';
            } catch (e) {}

            @php
                $isCash = ($history->method === 'HandCash' || $history->cash_amount > 0);
            @endphp
            @if($isCash)
                if (!skipDrawer && !isPreview && window.ZyaPosHardware) {
                    try {
                        await ZyaPosHardware.autoOpenUsbDrawer();
                    } catch (e) {}
                }
            @endif

            if (window.self === window.top && !isPreview) {
                try {
                    if (document.fonts && document.fonts.ready) {
                        await Promise.race([document.fonts.ready, new Promise(r => setTimeout(r, 250))]);
                    }
                } catch (e) {}
                await new Promise(r => {
                    const raf = window.requestAnimationFrame || (cb => setTimeout(cb, 16));
                    raf(() => raf(r));
                });
                try {
                    window.focus();
                    window.print();
                } catch (e) {}
            }
        });
    </script>
</body>
</html>

