<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket {{ $transaction->transaction_code }}</title>
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
        body.paper-letter {
            width: 100%;
            max-width: 580px;
            margin: 14px auto;
            padding: 20px 26px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 13.5px;
            line-height: 1.45;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
        }
        body.capture-clean .toolbar {
            display: none !important;
        }
        .capture-restore-chip {
            display: none;
            position: fixed;
            top: 8px;
            right: 8px;
            z-index: 9999;
            background: rgba(15, 23, 42, 0.78);
            color: #fff !important;
            border: none;
            border-radius: 20px;
            padding: 5px 12px;
            font-size: 11px;
            font-weight: bold;
            cursor: pointer;
        }
        body.capture-clean .capture-restore-chip {
            display: inline-block;
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
                overflow: hidden !important;
            }
            body.paper-58mm {
                width: 48mm !important;
                max-width: 48mm !important;
                margin: 0 !important;
                padding: 0 4mm 0 1mm !important;
                box-sizing: border-box !important;
                overflow: hidden !important;
            }
            body.paper-letter {
                width: 185mm !important;
                max-width: 185mm !important;
                margin: 8mm auto !important;
                padding: 10mm 14mm !important;
                border: none !important;
                box-shadow: none !important;
                font-size: 13px !important;
                line-height: 1.45 !important;
                overflow: visible !important;
            }
        }
    </style>
    <style id="dynamicPageRule"></style>
</head>
<body id="ticketBody">

    <button type="button" class="no-print capture-restore-chip" onclick="toggleCaptureMode(false)">
        👁️ Mostrar controles
    </button>

    <div class="no-print toolbar">
        <a href="{{ route('central-pos.index') }}" class="btn-tool" style="text-decoration: none; color: #1e293b; display: inline-flex; align-items: center;">
            ⬅️ Volver al Punto de Venta
        </a>
        <a href="{{ route('dashboard') }}" class="btn-tool" style="text-decoration: none; color: #1e293b; display: inline-flex; align-items: center;">
            🏠 Menú Principal
        </a>
        <button type="button" class="btn-tool btn-primary-tool" onclick="window.print()">
            🖨️ Imprimir
        </button>
        <button type="button" class="btn-tool" id="btnMode80" onclick="setPaperMode('80')">
            🧾 XP 80mm
        </button>
        <button type="button" class="btn-tool" id="btnMode58" onclick="setPaperMode('58')">
            🧾 XP 58mm
        </button>
        <button type="button" class="btn-tool" id="btnModeLetter" onclick="setPaperMode('letter')">
            📄 Epson Carta
        </button>
        <button type="button" class="btn-tool btn-success-tool" onclick="toggleCaptureMode(true)" title="Ocultar botones para tomar captura y enviar por WhatsApp">
            📸 Captura WhatsApp
        </button>
        <span style="display:none;" id="paperLabel">Modo: 80mm</span>
    </div>

    {{-- Secuencia ESC/POS invisible en caso de driver con modo texto --}}
    <span style="display:none;">&#27;&#112;&#0;&#25;&#250;</span>

    <div class="center bold" style="font-size: 15px; letter-spacing: 1px;">ZYA</div>
    <div class="center" style="font-size: 11px;">CELULARES, ACCESORIOS Y REPARACIONES</div>
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
                if (($item['section'] ?? '') === 'gift') {
                    continue;
                }
                if (!empty($item['financiera_name'])) {
                    $financieraList[] = $item['financiera_name'];
                } elseif (!empty($item['title'])) {
                    foreach (['PayJoy', 'Credimás', 'Credimas', 'Creditienda', 'Contado'] as $known) {
                        if (stripos($item['title'], $known) !== false) {
                            $financieraList[] = ($known === 'Credimas') ? 'Credimás' : $known;
                            break;
                        }
                    }
                }
            }
        }
        $uniqueFinancieras = [];
        foreach ($financieraList as $f) {
            $cleaned = trim((string) $f);
            if ($cleaned !== '') {
                $upper = mb_strtoupper($cleaned, 'UTF-8');
                if ($upper === 'CREDIMAS') {
                    $upper = 'CREDIMÁS';
                }
                $uniqueFinancieras[$upper] = $upper;
            }
        }
        $financieraList = array_values($uniqueFinancieras);
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
        <span class="bold">{{ $transaction->external_customer_id ? 'Cliente Externo:' : 'Cliente:' }}</span>
        <span class="bold" style="font-size: 13px;">{{ strtoupper($transaction->customer_name ?: 'Cliente Mostrador') }}</span>
    </div>
    @if (!empty($financieraList))
        <div class="row-flex">
            <span class="bold">Financiera:</span>
            <span class="bold" style="font-size: 13px;">{{ implode(', ', $financieraList) }}</span>
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
            $sec = $item['section'] ?? '';
            $qty = max(1, (int) ($item['quantity'] ?? 1));
            $amt = in_array($sec, ['prestamo_apertura', 'gift'], true) ? 0 : ((float) ($item['amount'] ?? 0) * $qty);
            $comm = (float) ($item['commission'] ?? 0);
            $itemSubtitle = $sec === 'gift'
                ? \App\Models\PosTransaction::sanitizeGiftSubtitle($item['subtitle'] ?? null)
                : ($item['subtitle'] ?? null);
        @endphp
        <div style="margin-bottom: 5px;">
            <div class="bold">{{ $qty }}x {{ $item['title'] ?? 'Concepto' }}@if($sec === 'gift') <span style="font-size:10px;">(REGALO)</span>@endif</div>
            @if (!empty($itemSubtitle))
                <div style="font-size: 10px; color: #222;">{{ $itemSubtitle }}</div>
            @endif
            @if ($sec !== 'prestamo_apertura')
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

    <div class="row-flex bold" style="font-size: 13.5px; margin: 5px 0; border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 4px 0;">
        <span class="bold">TOTAL DE LA NOTA:</span>
        <span class="bold" style="font-size: 15px;">${{ number_format($transaction->total_amount, 2) }} MXN</span>
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

        let paperMode = (function () {
            const urlPaper = new URLSearchParams(window.location.search).get('paper');
            if (urlPaper && ['80', '58', 'letter'].includes(urlPaper)) {
                return urlPaper;
            }
            const stored = localStorage.getItem('zya_ticket_paper_mode');
            if (stored && ['80', '58', 'letter'].includes(stored)) {
                return stored;
            }
            return localStorage.getItem('zya_ticket_width') === '58' ? '58' : '80';
        })();

        function applyPaperWidth() {
            const body = document.getElementById('ticketBody');
            const lbl = document.getElementById('paperLabel');
            const pageRuleEl = document.getElementById('dynamicPageRule');
            body.classList.remove('paper-58mm', 'paper-letter');

            if (paperMode === '58') {
                body.classList.add('paper-58mm');
                if (lbl) lbl.textContent = 'Modo: 58mm';
                if (pageRuleEl) pageRuleEl.textContent = '';
            } else if (paperMode === 'letter') {
                body.classList.add('paper-letter');
                if (lbl) lbl.textContent = 'Modo: Carta (Epson)';
                if (pageRuleEl) pageRuleEl.textContent = '@media print { @page { size: letter portrait; margin: 10mm; } }';
            } else {
                if (lbl) lbl.textContent = 'Modo: 80mm';
                if (pageRuleEl) pageRuleEl.textContent = '';
            }

            ['btnMode80', 'btnMode58', 'btnModeLetter'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.classList.remove('btn-primary-tool');
            });
            const activeBtnId = paperMode === '58' ? 'btnMode58' : (paperMode === 'letter' ? 'btnModeLetter' : 'btnMode80');
            const activeEl = document.getElementById(activeBtnId);
            if (activeEl) activeEl.classList.add('btn-primary-tool');
        }

        function setPaperMode(mode) {
            if (!['80', '58', 'letter'].includes(mode)) return;
            paperMode = mode;
            localStorage.setItem('zya_ticket_paper_mode', mode);
            localStorage.setItem('zya_ticket_width', mode === '58' ? '58' : '80');
            applyPaperWidth();
        }

        function togglePaperWidth() {
            const order = ['80', '58', 'letter'];
            const next = order[(order.indexOf(paperMode) + 1) % order.length];
            setPaperMode(next);
        }

        function toggleCaptureMode(enable) {
            const body = document.getElementById('ticketBody');
            if (!body) return;
            if (enable) body.classList.add('capture-clean');
            else body.classList.remove('capture-clean');
        }

        window.addEventListener('load', async function () {
            applyPaperWidth();

            const queryParams = new URLSearchParams(window.location.search);
            const skipDrawerParam = queryParams.get('skip_drawer') === '1';
            const isPreviewMode = queryParams.get('preview') === '1';

            @php
                $isCash = ($transaction->payment_method === 'efectivo' || (float) $transaction->cash_amount > 0);
            @endphp
            @if($isCash)
                // Disparo AUTOMÁTICO del cajón físico USB solo cuando el cobro involucra efectivo (evita doble disparo o disparo en vista previa)
                if (!skipDrawerParam && !isPreviewMode && window.ZyaPosHardware) {
                    try {
                        await ZyaPosHardware.autoOpenUsbDrawer();
                    } catch (e) {}
                }
            @endif

            // Disparar diálogo de impresión solo si la ventana es independiente y no está en vista previa de captura
            if (window.self === window.top && !isPreviewMode) {
                try {
                    window.focus();
                    window.print();
                } catch (e) {
                    console.warn('Auto-print dialog blocked or unavailable:', e);
                }
            }
        });
    </script>
</body>
</html>
