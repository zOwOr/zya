<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pagaré {{ $loan->loan_code }}</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            color: #1f2937;
            line-height: 1.55;
            margin: 25px;
        }
        .header-box {
            border: 2px solid #111827;
            padding: 14px 18px;
            margin-bottom: 18px;
        }
        .title {
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .amount-box {
            border: 2px solid #111827;
            background: #f3f4f6;
            padding: 8px 14px;
            font-size: 16px;
            font-weight: bold;
            display: inline-block;
        }
        .legal-body {
            text-align: justify;
            border: 1px solid #d1d5db;
            padding: 18px;
            margin-bottom: 18px;
        }
        table.schedule {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 20px;
        }
        table.schedule th, table.schedule td {
            border: 1px solid #9ca3af;
            padding: 6px 8px;
            font-size: 12px;
            text-align: center;
        }
        table.schedule th {
            background: #e5e7eb;
        }
        .signatures {
            margin-top: 55px;
            width: 100%;
        }
        .sig-line {
            border-top: 1px solid #111827;
            width: 75%;
            margin: 0 auto;
            padding-top: 6px;
            text-align: center;
            font-weight: bold;
        }
        @media print {
            .no-print { display: none !important; }
            body { margin: 10px; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px; margin-bottom: 18px; display: flex; flex-wrap: wrap; gap: 8px; justify-content: space-between; align-items: center;">
        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
            <a href="{{ route('loans.show', $loan->id) }}" style="padding: 6px 12px; font-size: 13px; font-weight: bold; border: 1px solid #94a3b8; border-radius: 5px; background: #fff; text-decoration: none; color: #1e293b; display: inline-flex; align-items: center;">
                ⬅️ Volver al Préstamo
            </a>
            <a href="{{ route('loans.index') }}" style="padding: 6px 12px; font-size: 13px; font-weight: bold; border: 1px solid #94a3b8; border-radius: 5px; background: #fff; text-decoration: none; color: #1e293b; display: inline-flex; align-items: center;">
                📋 Catálogo de Préstamos
            </a>
            <a href="{{ route('central-pos.index') }}" style="padding: 6px 12px; font-size: 13px; font-weight: bold; border: 1px solid #94a3b8; border-radius: 5px; background: #fff; text-decoration: none; color: #1e293b; display: inline-flex; align-items: center;">
                🛒 Punto de Venta
            </a>
            <a href="{{ route('dashboard') }}" style="padding: 6px 12px; font-size: 13px; font-weight: bold; border: 1px solid #94a3b8; border-radius: 5px; background: #fff; text-decoration: none; color: #1e293b; display: inline-flex; align-items: center;">
                🏠 Menú Principal
            </a>
        </div>
        <div>
            <button onclick="window.print()" style="padding: 8px 16px; background: #1d4ed8; color: #fff; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 13px;">
                🖨️ Imprimir Pagaré para Firma
            </button>
        </div>
    </div>

    <div class="header-box">
        <table width="100%">
            <tr>
                <td>
                    <div class="title">PAGARÉ MERCANTIL</div>
                    <div><strong>Folio de Préstamo:</strong> {{ $loan->loan_code }}</div>
                    <div><strong>Sucursal:</strong> {{ $loan->branch?->name ?? 'ZYA' }}</div>
                    <div><strong>Fecha de Suscripción:</strong> {{ ($loan->start_date ?: $loan->created_at ?: now())->format('d/m/Y') }}</div>
                </td>
                <td align="right">
                    <div class="amount-box">
                        BUENO POR: ${{ number_format($loan->total_amount, 2) }} M.N.
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="legal-body">
        <p>
            Debo(emos) y pagaré(mos) incondicionalmente por este <strong>PAGARÉ</strong> a la orden de <strong>Nancy Esmeralda Zapata Medina</strong> en esta ciudad o en cualquier otro lugar que se me(nos) requiera de pago,
            la cantidad total de <strong>${{ number_format($loan->total_amount, 2) }} MXN</strong>
            ({{ \App\Models\FinSale::numberToWords($loan->total_amount) }}),
            mediante <strong>{{ $loan->term_periods }}</strong> pagos periódicos en modalidad <strong>{{ strtoupper($loan->payment_frequency_label) }}</strong>
            de <strong>${{ number_format($loan->installment_amount, 2) }} MXN</strong> cada uno, iniciando conforme al calendario de vencimientos anexo.
        </p>
        <p>
            Valor recibido a mi(nuestra) entera satisfacción en calidad de <strong>{{ $loan->loanType?->name ?? 'Préstamo' }}</strong>.
            Este pagaré forma parte de una serie numerada del 1 al {{ $loan->term_periods }} y todos están sujetos a la condición de que,
            al no pagarse cualquiera de ellos a su vencimiento, serán exigibles todos los que le sigan en número, además de los ya vencidos,
            desde la fecha de vencimiento del presente documento hasta el día de su liquidación total.
        </p>

        @if (!empty($loan->type_specific_data))
            <p>
                <strong>Datos Específicos / Garantía del Préstamo:</strong><br>
                @foreach ($loan->type_specific_data as $key => $val)
                    @if (!empty($val))
                        • <strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong> {{ $val }}<br>
                    @endif
                @endforeach
            </p>
        @endif

        <p style="margin-bottom: 0;">
            <strong>Datos del Deudor / Suscriptor:</strong><br>
            <strong>Nombre:</strong> {{ $loan->customer?->tit_name ?? '-' }}<br>
            <strong>Teléfono:</strong> {{ $loan->customer?->tit_phone ?? '-' }}<br>
            <strong>Dirección:</strong> {{ $loan->customer?->tit_address ?? '-' }}
        </p>
    </div>

    <strong>Calendario de Pagos Programados:</strong>
    <table class="schedule">
        <thead>
            <tr>
                <th>No. de Pago</th>
                <th>Día Programado de Pago</th>
                <th>Importe Programado</th>
                <th>Firma / Sello de Recibido</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($loan->installments as $inst)
                <tr>
                    <td>Pago #{{ $inst->installment_number }}</td>
                    <td>{{ $inst->scheduled_date ? $inst->scheduled_date->format('d/m/Y') : '-' }}</td>
                    <td>${{ number_format($inst->scheduled_amount, 2) }}</td>
                    <td style="height: 22px;"></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="signatures">
        <tr>
            <td width="50%" align="center">
                <div class="sig-line">
                    ACEPTO Y PAGARÉ (FIRMA DEL CLIENTE / DEUDOR)<br>
                    <span style="font-weight: normal; font-size: 12px;">{{ $loan->customer?->tit_name }}</span>
                </div>
            </td>
            <td width="50%" align="center">
                <div class="sig-line">
                    FIRMA DEL AVAL<br>
                    <span style="font-weight: normal; font-size: 12px;">{{ $loan->customer?->aval_name ?: ($loan->type_specific_data['aval_nombre'] ?? $loan->type_specific_data['aval_name'] ?? '') }}</span>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>

