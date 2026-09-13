<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Póliza de Venta - {{ $sale->sale_code }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.4;
            margin: 20px;
        }
        .header-table {
            width: 100%;
            margin-bottom: 20px;
            border-bottom: 2px solid #0056b3;
            padding-bottom: 10px;
        }
        .title {
            font-size: 20px;
            font-weight: bold;
            color: #0056b3;
        }
        .sale-code {
            font-size: 16px;
            font-weight: bold;
            color: #d9534f;
            text-align: right;
        }
        .section-title {
            font-size: 13px;
            font-weight: bold;
            background-color: #f1f5f9;
            padding: 5px 8px;
            margin-top: 15px;
            margin-bottom: 8px;
            border-left: 4px solid #0056b3;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        table.data-table th, table.data-table td {
            padding: 6px 8px;
            border: 1px solid #ddd;
        }
        table.data-table th {
            background-color: #f8f9fa;
            text-align: left;
            font-size: 11px;
            color: #555;
        }
        .totals-table {
            width: 50%;
            margin-left: auto;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .totals-table td {
            padding: 5px 8px;
        }
        .totals-table tr.total-row td {
            font-weight: bold;
            border-top: 2px solid #333;
            font-size: 14px;
        }
        .signatures {
            margin-top: 60px;
            width: 100%;
        }
        .signatures td {
            text-align: center;
            width: 50%;
        }
        .signature-line {
            width: 80%;
            margin: 0 auto;
            border-top: 1px solid #333;
            padding-top: 5px;
            font-weight: bold;
            font-size: 11px;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #777;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td>
                <div class="title">ZYA - MÓDULO DE FINANCIERAS</div>
                <div style="font-size: 11px; color: #666;">
                    Sucursal: {{ $sale->branch?->name ?? 'Sucursal Principal' }}<br>
                    Fecha de emisión: {{ $sale->sale_date ? $sale->sale_date->format('d/m/Y H:i') : date('d/m/Y H:i') }}
                </div>
            </td>
            <td style="text-align: right;">
                <div class="sale-code">{{ $sale->sale_code }}</div>
                <div style="font-size: 11px;">Estado: <strong>{{ strtoupper($sale->status) }}</strong></div>
                <div style="font-size: 11px;">Financiera: <strong>{{ $sale->financiera?->name ?? 'Directo' }}</strong></div>
            </td>
        </tr>
    </table>

    <!-- DATOS DEL CLIENTE -->
    <div class="section-title">DATOS DEL CLIENTE</div>
    <table class="data-table">
        <tr>
            <th style="width: 25%;">Nombre:</th>
            <td style="width: 75%;">{{ $sale->customer_name ?: 'No especificado' }}</td>
        </tr>
        <tr>
            <th>Teléfono:</th>
            <td>{{ $sale->customer_phone ?: 'No especificado' }}</td>
        </tr>
        <tr>
            <th>Correo Electrónico:</th>
            <td>{{ $sale->customer_email ?: 'No especificado' }}</td>
        </tr>
        <tr>
            <th>INE / Identificación:</th>
            <td>{{ $sale->customer_ine ?: 'No especificado' }}</td>
        </tr>
        <tr>
            <th>Dirección:</th>
            <td>{{ $sale->customer_address ?: 'No especificado' }}</td>
        </tr>
    </table>

    <!-- DATOS DEL EQUIPO -->
    <div class="section-title">DETALLES DEL DISPOSITIVO MÓVIL</div>
    <table class="data-table">
        <tr>
            <th style="width: 20%;">IMEI:</th>
            <td style="width: 30%; font-weight: bold;">{{ $sale->device?->imei ?? 'N/A' }}</td>
            <th style="width: 20%;">Marca / Modelo:</th>
            <td style="width: 30%;">{{ $sale->device?->brand?->name }} {{ $sale->device?->model }}</td>
        </tr>
        <tr>
            <th>Color:</th>
            <td>{{ $sale->device?->color ?: 'N/A' }}</td>
            <th>Vendedor:</th>
            <td>{{ $sale->seller?->name ?? 'N/A' }}</td>
        </tr>
    </table>

    <!-- CONDICIONES ECONÓMICAS -->
    <div class="section-title">CONDICIONES ECONÓMICAS Y FINANCIAMIENTO</div>
    <table class="totals-table">
        <tr>
            <td>Precio Total del Equipo:</td>
            <td style="text-align: right;">${{ number_format($sale->price, 2) }}</td>
        </tr>
        <tr>
            <td>Enganche Inicial Pagado:</td>
            <td style="text-align: right; color: green;">- ${{ number_format($sale->down_payment, 2) }}</td>
        </tr>
        <tr class="total-row">
            <td>Saldo Financiado:</td>
            <td style="text-align: right; color: #0056b3;">${{ number_format($sale->credit_amount, 2) }}</td>
        </tr>
        <tr>
            <td>Plazo de Crédito:</td>
            <td style="text-align: right;">{{ $sale->term_months ? $sale->term_months . ' Meses' : 'Contado' }}</td>
        </tr>
        @if ($sale->term_months && $sale->term_months > 0 && $sale->credit_amount > 0)
        <tr>
            <td>Mensualidad Aproximada:</td>
            <td style="text-align: right; font-weight: bold;">${{ number_format($sale->credit_amount / $sale->term_months, 2) }} / mes</td>
        </tr>
        @endif
    </table>

    @if ($sale->notes->isNotEmpty())
    <div class="section-title">OBSERVACIONES / NOTAS DE VENTA</div>
    <div style="font-size: 11px; padding: 5px; background: #fafafa; border: 1px solid #eee;">
        @foreach ($sale->notes as $n)
            <p style="margin: 3px 0;">• {{ $n->note }} <small style="color: #888;">({{ $n->created_at->format('d/m/Y') }})</small></p>
        @endforeach
    </div>
    @endif

    <!-- FIRMAS -->
    <table class="signatures">
        <tr>
            <td>
                <div class="signature-line">
                    Firma del Cliente<br>
                    <span style="font-weight: normal; font-size: 10px;">Acepto las condiciones y entrega del equipo</span>
                </div>
            </td>
            <td>
                <div class="signature-line">
                    Firma y Sello del Vendedor / Sucursal<br>
                    <span style="font-weight: normal; font-size: 10px;">{{ $sale->seller?->name ?? 'Vendedor Autorizado' }}</span>
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Comprobante emitido por el sistema Zya. Valide los términos de garantía y pagos con su financiera.
    </div>

</body>
</html>
