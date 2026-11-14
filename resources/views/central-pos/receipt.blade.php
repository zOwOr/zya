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
            break-inside: avoid;
            page-break-inside: avoid;
        }
        .concept-block {
            margin-bottom: 5px;
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
            -webkit-appearance: none;
            appearance: none;
            touch-action: manipulation;
        }
        .capture-restore-chip {
            -webkit-appearance: none;
            appearance: none;
            touch-action: manipulation;
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
    @php
        $initialPaperMode = in_array(request('paper'), ['80', '58', 'letter'], true) ? request('paper') : '80';
        $initialBodyClass = $initialPaperMode === '58' ? 'paper-58mm' : ($initialPaperMode === 'letter' ? 'paper-letter' : '');
    @endphp
    <style id="dynamicPageRule">@if($initialPaperMode === 'letter')@page { size: letter portrait; margin: 10mm; }@endif</style>
</head>
<body id="ticketBody" class="{{ $initialBodyClass }}">

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
        <div class="concept-block">
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
        $paidToday = max(0.0, round((float) $transaction->paid_amount - (float) $transaction->change_amount, 2));
        $dueAtSale = max(0.0, round((float) $transaction->total_amount - $paidToday, 2));
        if ($dueAtSale < 0.01) {
            $dueAtSale = 0.0;
        }
        $linkedOrder = $transaction->relationLoaded('order') ? $transaction->order : null;
        $updatedDue = $linkedOrder ? max(0.0, round((float) $linkedOrder->due, 2)) : $dueAtSale;
        if ($updatedDue < 0.01) {
            $updatedDue = 0.0;
        }
        $payRefs = is_array($transaction->payment_references) ? $transaction->payment_references : [];
        $cardRef = trim((string) ($payRefs['card_ref'] ?? ''));
        $transferRef = trim((string) ($payRefs['transfer_ref'] ?? ''));
    @endphp

    <div class="row-flex bold" style="font-size: 13.5px; margin: 5px 0; border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 4px 0;">
        <span class="bold">TOTAL DE LA VENTA:</span>
        <span class="bold" style="font-size: 15px;">${{ number_format($transaction->total_amount, 2) }} MXN</span>
    </div>

    @if ($dueAtSale > 0 || $updatedDue > 0)
        <div class="row-flex bold" style="font-size: 12.5px; color: #16a34a; margin: 2px 0;">
            <span class="bold">PAGO INICIAL / ABONADO HOY:</span>
            <span class="bold">${{ number_format($paidToday, 2) }} MXN</span>
        </div>
        @if ($updatedDue > 0)
            <div class="row-flex bold" style="font-size: 13px; color: #dc2626; border: 1px dashed #dc2626; padding: 3px 5px; margin: 4px 0; background: #fff5f5;">
                <span class="bold">SALDO PENDIENTE ACTUALIZADO:</span>
                <span class="bold">${{ number_format($updatedDue, 2) }} MXN</span>
            </div>
            @if (abs($updatedDue - $dueAtSale) >= 0.01)
                <div class="row-flex" style="font-size: 10.5px; color: #444;">
                    <span>Saldo inicial de esta nota:</span>
                    <span>${{ number_format($dueAtSale, 2) }} MXN</span>
                </div>
            @endif
            <div class="center bold" style="font-size: 10px; color: #dc2626; margin-bottom: 4px;">
                *** NOTA CON SALDO PENDIENTE POR LIQUIDAR ***
            </div>
        @else
            <div class="row-flex bold" style="font-size: 12px; color: #16a34a; margin: 2px 0;">
                <span class="bold">SALDO PENDIENTE ACTUALIZADO:</span>
                <span class="bold">$0.00 MXN (LIQUIDADO)</span>
            </div>
        @endif
    @else
        <div class="row-flex bold" style="font-size: 12px; color: #16a34a;">
            <span class="bold">ESTADO:</span>
            <span class="bold">TOTALMENTE PAGADO</span>
        </div>
    @endif
    <div class="divider"></div>

    <div class="row-flex">
        <span>Método de Pago:</span>
        <span class="bold">{{ $paidToday > 0 ? strtoupper($transaction->payment_method) : strtoupper($transaction->payment_method) . ' (SIN ABONO INICIAL)' }}</span>
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
    @if ($cardRef !== '')
        <div class="row-flex" style="font-size: 10.5px;"><span>Ref. Tarjeta:</span><span class="bold">{{ $cardRef }}</span></div>
    @endif
    @if ($transferRef !== '')
        <div class="row-flex" style="font-size: 10.5px;"><span>Ref. Transferencia:</span><span class="bold">{{ $transferRef }}</span></div>
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

    <script src="{{ asset('assets/js/pos-hardware.js') }}?v=20261113"></script>
    <script>
        function safeStorageGet(key) {
            try {
                return window.localStorage ? window.localStorage.getItem(key) : null;
            } catch (e) {
                return null;
            }
        }

        function safeStorageSet(key, val) {
            try {
                if (window.localStorage) {
                    window.localStorage.setItem(key, val);
                }
            } catch (e) {}
        }

        let paperMode = (function () {
            try {
                const urlPaper = new URLSearchParams(window.location.search).get('paper');
                if (urlPaper && ['80', '58', 'letter'].includes(urlPaper)) {
                    return urlPaper;
                }
            } catch (e) {}
            const stored = safeStorageGet('zya_ticket_paper_mode');
            if (stored && ['80', '58', 'letter'].includes(stored)) {
                return stored;
            }
            return safeStorageGet('zya_ticket_width') === '58' ? '58' : '80';
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
                if (pageRuleEl) pageRuleEl.textContent = '@page { size: letter portrait; margin: 10mm; }';
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
            safeStorageSet('zya_ticket_paper_mode', mode);
            safeStorageSet('zya_ticket_width', mode === '58' ? '58' : '80');
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

        async function waitBeforeAutoPrint() {
            try {
                if (document.fonts && document.fonts.ready) {
                    await Promise.race([
                        document.fonts.ready,
                        new Promise(r => setTimeout(r, 250))
                    ]);
                }
            } catch (e) {}
            await new Promise(r => {
                const raf = window.requestAnimationFrame || (cb => setTimeout(cb, 16));
                raf(() => raf(r));
            });
        }

        window.addEventListener('load', async function () {
            applyPaperWidth();

            let skipDrawerParam = false;
            let isPreviewMode = false;
            try {
                const queryParams = new URLSearchParams(window.location.search);
                skipDrawerParam = queryParams.get('skip_drawer') === '1';
                isPreviewMode = queryParams.get('preview') === '1';
            } catch (e) {}

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
                await waitBeforeAutoPrint();
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
