@php
    $isArticle = $loan->isArticleCredit();
    $signDate = $loan->start_date ?: ($loan->created_at ?: now());
    $place = $loan->resolvePlaceOfSigning();
    $debtorName = $loan->customer?->tit_name ?? '-';
    $debtorPhone = $loan->customer?->tit_phone ?? '-';
    $debtorAddress = $loan->customer?->tit_address ?? '-';
    $aval = $loan->resolveAvalInfo();
    $countWords = \App\Models\Loan::integerCountInSpanish((int) $loan->term_periods);
    $totalWords = mb_strtolower(\App\Models\FinSale::numberToWords($loan->total_amount), 'UTF-8');
    $totalWordsUpper = \App\Models\FinSale::numberToWords($loan->total_amount);

    $otherExtraFields = [];
    if (!empty($loan->type_specific_data) && is_array($loan->type_specific_data)) {
        foreach ($loan->type_specific_data as $k => $v) {
            if (!in_array($k, ['aval_nombre', 'aval_name', 'aval_telefono', 'aval_phone', 'aval_domicilio', 'aval_address'], true) && trim((string) $v) !== '') {
                $otherExtraFields[$k] = $v;
            }
        }
    }
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pagaré Mercantil - {{ $loan->loan_code }}</title>
    <style>
        @page {
            size: letter portrait;
            margin: 11mm 13mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11.5px;
            color: #111827;
            line-height: 1.42;
            margin: 14px auto;
            max-width: 780px;
            background: #fff;
        }
        .no-print-bar {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 14px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: space-between;
            align-items: center;
        }
        .nav-btn {
            padding: 6px 11px;
            font-size: 12px;
            font-weight: bold;
            border: 1px solid #94a3b8;
            border-radius: 5px;
            background: #fff;
            text-decoration: none;
            color: #1e293b;
            display: inline-block;
        }
        .action-btn {
            padding: 7px 14px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 12.5px;
            text-decoration: none;
            display: inline-block;
        }
        .header-box {
            border: 1.5px solid #1f2937;
            border-radius: 5px;
            padding: 10px 14px;
            margin-bottom: 12px;
        }
        .doc-title {
            font-size: 19px;
            font-weight: bold;
            letter-spacing: 0.4px;
            margin-bottom: 4px;
        }
        .meta-line {
            font-size: 11.5px;
            line-height: 1.38;
        }
        .amount-box {
            border: 1.5px solid #1f2937;
            background: #eef2f6;
            padding: 7px 13px;
            font-size: 14px;
            font-weight: bold;
            display: inline-block;
            white-space: nowrap;
        }
        .legal-paragraphs {
            text-align: justify;
            margin-bottom: 10px;
        }
        .legal-paragraphs p {
            margin: 0 0 8px 0;
        }
        .article-box {
            border: 1px solid #374151;
            border-radius: 4px;
            padding: 7px 10px;
            margin: 8px 0 10px 0;
            background: #f9fafb;
        }
        .article-box-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 2px;
        }
        table.article-grid {
            width: 100%;
            border-collapse: collapse;
        }
        table.article-grid td {
            font-size: 10.8px;
            padding: 2px 6px 2px 0;
            vertical-align: top;
        }
        table.parties-table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0 12px 0;
        }
        table.parties-table td {
            vertical-align: top;
            font-size: 11px;
            line-height: 1.38;
        }
        .party-title {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 11.5px;
            margin-bottom: 3px;
        }
        .section-banner {
            border: 1.2px solid #374151;
            padding: 5px 10px;
            font-weight: bold;
            font-size: 11.5px;
            text-transform: uppercase;
            background: #f8fafc;
            margin-top: 8px;
        }
        table.schedule {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            margin-bottom: 14px;
        }
        table.schedule th,
        table.schedule td {
            border: 1px solid #6b7280;
            padding: 4.5px 7px;
            font-size: 11px;
            text-align: center;
        }
        table.schedule th {
            background: #e5e7eb;
            font-weight: bold;
        }
        table.schedule tr.total-row td {
            font-weight: bold;
            background: #f3f4f6;
        }
        table.signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 26px;
            page-break-inside: avoid;
        }
        table.signatures-table td {
            vertical-align: bottom;
        }
        .sig-line {
            border-top: 1.2px solid #111827;
            padding-top: 4px;
            text-align: center;
            font-size: 10.8px;
            line-height: 1.32;
        }
        .thumb-box {
            width: 64px;
            height: 76px;
            border: 1.2px solid #1f2937;
            margin: 0 auto 3px auto;
        }
        .thumb-label {
            font-size: 9.5px;
            text-align: center;
            color: #374151;
        }
        .footer-meta {
            margin-top: 14px;
            font-size: 11.2px;
        }
        @media print {
            .no-print { display: none !important; }
            body {
                margin: 0;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="no-print no-print-bar">
        <div style="display: flex; flex-wrap: wrap; gap: 6px;">
            <a href="{{ route('loans.show', $loan->id) }}" class="nav-btn">
                &larr; {{ $isArticle ? 'Volver al Crédito' : 'Volver al Préstamo' }}
            </a>
            <a href="{{ $isArticle ? route('article-credits.index') : route('loans.index') }}" class="nav-btn">
                {{ $isArticle ? 'Crédito de Artículos' : 'Catálogo de Préstamos' }}
            </a>
            <a href="{{ route('central-pos.index') }}" class="nav-btn">Punto de Venta</a>
            <a href="{{ route('dashboard') }}" class="nav-btn">Menú Principal</a>
        </div>
        <div style="display: flex; flex-wrap: wrap; gap: 6px;">
            <a href="{{ route('loans.delivery-receipt.print', $loan->id) }}" class="action-btn" style="background: #0f766e; color: #fff;">
                {{ $isArticle ? 'Ver Recibo de Entrega de Mercancía' : 'Ver Recibo de Entrega de Préstamo' }}
            </a>
            <a href="{{ route('loans.pagare.print', ['loan' => $loan->id, 'pdf' => 1]) }}" class="action-btn" style="background: #475569; color: #fff;">
                Descargar PDF
            </a>
            <button type="button" onclick="window.print()" class="action-btn" style="background: #1d4ed8; color: #fff;">
                Imprimir Pagaré
            </button>
        </div>
    </div>

    <div class="header-box">
        <table width="100%" cellspacing="0" cellpadding="0">
            <tr>
                <td valign="top">
                    <div class="doc-title">PAGARÉ MERCANTIL</div>
                    <div class="meta-line">
                        {{ $isArticle ? 'Folio de venta a crédito' : 'Folio de préstamo' }} <strong>{{ $loan->loan_code }}</strong><br>
                        Sucursal {{ $loan->branch?->name ?? 'ZYA' }}<br>
                        <strong>Fecha del {{ $isArticle ? 'crédito' : 'préstamo' }}:</strong> {{ \App\Models\Loan::formatDateEs($signDate, true) }}
                        &nbsp;|&nbsp; <strong>Fecha de Suscripción:</strong> {{ $signDate->format('d/m/Y') }}
                    </div>
                </td>
                <td align="right" valign="top">
                    <div class="amount-box">
                        BUENO POR: ${{ number_format($loan->total_amount, 2) }} M.N.
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="legal-paragraphs">
        <p>
            Debo y pagaré incondicionalmente por este <strong>PAGARÉ</strong> a la orden de <strong>Nancy Esmeralda Zapata Medina</strong>,
            en {{ $place }}, la cantidad total de <strong>${{ number_format($loan->total_amount, 2) }} MXN</strong>
            (<span style="display:none;">{{ $totalWordsUpper }}</span>{{ $totalWords }}),
            mediante <strong>{{ $countWords }} ({{ $loan->term_periods }}) pago{{ $loan->term_periods > 1 ? 's' : '' }} de ${{ number_format($loan->installment_amount, 2) }} MXN</strong> cada uno,
            {{ $loan->payment_schedule_description }}, conforme al calendario incluido en este pagaré.
        </p>
        @if ($isArticle)
            <p>
                Recibí a mi entera satisfacción el artículo descrito a continuación en calidad de compraventa a crédito
                (Precio total: <strong>${{ number_format($loan->article_price, 2) }} MXN</strong>@if ((float) $loan->down_payment > 0), Enganche cubierto: <strong>${{ number_format($loan->down_payment, 2) }} MXN</strong>@endif, Monto financiado: <strong>${{ number_format($loan->principal_amount, 2) }} MXN</strong>).
                Si dejo de cubrir cualquiera de las parcialidades en su fecha programada, se dará por vencido anticipadamente el saldo pendiente de este pagaré.
            </p>
        @else
            <p>
                Recibí a mi entera satisfacción la cantidad de <strong>${{ number_format($loan->principal_amount, 2) }} MXN</strong> en calidad de préstamo personal.
                Si dejo de cubrir cualquiera de las parcialidades en su fecha programada, se dará por vencido anticipadamente el saldo pendiente de este pagaré.
            </p>
        @endif
    </div>

    @if ($isArticle && !empty($loan->article_specs))
        <div class="article-box">
            <div class="article-box-title">Ficha del Artículo en Compraventa a Crédito</div>
            <table class="article-grid">
                @foreach (array_chunk($loan->article_specs, 2, true) as $rowSpecs)
                    <tr>
                        @foreach ($rowSpecs as $specLabel => $specValue)
                            <td width="50%"><strong>{{ $specLabel }}:</strong> {{ $specValue }}</td>
                        @endforeach
                        @if (count($rowSpecs) === 1)
                            <td width="50%"></td>
                        @endif
                    </tr>
                @endforeach
            </table>
        </div>
    @elseif (!$isArticle && !empty($otherExtraFields))
        <div class="article-box">
            <div class="article-box-title">Datos Específicos / Garantía</div>
            <table class="article-grid">
                @foreach (array_chunk($otherExtraFields, 2, true) as $rowSpecs)
                    <tr>
                        @foreach ($rowSpecs as $key => $val)
                            <td width="50%"><strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong> {{ $val }}</td>
                        @endforeach
                        @if (count($rowSpecs) === 1)
                            <td width="50%"></td>
                        @endif
                    </tr>
                @endforeach
            </table>
        </div>
    @endif

    <table class="parties-table">
        <tr>
            <td width="{{ $aval['has_aval'] ? '49%' : '100%' }}" style="padding-right: 12px;">
                <div class="party-title">Datos del Deudor</div>
                <div><strong>Nombre:</strong> {{ $debtorName }}</div>
                <div><strong>Teléfono:</strong> {{ $debtorPhone }}</div>
                <div><strong>Domicilio:</strong> {{ $debtorAddress }}</div>
            </td>
            @if ($aval['has_aval'])
                <td width="51%" style="padding-left: 12px;">
                    <div class="party-title">Datos del Aval</div>
                    <div><strong>Nombre:</strong> {{ $aval['name'] ?: '-' }}</div>
                    <div><strong>Teléfono:</strong> {{ $aval['phone'] ?: '-' }}</div>
                    <div><strong>Domicilio:</strong> {{ $aval['address'] ?: '-' }}</div>
                </td>
            @endif
        </tr>
    </table>

    <div class="section-banner">Calendario de Pagos Programados</div>
    <table class="schedule">
        <thead>
            <tr>
                <th width="12%">Pago</th>
                <th width="26%">Fecha programada</th>
                <th width="20%">Importe</th>
                <th width="42%">Firma o sello de recibido</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($loan->installments as $inst)
                <tr>
                    <td>{{ $inst->installment_number }}</td>
                    <td>{{ $inst->scheduled_date ? $inst->scheduled_date->format('d/m/Y') : '-' }}</td>
                    <td>${{ number_format($inst->scheduled_amount, 2) }}</td>
                    <td style="height: 20px;"></td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2">TOTAL</td>
                <td>${{ number_format($loan->total_amount, 2) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <table class="signatures-table">
        <tr>
            @if ($aval['has_aval'])
                <td width="35%" style="padding-right: 8px;">
                    <div class="sig-line">
                        <strong>ACEPTO Y PAGARÉ</strong><br>
                        FIRMA DEL DEUDOR<br>
                        <span style="font-weight: normal;">{{ $debtorName }}</span>
                    </div>
                </td>
                <td width="15%" align="center" style="padding: 0 6px;">
                    <div class="thumb-box"></div>
                    <div class="thumb-label">Huella del pulgar</div>
                </td>
                <td width="35%" style="padding-left: 8px; padding-right: 8px;">
                    <div class="sig-line">
                        <strong>FIRMA DEL AVAL</strong><br>
                        Por aval de {{ $debtorName }}<br>
                        <span style="font-weight: normal;">{{ $aval['name'] }}</span>
                    </div>
                </td>
                <td width="15%" align="center" style="padding-left: 6px;">
                    <div class="thumb-box"></div>
                    <div class="thumb-label">Huella del pulgar</div>
                </td>
            @else
                <td width="18%"></td>
                <td width="46%" style="padding-right: 12px;">
                    <div class="sig-line">
                        <strong>ACEPTO Y PAGARÉ</strong><br>
                        FIRMA DEL DEUDOR<br>
                        <span style="font-weight: normal;">{{ $debtorName }}</span>
                    </div>
                </td>
                <td width="18%" align="center">
                    <div class="thumb-box"></div>
                    <div class="thumb-label">Huella del pulgar</div>
                </td>
                <td width="18%"></td>
            @endif
        </tr>
    </table>

    <div class="footer-meta">
        <span><strong>Lugar de firma:</strong> {{ $place }}</span>
        <span style="margin-left: 28px;"><strong>Fecha de firma:</strong> ______ / ______ / ________</span>
    </div>
</body>
</html>
