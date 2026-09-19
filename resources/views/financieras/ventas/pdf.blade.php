<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nota de Venta - {{ $sale->sale_code }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #000;
            padding: 8px 12px;
            line-height: 1.3;
        }
        .header-wrap { width: 100%; border-bottom: 2px solid #000; padding-bottom: 6px; margin-bottom: 6px; }
        .header-left { float: left; width: 52%; }
        .header-right { float: right; width: 46%; text-align: right; }
        .logo-img { height: 80px; width: auto; max-width: 140px; display: block; margin-bottom: 2px; }
        .logo-text { font-size: 28px; font-weight: 900; letter-spacing: -1px; line-height: 1; }
        .tagline { font-size: 8.5px; font-weight: 700; letter-spacing: 1px; margin-top: 2px; }
        .branch-cols { display: table; width: 100%; margin-top: 5px; }
        .branch-col { display: table-cell; vertical-align: top; font-size: 7.5px; line-height: 1.5; padding-right: 6px; }
        .folio-box { border: 1.5px solid #000; display: inline-block; text-align: center; padding: 2px 6px; margin-bottom: 4px; min-width: 120px; }
        .folio-label { font-size: 9px; font-weight: 700; border-bottom: 1px solid #000; letter-spacing: 2px; }
        .folio-value { font-size: 14px; font-weight: 900; color: #cc0000; padding: 2px 0; }
        .fecha-box { border: 1.5px solid #000; display: inline-block; text-align: center; padding: 2px 6px; min-width: 120px; }
        .fecha-label { font-size: 9px; font-weight: 700; border-bottom: 1px solid #000; letter-spacing: 2px; }
        .fecha-sub { display: table; width: 100%; }
        .fecha-sub-cell { display: table-cell; text-align: center; font-size: 8px; padding: 2px 3px; border-right: 1px solid #000; }
        .fecha-sub-cell:last-child { border-right: none; }
        .fecha-sub-val { font-size: 12px; font-weight: 700; min-height: 16px; display: block; }
        .fin-logos { margin-top: 5px; text-align: right; font-size: 8px; }
        .fin-logo-badge { display: inline-block; border: 1.5px solid #000; border-radius: 4px; padding: 2px 6px; font-weight: 900; font-size: 10px; margin-left: 4px; letter-spacing: 0.5px; }
        .badge-payjoy { color: #0066cc; border-color: #0066cc; }
        .badge-krediya { color: #cc0000; border-color: #cc0000; }
        .clearfix::after { content: ""; display: table; clear: both; }
        .device-table { width: 100%; border-collapse: collapse; margin: 8px 0; }
        .device-table th { border: 1.5px solid #000; text-align: center; font-size: 8px; font-weight: 700; padding: 3px 2px; background: #f0f0f0; text-transform: uppercase; }
        .device-table td { border: 1.5px solid #000; text-align: center; font-size: 9.5px; padding: 5px 4px; }
        .field-row { border-bottom: 1px solid #000; margin-bottom: 4px; padding-bottom: 1px; font-size: 9.5px; line-height: 1.6; }
        .field-label { font-weight: 900; font-size: 9px; text-transform: uppercase; display: inline; }
        .field-value { display: inline; padding-left: 4px; }
        .clausulas { margin-top: 8px; font-size: 6.8px; line-height: 1.45; }
        .clausulas ul { padding-left: 10px; }
        .clausulas li { margin-bottom: 2px; }
        .firma-section { margin-top: 55px; width: 100%; page-break-inside: avoid; }
        .firma-left { display: inline-block; width: 42%; text-align: center; }
        .firma-right { display: inline-block; width: 42%; text-align: center; float: right; }
        .firma-line { border-top: 1.5px solid #000; margin-bottom: 4px; }
        .firma-nombre { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
    </style>
</head>
<body>

    {{-- HEADER --}}
    <div class="header-wrap clearfix">
        <div class="header-left">
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
            {{-- Sucursales dinámicas desde la BD --}}
            <div class="branch-cols">
                @forelse($branches as $branch)
                    <div class="branch-col" style="width: {{ count($branches) > 0 ? floor(100 / count($branches)) : 50 }}%;">
                        <strong>{{ $branch->name }}</strong><br>
                        @if($branch->address)
                            {!! nl2br(e($branch->address)) !!}<br>
                        @endif
                        @if($branch->phone)
                            Tel. {{ $branch->phone }}
                        @endif
                    </div>
                @empty
                    {{-- fallback si no hay sucursales cargadas --}}
                    <div class="branch-col" style="width: 100%;">
                        <strong>{{ $sale->branch?->name ?? 'ZYA' }}</strong><br>
                        @if($sale->branch?->address) {!! nl2br(e($sale->branch->address)) !!}<br> @endif
                        @if($sale->branch?->phone) Tel. {{ $sale->branch->phone }} @endif
                    </div>
                @endforelse
            </div>
        </div>
        <div class="header-right">
            <div class="folio-box">
                <div class="folio-label">FOLIO</div>
                <div class="folio-value">N°&nbsp;{{ str_pad($sale->id, 4, '0', STR_PAD_LEFT) }}</div>
            </div>
            <br>
            <div class="fecha-box">
                <div class="fecha-label">FECHA</div>
                <div class="fecha-sub">
                    <div class="fecha-sub-cell">DIA<br><span class="fecha-sub-val">{{ $sale->sale_date ? $sale->sale_date->format('d') : '' }}</span></div>
                    <div class="fecha-sub-cell">MES<br><span class="fecha-sub-val">{{ $sale->sale_date ? $sale->sale_date->format('m') : '' }}</span></div>
                    <div class="fecha-sub-cell">AÑO<br><span class="fecha-sub-val">{{ $sale->sale_date ? $sale->sale_date->format('Y') : '' }}</span></div>
                </div>
            </div>
            <div class="fin-logos">
                @if($sale->financiera)
                    <span class="fin-logo-badge">{{ strtoupper($sale->financiera->name) }}</span>
                @else
                    <span class="fin-logo-badge badge-payjoy">PAYJOY</span>
                    <span class="fin-logo-badge badge-krediya">KrediYA</span>
                @endif
            </div>
        </div>
    </div>

    {{-- TABLA EQUIPO --}}
    <table class="device-table">
        <thead>
            <tr>
                <th style="width:18%;">MODELO</th>
                <th style="width:20%;">IMEI</th>
                <th style="width:14%;">TAG/<br>CONTRATO</th>
                <th style="width:11%;">ENGANCHE</th>
                <th style="width:13%;">ENGANCHE CON<br>DESCUENTO</th>
                <th style="width:11%;">ABONO</th>
                <th style="width:13%;">PLAZO<br>(SEMANAS)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $sale->device?->brand?->name }} {{ $sale->device?->model }}</td>
                <td style="font-weight:700;">{{ $sale->device?->imei ?? '' }}</td>
                <td>{{ $sale->tag_contrato ?? '' }}</td>
                <td>${{ number_format($sale->down_payment, 2) }}</td>
                <td>{{ $sale->enganche_descuento ? '$'.number_format($sale->enganche_descuento, 2) : '' }}</td>
                <td>{{ $sale->abono_semanal ? '$'.number_format($sale->abono_semanal, 2) : '' }}</td>
                <td>{{ $sale->term_weeks ?? '' }}</td>
            </tr>
        </tbody>
    </table>

    {{-- DATOS DEL CLIENTE --}}
    <div style="margin-top:6px;">
        <div class="field-row"><span class="field-label">CLIENTE/TITULAR:</span><span class="field-value">{{ $sale->customer_name }}</span></div>
        <div class="field-row"><span class="field-label">DIRECCIÓN:</span><span class="field-value">{{ $sale->customer_address }}</span></div>
        <div class="field-row"><span class="field-label">CELULAR:</span><span class="field-value">{{ $sale->customer_phone }}</span></div>
        <div class="field-row"><span class="field-label">CHIP INGRESADO:</span><span class="field-value">{{ $sale->customer_chip }}</span></div>
        <div class="field-row"><span class="field-label">CORREO:</span><span class="field-value">{{ $sale->customer_email }}</span></div>
        <div class="field-row"><span class="field-label">FACEBOOK:</span><span class="field-value">{{ $sale->customer_facebook }}</span></div>
        <div class="field-row"><span class="field-label">NOMBRE DE REFERENCIA #1:</span><span class="field-value">{{ $sale->ref1_name }}</span></div>
        <div class="field-row"><span class="field-label">CELULAR:</span><span class="field-value">{{ $sale->ref1_phone }}</span></div>
        <div class="field-row"><span class="field-label">NOMBRE DE REFERENCIA #2:</span><span class="field-value">{{ $sale->ref2_name }}</span></div>
        <div class="field-row"><span class="field-label">CELULAR:</span><span class="field-value">{{ $sale->ref2_phone }}</span></div>
        <div class="field-row"><span class="field-label">NOMBRE DE REFERENCIA #3:</span><span class="field-value">{{ $sale->ref3_name }}</span></div>
        <div class="field-row"><span class="field-label">CELULAR:</span><span class="field-value">{{ $sale->ref3_phone }}</span></div>
    </div>

    {{-- CLÁUSULAS --}}
    <div class="clausulas">
        <ul>
            <li>El vendedor le proporcionará indicación de como pagar su crédito ya sea en tienda, Oxxo o transferencia ingresando a su aplicación.</li>
            <li>El teléfono deberá tener activada la conexión de Wifi o datos móviles permanentemente para recibir notificaciones de pagos. De no conectarse, es posible que su equipo se bloquee. Por favor no desactive los datos móviles.</li>
            <li>Anote y guarde el usuario de Google con el que accede al teléfono (dirección de correo y contraseña) así como el PIN o Patrón de bloqueo, ya que estos son necesarios en caso de restaurar su equipo. El bloqueo por olvido de patrón, código de seguridad o usuario de Google, no se considera parte de la garantía o plan de reparación.</li>
            <li>Durante el crédito no se puede sacar el SIM de bandeja ya que si se bloquea el equipo, para cambiar su número acérquese a tienda para solicitar el cambio.</li>
            <li>Si adquirió un SIM nuevo comuníquese a la compañía para registrar la línea a su nombre para futuras reposiciones o bloqueos.</li>
            <li>Reporte de Robo: Si extravío o le roban su equipo puede venir a tienda a hacer el reporte para que se bloquee y no lo usen, pero su cuenta debe pagarse evitando el mal historial, así mismo reportar su número de SIM a la compañía celular para que se de baja.</li>
            <li>El equipo debe recibir su primera carga de 4 horas para llegar a 100% y optimizarse, no dejar cargando en lugares donde esté muy caliente, hay humedad, revisar la conexión que no tenga corto, no usar cables ni cubos dañados o con alteraciones evidentes.</li>
            <li>El equipo no se puede restablecer a fábrica mientras se esté pagando, de lo contrario se bloqueará y será necesario acudir a tienda.</li>
            <li>La garantía solo es válida sino presenta golpes, humedad o uso anormal del equipo, previo a revisión y evaluación con el proveedor.</li>
            <li>En caso de solicitar plan de reparación software o hardware por invalidación de garantía se cotiza con proveedor o vendedor según su conveniencia, es indispensable seguir realizando los pagos durante su garantía para que el equipo no se encuentre en bloqueo al momento de la revisión por parte de financiera/proveedor/vendedor.</li>
        </ul>
    </div>

    {{-- FIRMAS --}}
    <div class="firma-section clearfix">
        <div class="firma-left">
            <div class="firma-line"></div>
            <div class="firma-nombre">CLIENTE/TITULAR</div>
        </div>
        <div class="firma-right">
            <div class="firma-line"></div>
            <div class="firma-nombre">VENDEDOR</div>
        </div>
    </div>

</body>
</html>
