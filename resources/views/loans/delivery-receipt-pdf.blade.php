@php
    $isArticle = $loan->isArticleCredit();
    $place = $loan->resolvePlaceOfSigning();
    $debtorName = $loan->customer?->tit_name ?? '-';
    $aval = $loan->resolveAvalInfo();
    $countWords = \App\Models\Loan::integerCountInSpanish((int) $loan->term_periods);
    $principalWords = mb_strtolower(\App\Models\FinSale::numberToWords($loan->principal_amount), 'UTF-8');
    $firstInstallment = $loan->installments->sortBy('installment_number')->first();
    $firstPaymentLongDate = $firstInstallment && $firstInstallment->scheduled_date
        ? \App\Models\Loan::formatDateEs($firstInstallment->scheduled_date, false)
        : \App\Models\Loan::formatDateEs($loan->start_date, false);
    $headerBoxAmount = $isArticle ? (float) ($loan->article_price ?: $loan->principal_amount) : (float) $loan->principal_amount;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $isArticle ? 'Recibo de Entrega de Mercancía' : 'Recibo de Entrega de Préstamo' }} - {{ $loan->loan_code }}</title>
    <style>
        @page {
            size: letter portrait;
            margin: 14mm 15mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            color: #111827;
            line-height: 1.55;
            margin: 18px auto;
            max-width: 780px;
            background: #fff;
        }
        .no-print-bar {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 18px;
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
            padding: 12px 16px;
            margin-bottom: 18px;
        }
        .doc-title {
            font-size: 19px;
            font-weight: bold;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .amount-box {
            border: 1.5px solid #1f2937;
            background: #e5e7eb;
            padding: 8px 16px;
            font-size: 15px;
            font-weight: bold;
            display: inline-block;
            white-space: nowrap;
        }
        .meta-section {
            margin-bottom: 18px;
            font-size: 13px;
            line-height: 1.7;
        }
        .check-square {
            display: inline-block;
            width: 13px;
            height: 13px;
            border: 1.4px solid #111827;
            text-align: center;
            line-height: 11px;
            font-size: 11px;
            font-weight: bold;
            vertical-align: middle;
            margin-right: 4px;
        }
        .article-box {
            border: 1.2px solid #374151;
            border-radius: 5px;
            padding: 10px 12px;
            margin: 12px 0 18px 0;
            background: #f9fafb;
        }
        .article-box-title {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 6px;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 3px;
        }
        table.article-grid {
            width: 100%;
            border-collapse: collapse;
        }
        table.article-grid td {
            font-size: 12px;
            padding: 3px 8px 3px 0;
            vertical-align: top;
        }
        .body-text {
            text-align: justify;
            margin-bottom: 22px;
        }
        .body-text p {
            margin: 0 0 14px 0;
        }
        table.signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 72px;
            page-break-inside: avoid;
        }
        .sig-line {
            border-top: 1.2px solid #111827;
            width: 82%;
            margin: 0 auto;
            padding-top: 6px;
            text-align: center;
            font-size: 12px;
            line-height: 1.4;
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
            <a href="{{ route('loans.pagare.print', $loan->id) }}" class="action-btn" style="background: #334155; color: #fff;">
                Ver Pagaré Mercantil
            </a>
            <a href="{{ route('loans.delivery-receipt.print', ['loan' => $loan->id, 'pdf' => 1]) }}" class="action-btn" style="background: #475569; color: #fff;">
                Descargar PDF
            </a>
            <button type="button" onclick="window.print()" class="action-btn" style="background: #0f766e; color: #fff;">
                Imprimir Recibo
            </button>
        </div>
    </div>

    <div class="header-box">
        <table width="100%" cellspacing="0" cellpadding="0">
            <tr>
                <td valign="middle">
                    <div class="doc-title">
                        {{ $isArticle ? 'RECIBO DE ENTREGA DE MERCANCÍA' : 'RECIBO DE ENTREGA DE PRÉSTAMO' }}
                    </div>
                    <div style="font-size: 12.5px;">
                        {{ $isArticle ? 'Folio de venta a crédito' : 'Folio del préstamo' }} <strong>{{ $loan->loan_code }}</strong>
                    </div>
                </td>
                <td align="right" valign="middle">
                    <div class="amount-box">
                        ${{ number_format($headerBoxAmount, 2) }} MXN
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="meta-section">
        <div>Lugar de entrega: <strong>{{ $place }}</strong></div>
        <div>Fecha real de entrega: ______ / ______ / ________</div>
        @if (!$isArticle)
            <div>
                Medio de entrega:
                <span style="margin-left: 14px;">
                    <span class="check-square">{{ $loan->delivery_method === 'efectivo' ? 'X' : '' }}</span>Efectivo
                </span>
                <span style="margin-left: 22px;">
                    <span class="check-square">{{ $loan->delivery_method === 'transferencia' ? 'X' : '' }}</span>Transferencia
                </span>
            </div>
        @else
            <div>
                Resumen financiero:
                <strong>Precio:</strong> ${{ number_format($loan->article_price, 2) }} MXN
                &nbsp;|&nbsp; <strong>Enganche:</strong> ${{ number_format($loan->down_payment, 2) }} MXN
                &nbsp;|&nbsp; <strong>Monto financiado:</strong> ${{ number_format($loan->principal_amount, 2) }} MXN
            </div>
        @endif
    </div>

    @if ($isArticle && !empty($loan->article_specs))
        <div class="article-box">
            <div class="article-box-title">Identificación de la Mercancía / Artículo Entregado</div>
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
    @endif

    <div class="body-text">
        @if ($isArticle)
            <p>
                Yo, <strong>{{ $debtorName }}</strong>, recibo a mi entera satisfacción de <strong>Nancy Esmeralda Zapata Medina</strong>
                el artículo descrito como <strong>{{ $loan->article_summary_label }}</strong>
                con valor total de <strong>${{ number_format($loan->article_price, 2) }} MXN</strong>@if ((float) $loan->down_payment > 0), habiendo cubierto un enganche de <strong>${{ number_format($loan->down_payment, 2) }} MXN</strong>@endif, quedando un saldo financiado de <strong>${{ number_format($loan->principal_amount, 2) }} MXN</strong> ({{ $principalWords }}).
                Me comprometo a pagarlo en <strong>{{ $countWords }} ({{ $loan->term_periods }}) pago{{ $loan->term_periods > 1 ? 's' : '' }} de ${{ number_format($loan->installment_amount, 2) }} MXN cada uno</strong>,
                {{ $loan->payment_schedule_description }}, conforme al calendario del pagaré del mismo folio,
                comenzando el <strong>{{ $firstPaymentLongDate }}</strong>.
                El total de los {{ $countWords }} pagos programados es de <strong>${{ number_format($loan->total_amount, 2) }} MXN</strong>.
            </p>
        @else
            <p>
                Yo, <strong>{{ $debtorName }}</strong>, recibo de <strong>Nancy Esmeralda Zapata Medina</strong> un préstamo de
                <strong>${{ number_format($loan->principal_amount, 2) }} MXN</strong> ({{ $principalWords }}).
                Me comprometo a pagarlo en <strong>{{ $countWords }} ({{ $loan->term_periods }}) pago{{ $loan->term_periods > 1 ? 's' : '' }} de ${{ number_format($loan->installment_amount, 2) }} MXN cada uno</strong>,
                {{ $loan->payment_schedule_description }}, conforme al calendario del pagaré del mismo folio,
                comenzando el <strong>{{ $firstPaymentLongDate }}</strong>.
                El total de los {{ $countWords }} pagos es de <strong>${{ number_format($loan->total_amount, 2) }} MXN</strong>.
            </p>
        @endif

        <p>
            En caso de atraso, el saldo pendiente podrá reclamarse conforme a lo establecido en el pagaré.
        </p>

        @if ($aval['has_aval'])
            <p>
                <strong>{{ $aval['name'] }}</strong> firma este recibo en su carácter de aval del titular, conforme al pagaré del mismo folio.
            </p>
        @endif
    </div>

    <table class="signatures-table">
        <tr>
            @if ($aval['has_aval'])
                <td width="50%" align="center">
                    <div class="sig-line">
                        <strong>FIRMA DEL TITULAR QUE RECIBE</strong><br>
                        <span style="font-weight: normal;">{{ $debtorName }}</span>
                    </div>
                </td>
                <td width="50%" align="center">
                    <div class="sig-line">
                        <strong>FIRMA DEL AVAL</strong><br>
                        <span style="font-weight: normal;">{{ $aval['name'] }}</span>
                    </div>
                </td>
            @else
                <td width="20%"></td>
                <td width="60%" align="center">
                    <div class="sig-line">
                        <strong>FIRMA DEL TITULAR QUE RECIBE</strong><br>
                        <span style="font-weight: normal;">{{ $debtorName }}</span>
                    </div>
                </td>
                <td width="20%"></td>
            @endif
        </tr>
    </table>
</body>
</html>

