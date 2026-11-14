<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket {{ $payment->payment_code }}</title>
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
        .btn-success-tool {
            background: #16a34a;
            color: #fff;
            border-color: #15803d;
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
        <a href="{{ route('loans.index') }}" class="btn-tool" style="text-decoration: none; color: #1e293b; display: inline-flex; align-items: center;">
            ⬅️ Volver a Préstamos
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
    <div class="row-flex bold" style="font-size: 15px; margin: 4px 0; border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 4px 0;">
        <span class="bold">IMPORTE PAGADO:</span>
        <span class="bold" style="font-size: 16px;">${{ number_format($payment->amount, 2) }} MXN</span>
    </div>
    <div class="divider"></div>

    <div class="center" style="font-size: 11px; margin-top: 6px;">
        Atendido por: {{ $payment->user?->name ?? 'Cajero' }}<br>
        ¡Gracias por su pago puntual!<br>
        Conserve este comprobante.
    </div>

    {{-- Margen inferior calibrado para el corte de guillotina térmica sin sobrante de papel --}}
    <div style="height: 8mm; line-height: 8mm; overflow: hidden; page-break-inside: avoid;">&nbsp;</div>

    <script src="{{ asset('assets/js/pos-hardware.js') }}?v=20261113"></script>
    <script>
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
                $isCashPayment = in_array($payment->payment_method, ['efectivo', 'mixto']);
            @endphp
            @if($isCashPayment)
                // Disparo AUTOMÁTICO del cajón USB al registrar abono en efectivo
                if (!skipDrawer && !isPreview && window.ZyaPosHardware) {
                    try {
                        await ZyaPosHardware.autoOpenUsbDrawer();
                    } catch (e) {}
                }
            @endif

            // Disparar diálogo de impresión solo si la ventana es independiente
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
