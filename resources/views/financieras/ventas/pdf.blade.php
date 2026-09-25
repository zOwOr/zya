<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nota de Venta - {{ $sale->sale_code }}</title>
    <style>
        @page {
            size: letter portrait;
            margin: 15mm 16mm 14mm 16mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8.5pt;
            color: #111;
            line-height: 1.3;
            margin: 0;
            padding: 0;
        }

        /* Header */
        .header-table {
            width: 100%;
            border-bottom: 2px solid #000;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }
        .header-left {
            width: 58%;
            vertical-align: top;
        }
        .header-right {
            width: 42%;
            text-align: right;
            vertical-align: top;
        }
        .logo-img {
            height: 55px;
            width: auto;
            max-width: 150px;
            display: block;
            margin-bottom: 2px;
        }
        .logo-text {
            font-size: 24px;
            font-weight: 900;
            letter-spacing: -1px;
            line-height: 1;
        }
        .tagline {
            font-size: 7.5pt;
            font-weight: bold;
            letter-spacing: 0.5px;
            margin-top: 1px;
            color: #222;
        }
        .branches-table {
            width: 100%;
            margin-top: 4px;
            border-collapse: collapse;
        }
        .branch-cell {
            vertical-align: top;
            font-size: 7.5pt;
            line-height: 1.3;
            color: #333;
            padding-right: 6px;
        }

        /* Folio & Fecha Box */
        .folio-box {
            border: 1.5px solid #000;
            text-align: center;
            padding: 2px 8px;
            margin-bottom: 4px;
            display: inline-block;
            min-width: 140px;
            background-color: #fafafa;
        }
        .folio-label {
            font-size: 8pt;
            font-weight: bold;
            border-bottom: 1px solid #000;
            letter-spacing: 1px;
            padding-bottom: 1px;
        }
        .folio-value {
            font-size: 13pt;
            font-weight: 900;
            color: #cc0000;
            padding-top: 1px;
        }
        .fecha-box {
            border: 1.5px solid #000;
            text-align: center;
            padding: 2px 4px;
            display: inline-block;
            min-width: 140px;
            background-color: #fafafa;
        }
        .fecha-label {
            font-size: 7.5pt;
            font-weight: bold;
            border-bottom: 1px solid #000;
            letter-spacing: 1px;
            padding-bottom: 1px;
        }
        .fecha-sub-table {
            width: 100%;
            border-collapse: collapse;
        }
        .fecha-sub-cell {
            text-align: center;
            font-size: 7pt;
            font-weight: bold;
            padding: 1px 3px;
            border-right: 1px solid #aaa;
        }
        .fecha-sub-cell:last-child {
            border-right: none;
        }
        .fecha-sub-val {
            font-size: 10pt;
            font-weight: 900;
            display: block;
        }
        .fin-badge {
            display: inline-block;
            border: 1.5px solid #000;
            border-radius: 4px;
            padding: 2px 8px;
            font-weight: 900;
            font-size: 8.5pt;
            margin-top: 4px;
            background-color: #f0f4f8;
            letter-spacing: 0.5px;
        }

        /* Device Table */
        .device-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .device-table th {
            border: 1.5px solid #000;
            text-align: center;
            font-size: 7.5pt;
            font-weight: bold;
            padding: 4px 2px;
            background: #f0f0f0;
            text-transform: uppercase;
        }
        .device-table td {
            border: 1.5px solid #000;
            text-align: center;
            font-size: 8pt;
            padding: 4px 3px;
        }

        /* Desglose de Datos (Cliente y Referencias) */
        .info-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 0;
            margin-bottom: 8px;
        }
        .info-card {
            border: 1.5px solid #000;
            border-radius: 4px;
            padding: 6px 8px;
            vertical-align: top;
            background-color: #fff;
            width: 50%;
        }
        .info-title {
            font-size: 8pt;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1.5px solid #000;
            background-color: #f0f0f0;
            margin: -6px -8px 5px -8px;
            padding: 3px 8px;
            color: #000;
        }
        .field-row {
            font-size: 8pt;
            line-height: 1.35;
            margin-bottom: 2px;
            border-bottom: 1px dotted #ddd;
            padding-bottom: 1px;
        }
        .field-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        .field-label {
            font-weight: bold;
            font-size: 7.5pt;
            text-transform: uppercase;
            color: #333;
        }
        .field-value {
            color: #000;
        }

        /* Notas / Observaciones registradas de la venta */
        .notes-box {
            border: 1.5px solid #b45309;
            border-radius: 4px;
            padding: 4px 8px;
            margin-bottom: 8px;
            background-color: #fffbeb;
        }
        .notes-title {
            font-size: 7.5pt;
            font-weight: 900;
            text-transform: uppercase;
            border-bottom: 1px solid #fde68a;
            padding-bottom: 2px;
            margin-bottom: 3px;
            color: #b45309;
        }
        .note-item {
            font-size: 7.5pt;
            line-height: 1.3;
            margin-bottom: 2px;
            color: #78350f;
        }

        /* Cláusulas / Políticas Legales */
        .clausulas-box {
            border: 1.2px solid #000;
            border-radius: 3px;
            padding: 3px 8px 3px 6px;
            margin-bottom: 6px;
            background-color: #fafafa;
            overflow: hidden;
        }
        .clausulas-title {
            font-size: 7.2pt;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #000;
            text-align: center;
            background-color: #f0f0f0;
            margin: -3px -8px 3px -6px;
            padding: 2px 6px;
            color: #000;
        }
        .clausulas-list {
            margin: 0 8px 0 0;
            padding-left: 12px;
            padding-right: 6px;
            font-size: 6.8pt;
            line-height: 1.22;
            text-align: justify;
        }
        .clausulas-list li {
            margin-bottom: 1.5px;
        }

        /* Firmas */
        .firmas-table {
            width: 100%;
            margin-top: 10px;
            page-break-inside: avoid;
        }
        .firma-col {
            width: 44%;
            text-align: center;
            vertical-align: bottom;
        }
        .firma-spacer {
            width: 12%;
        }
        .firma-space {
            height: 38px;
            width: 100%;
        }
        .firma-line {
            border-top: 1.5px solid #000;
            margin-bottom: 3px;
            width: 90%;
            margin-left: auto;
            margin-right: auto;
        }
        .firma-role {
            font-size: 8.5pt;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .firma-name {
            font-size: 8pt;
            color: #222;
            margin-top: 2px;
            font-weight: 600;
        }

        /* Estilos específicos para Nota de Venta al Contado */
        .nota-grid-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .nota-grid-table th {
            border: 1.5px solid #000;
            background-color: #f0f0f0;
            text-align: center;
            font-size: 8pt;
            font-weight: 900;
            padding: 4px;
            text-transform: uppercase;
        }
        .nota-grid-table td {
            border: 1.5px solid #000;
            padding: 4px 6px;
            font-size: 8pt;
        }
    </style>
</head>
<body>

    {{-- HEADER --}}
    <table class="header-table">
        <tr>
            <td class="header-left">
                @php
                    $logoPath = public_path('assets/images/logo.png');
                    $logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : null;
                @endphp
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" alt="Logo ZYA" class="logo-img">
                @else
                    <div class="logo-text">&#9112; ZYA</div>
                @endif
                <div class="tagline">ZYA CELULARES. ACCESORIOS. REPARACIONES</div>
                
                {{-- Sucursales dinámicas --}}
                <table class="branches-table">
                    <tr>
                        @forelse($branches as $branch)
                            <td class="branch-cell" style="width: {{ count($branches) > 0 ? floor(100 / count($branches)) : 50 }}%;">
                                <strong>{{ $branch->name }}</strong><br>
                                @if($branch->address) {!! nl2br(e($branch->address)) !!}<br> @endif
                                @if($branch->phone) Tel. {{ $branch->phone }} @endif
                            </td>
                        @empty
                            <td class="branch-cell" style="width: 100%;">
                                <strong>{{ $sale->branch?->name ?? 'ZYA' }}</strong><br>
                                @if($sale->branch?->address) {!! nl2br(e($sale->branch->address)) !!}<br> @endif
                                @if($sale->branch?->phone) Tel. {{ $sale->branch->phone }} @endif
                            </td>
                        @endforelse
                    </tr>
                </table>
            </td>
            <td class="header-right">
                <div class="folio-box">
                    <div class="folio-label">FOLIO DE VENTA</div>
                    <div class="folio-value">N°&nbsp;{{ str_pad((string) ($sale->id ?? 0), 5, '0', STR_PAD_LEFT) }}</div>
                </div>
                <br>
                <div class="fecha-box">
                    <div class="fecha-label">FECHA DE VENTA</div>
                    <table class="fecha-sub-table">
                        <tr>
                            <td class="fecha-sub-cell">DÍA<br><span class="fecha-sub-val">{{ $sale->sale_date ? $sale->sale_date->format('d') : '--' }}</span></td>
                            <td class="fecha-sub-cell">MES<br><span class="fecha-sub-val">{{ $sale->sale_date ? $sale->sale_date->format('m') : '--' }}</span></td>
                            <td class="fecha-sub-cell">AÑO<br><span class="fecha-sub-val">{{ $sale->sale_date ? $sale->sale_date->format('Y') : '----' }}</span></td>
                        </tr>
                    </table>
                </div>
                @if($sale->isContado())
                    <br>
                    <div class="fin-badge" style="background-color: #e6fffa; border-color: #234e52; color: #234e52;">
                        VENTA AL CONTADO
                    </div>
                @elseif($sale->financiera)
                    <br>
                    <div class="fin-badge">
                        FINANCIERA: {{ strtoupper($sale->financiera->name) }}
                    </div>
                @endif
            </td>
        </tr>
    </table>

    @if ($sale->isContado())
        {{-- ========================================== --}}
        {{-- FORMATO NOTA DE VENTA FÍSICA AL CONTADO   --}}
        {{-- ========================================== --}}

        {{-- DATOS DEL CLIENTE EN LA NOTA --}}
        <table style="width: 100%; border: 1.5px solid #000; margin-bottom: 8px; border-collapse: collapse;">
            <tr>
                <td style="padding: 4px 6px; font-size: 8.5pt; width: 62%; border-bottom: 1px solid #ccc;">
                    <strong>CLIENTE:</strong> <span style="font-size: 9pt; font-weight: bold;">{{ $sale->customer_name ?: 'Sin registrar' }}</span>
                </td>
                <td style="padding: 4px 6px; font-size: 8.5pt; width: 38%; border-bottom: 1px solid #ccc;">
                    <strong>TEL.:</strong> {{ $sale->customer_phone ?: '----------------' }}
                </td>
            </tr>
            <tr>
                <td style="padding: 4px 6px; font-size: 8.5pt; border-bottom: 1px solid #ccc;" colspan="2">
                    <strong>DIRECCIÓN:</strong> {{ $sale->customer_address ?: '---------------------------------------------------------' }}
                </td>
            </tr>
            <tr>
                <td style="padding: 4px 6px; font-size: 8.5pt;" colspan="2">
                    <strong>R.F.C.:</strong> {{ $sale->customer_rfc ?: '-------------------------' }}
                </td>
            </tr>
        </table>

        {{-- TABLA CANT / DESCRIPCIÓN / P. UNIT / IMPORTE (PRODUCTO ÚNICO) --}}
        <table class="nota-grid-table">
            <thead>
                <tr>
                    <th style="width: 8%;">CANT.</th>
                    <th style="width: 58%;">DESCRIPCIÓN</th>
                    <th style="width: 17%;">P. UNIT.</th>
                    <th style="width: 17%;">IMPORTE</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="text-align: center; font-weight: bold; vertical-align: middle; padding: 10px 6px; font-size: 9pt;">1</td>
                    <td style="vertical-align: top; padding: 10px 8px; font-size: 8.5pt; line-height: 1.45;">
                        <strong style="font-size: 9pt; color: #000;">{{ $sale->device?->brand?->name }} {{ $sale->device?->model }} {{ $sale->device?->color }} {{ $sale->device?->storage ? 'con ' . $sale->device->storage : '' }} de contado</strong><br>
                        <strong>Imei:</strong> {{ $sale->device?->imei ?? 'N/D' }}<br>
                        <span style="font-style: italic; color: #333;">({{ $sale->warranty_text ?: '1 Mes de garantia' }})</span>
                    </td>
                    <td style="text-align: right; font-weight: bold; vertical-align: middle; padding: 10px 8px; font-size: 9pt;">
                        ${{ number_format($sale->price, 2) }}
                    </td>
                    <td style="text-align: right; font-weight: bold; vertical-align: middle; padding: 10px 8px; font-size: 9pt;">
                        ${{ number_format($sale->price, 2) }}
                    </td>
                </tr>
            </tbody>
        </table>

        {{-- TOTALES E IMPORTE CON LETRA --}}
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
            <tr>
                <td style="width: 65%; vertical-align: middle; padding-right: 12px;">
                    <div style="font-size: 7.5pt; font-weight: bold; text-transform: uppercase; color: #333;">IMPORTE TOTAL CON LETRA:</div>
                    <div style="font-size: 8.5pt; font-weight: bold; color: #000; border-bottom: 1px solid #000; padding: 2px 0;">
                        {{ $sale->price_in_words }}
                    </div>
                    <div style="font-size: 7.5pt; margin-top: 4px; color: #333;">
                        <strong>MÉTODO DE PAGO:</strong> {{ strtoupper($sale->payment_method ?: 'EFECTIVO') }}
                    </div>
                </td>
                <td style="width: 35%; vertical-align: top;">
                    <table style="width: 100%; border: 1.5px solid #000; border-collapse: collapse; background-color: #fafafa;">
                        <tr>
                            <td style="text-align: center; font-size: 7.5pt; font-weight: bold; border-bottom: 1px solid #000; padding: 2px;">TOTAL</td>
                        </tr>
                        <tr>
                            <td style="text-align: center; font-size: 13pt; font-weight: 900; color: #000; padding: 4px;">
                                ${{ number_format($sale->price, 2) }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        {{-- SECCIÓN DE NOTAS Y OBSERVACIONES (SI EXISTEN REGISTRADAS) --}}
        @if($sale->notes && $sale->notes->count() > 0)
            <div class="notes-box">
                <div class="notes-title">Notas y Observaciones de la Venta</div>
                @foreach($sale->notes as $note)
                    <div class="note-item">
                        <strong>• {{ $note->created_at ? $note->created_at->format('d/m/Y H:i') : '' }} ({{ $note->user?->name ?? 'Sistema' }}):</strong> {{ $note->note }}
                    </div>
                @endforeach
            </div>
        @endif

        {{-- POLÍTICAS Y CONDICIONES DE GARANTÍA AL CONTADO --}}
        <div class="clausulas-box" style="margin-top: 4px; margin-bottom: 10px;">
            <div class="clausulas-title">Términos, Condiciones y Políticas de Garantía (Venta de Contado)</div>
            <ul class="clausulas-list" style="font-size: 7.5pt; line-height: 1.35;">
                <li>La garantía otorgada ({{ $sale->warranty_text ?: '1 Mes de garantia' }}) ampara exclusivamente defectos de fábrica y funcionamiento interno del equipo.</li>
                <li>La garantía no será válida si el dispositivo presenta signos de mal uso, caídas, pantalla rota o estrellada, humedad o ingreso de líquidos, sello de seguridad violado o modificaciones no autorizadas en el software.</li>
                <li>Es indispensable presentar este comprobante original de compra junto con el equipo para hacer válida cualquier revisión técnica o garantía en tienda.</li>
                <li>Anote y conserve su cuenta (Google/iCloud) y contraseñas. El bloqueo por olvido de contraseñas no está cubierto por la garantía.</li>
            </ul>
        </div>

        {{-- FIRMAS --}}
        <table class="firmas-table" style="margin-top: 12px;">
            <tr>
                <td class="firma-col">
                    <div class="firma-space" style="height: 40px;"></div>
                    <div class="firma-line"></div>
                    <div class="firma-role">FIRMA DEL CLIENTE / TITULAR</div>
                    <div class="firma-name">{{ $sale->customer_name ?: 'Acepto de conformidad' }}</div>
                </td>
                <td class="firma-spacer"></td>
                <td class="firma-col">
                    <div class="firma-space" style="height: 40px;"></div>
                    <div class="firma-line"></div>
                    <div class="firma-role">VENDEDOR / SUCURSAL</div>
                    <div class="firma-name">{{ $sale->seller_display_name }} ({{ $sale->branch?->name ?? 'ZYA' }})</div>
                </td>
            </tr>
        </table>

    @else
        {{-- ========================================== --}}
        {{-- FORMATO PARA VENTA A CRÉDITO / FINANCIERA  --}}
        {{-- ========================================== --}}

        {{-- TABLA DETALLES DEL EQUIPO Y FINANCIAMIENTO --}}
        <table class="device-table">
            <thead>
                <tr>
                    <th style="width:18%;">MODELO / EQUIPO</th>
                    <th style="width:17%;">IMEI</th>
                    <th style="width:11%;">TAG / DEVICE ID</th>
                    <th style="width:9%;">ENGANCHE</th>
                    <th style="width:9%;">ENGANCHE CON DESCUENTO</th>
                    <th style="width:8%;">ABONO</th>
                    <th style="width:8%;">PLAZO</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>{{ $sale->device?->brand?->name }} {{ $sale->device?->model }}</strong>
                        @if($sale->device?->storage || $sale->device?->color)
                            <br><span style="font-size:7pt; color:#555;">{{ trim(($sale->device?->color ?? '') . ' ' . ($sale->device?->storage ?? '')) }}</span>
                        @endif
                    </td>
                    <td style="font-weight:bold;">{{ $sale->device?->imei ?? 'N/D' }}</td>
                    <td>{{ $sale->tag_contrato ?: 'N/D' }}</td>
                    <td>${{ number_format($sale->down_payment, 2) }}</td>
                    <td>{{ $sale->enganche_descuento ? '$'.number_format($sale->enganche_descuento, 2) : '-' }}</td>
                    <td>{{ $sale->abono_semanal ? '$'.number_format($sale->abono_semanal, 2) : '-' }}</td>
                    <td>{{ $sale->term_weeks ? $sale->term_weeks . ' sem.' : ($sale->term_months ? $sale->term_months . ' mes.' : '-') }}</td>
                </tr>
            </tbody>
        </table>

        {{-- DESGLOSE DE DATOS: CLIENTE Y REFERENCIAS (2 COLUMNAS) --}}
        <table class="info-table">
            <tr>
                {{-- Columna 1: Datos del Cliente --}}
                <td class="info-card">
                    <div class="info-title">1. Datos del Cliente / Titular</div>
                    <div class="field-row"><span class="field-label">Nombre del Cliente:</span> <span class="field-value">{{ $sale->customer_name ?: 'Sin registrar' }}</span></div>
                    <div class="field-row"><span class="field-label">Dirección:</span> <span class="field-value">{{ $sale->customer_address ?: 'No especificada' }}</span></div>
                    <div class="field-row"><span class="field-label">Teléfono Celular:</span> <span class="field-value">{{ $sale->customer_phone ?: 'Sin teléfono' }}</span></div>
                    <div class="field-row"><span class="field-label">Chip Ingresado:</span> <span class="field-value">{{ $sale->customer_chip ?: 'N/D' }}</span></div>
                    <div class="field-row"><span class="field-label">Correo Electrónico:</span> <span class="field-value">{{ $sale->customer_email ?: 'N/D' }}</span></div>
                    <div class="field-row"><span class="field-label">Facebook:</span> <span class="field-value">{{ $sale->customer_facebook ?: 'N/D' }}</span></div>
                </td>

                {{-- Columna 2: Referencias y Datos de Venta --}}
                <td class="info-card">
                    <div class="info-title">2. Referencias Personales y Operación</div>
                    <div class="field-row"><span class="field-label">Referencia #1:</span> <span class="field-value">{{ $sale->ref1_name ?: 'N/D' }} {{ $sale->ref1_phone ? '— Tel: '.$sale->ref1_phone : '' }}</span></div>
                    <div class="field-row"><span class="field-label">Referencia #2:</span> <span class="field-value">{{ $sale->ref2_name ?: 'N/D' }} {{ $sale->ref2_phone ? '— Tel: '.$sale->ref2_phone : '' }}</span></div>
                    <div class="field-row"><span class="field-label">Referencia #3:</span> <span class="field-value">{{ $sale->ref3_name ?: 'N/D' }} {{ $sale->ref3_phone ? '— Tel: '.$sale->ref3_phone : '' }}</span></div>
                    <div class="field-row"><span class="field-label">Vendedor Asignado:</span> <span class="field-value"><strong>{{ $sale->seller_display_name }}</strong></span></div>
                    <div class="field-row"><span class="field-label">Sucursal de Venta:</span> <span class="field-value">{{ $sale->branch?->name ?? 'ZYA' }}</span></div>
                    <div class="field-row"><span class="field-label">Código de Venta:</span> <span class="field-value">{{ $sale->sale_code }}</span></div>
                </td>
            </tr>
        </table>

        {{-- SECCIÓN DE NOTAS Y OBSERVACIONES (SI EXISTEN REGISTRADAS) --}}
        @if($sale->notes && $sale->notes->count() > 0)
            <div class="notes-box">
                <div class="notes-title">Notas y Observaciones de la Venta</div>
                @foreach($sale->notes as $note)
                    <div class="note-item">
                        <strong>• {{ $note->created_at ? $note->created_at->format('d/m/Y H:i') : '' }} ({{ $note->user?->name ?? 'Sistema' }}):</strong> {{ $note->note }}
                    </div>
                @endforeach
            </div>
        @endif

        {{-- CLÁUSULAS, CONDICIONES Y POLÍTICAS CRÉDITO --}}
        <div class="clausulas-box">
            <div class="clausulas-title">Términos, Condiciones y Políticas de Garantía</div>
            <ul class="clausulas-list">
                <li>El vendedor le proporcionará indicación de cómo pagar su crédito ya sea en tienda, Oxxo o transferencia bancaria ingresando a su aplicación.</li>
                <li>El teléfono deberá tener activada la conexión de Wi-Fi o datos móviles permanentemente para recibir notificaciones de pagos. De no conectarse, es posible que su equipo se bloquee. Por favor no desactive los datos móviles.</li>
                <li>Anote y guarde el usuario de Google con el que accede al teléfono (correo y contraseña), así como el PIN o Patrón de bloqueo, ya que son necesarios en caso de restaurar su equipo. El bloqueo por olvido de patrón, contraseña o cuenta Google no se considera parte de la garantía.</li>
                <li>Durante el financiamiento no se debe retirar el SIM de la bandeja. Si se bloquea el equipo por cambio de número, deberá acudir a la tienda para tramitar la autorización correspondiente.</li>
                <li>Si adquirió un SIM nuevo, comuníquese con la compañía telefónica para registrar la línea a su nombre para futuros trámites, reposiciones o aclaraciones.</li>
                <li>Reporte de Robo / Extravío: En caso de robo o extravío, acuda a tienda a realizar el reporte para bloquear el equipo; la cuenta deberá continuar pagándose para conservar un buen historial crediticio.</li>
                <li>El equipo debe recibir su primera carga continua de 4 horas para llegar al 100% y calibrar la batería. No deje cargando en lugares calientes, húmedos, ni utilice cables o cargadores genéricos o dañados.</li>
                <li>El equipo no se puede restablecer a valores de fábrica mientras se encuentre activo el crédito; de lo contrario, se bloqueará por seguridad y será necesario acudir a sucursal.</li>
                <li>La garantía solo es válida si el equipo no presenta caídas, golpes, humedad, pantalla estrellada o alteraciones de software, previa evaluación técnica autorizada.</li>
                <li>En caso de requerir servicio técnico fuera de garantía, se cotizará previamente. Es indispensable continuar al corriente en sus pagos para que el equipo no presente bloqueo durante la revisión.</li>
            </ul>
        </div>

        {{-- FIRMAS --}}
        <table class="firmas-table">
            <tr>
                <td class="firma-col">
                    <div class="firma-space"></div>
                    <div class="firma-line"></div>
                    <div class="firma-role">FIRMA DEL CLIENTE / TITULAR</div>
                    <div class="firma-name">{{ $sale->customer_name ?: 'Acepto de conformidad' }}</div>
                </td>
                <td class="firma-spacer"></td>
                <td class="firma-col">
                    <div class="firma-space"></div>
                    <div class="firma-line"></div>
                    <div class="firma-role">VENDEDOR / REPRESENTANTE</div>
                    <div class="firma-name">{{ $sale->seller_display_name }}</div>
                </td>
            </tr>
        </table>
    @endif

</body>
</html>
