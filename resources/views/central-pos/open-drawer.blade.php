<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apertura y Configuración de Cajón USB - ZYA POS</title>
    <link rel="stylesheet" href="{{ asset('assets/css/backend-plugin.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/backend.css?v=1.0.0') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            padding: 20px;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .hardware-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e1e7ec;
            box-shadow: 0 4px 14px rgba(0,0,0,0.06);
            width: 100%;
            max-width: 490px;
            overflow: hidden;
            text-align: center;
        }
        .header-banner {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 18px 20px;
        }
        .btn-kick-usb {
            background: #10b981;
            color: #ffffff;
            font-weight: 700;
            font-size: 15px;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);
            transition: all 0.2s ease;
            width: 100%;
            cursor: pointer;
        }
        .btn-kick-usb:hover {
            background: #059669;
            color: #fff;
        }
    </style>
</head>
<body>

    <div class="hardware-card">
        <div class="header-banner">
            <h5 class="mb-1 text-white font-weight-bold">
                <i class="fa-solid fa-cash-register mr-2"></i>Cajón de Dinero (USB / COM)
            </h5>
            <small class="text-white-50">Control y Diagnóstico de Gaveta Física - ZYA POS</small>
        </div>

        <div class="p-4">
            <div id="statusAlert" class="alert alert-info py-2 px-3 mb-3 font-size-13">
                <i class="fa-brands fa-usb mr-1"></i> Verificando navegador y puertos USB-COM...
            </div>

            <div class="form-group text-left bg-light border rounded p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label for="baudRateSelect" class="font-size-12 font-weight-bold text-dark mb-0">
                        <i class="fa-solid fa-gauge-high mr-1 text-primary"></i>Velocidad del Puerto (Baudios):
                    </label>
                    <select id="baudRateSelect" class="form-control form-control-sm" style="width: 140px;">
                        <option value="9600">9600 bps (Estándar)</option>
                        <option value="19200">19200 bps</option>
                        <option value="38400">38400 bps</option>
                        <option value="57600">57600 bps</option>
                        <option value="115200">115200 bps</option>
                    </select>
                </div>
                <small id="portInfoText" class="text-muted d-block font-size-12">
                    Puerto asignado: <strong>Detectando...</strong>
                </small>
            </div>

            <button type="button" class="btn btn-kick-usb mb-2" id="btnKickUsb">
                <i class="fa-solid fa-bolt mr-2"></i>Abrir Cajón Físico (USB)
            </button>

            <button type="button" class="btn btn-outline-primary btn-sm btn-block font-weight-bold mb-3" id="btnSwitchUsbPort">
                <i class="fa-solid fa-plug-circle-check mr-1"></i>Seleccionar / Cambiar Puerto USB-COM
            </button>

            <div class="alert alert-light border text-left font-size-12 text-muted mb-3">
                <strong class="text-dark d-block mb-1"><i class="fa-solid fa-circle-info mr-1 text-info"></i>Notas de conexión por sucursal:</strong>
                <ul class="pl-3 mb-0">
                    <li>El cable RJ11 del cajón debe conectarse al puerto <strong>DK / Cash Drawer</strong> de la impresora térmica.</li>
                    <li>El navegador solo lista puertos <strong>Serial / Virtual COM</strong> bajo <strong>HTTPS</strong> en Chrome o Edge.</li>
                    <li>Si la impresora usa driver estándar USB de Windows (<code>USB001</code>) sin puerto COM, activa <em>"Abrir cajón al imprimir"</em> en Propiedades de Impresora de Windows.</li>
                </ul>
            </div>

            <button type="button" class="btn btn-sm btn-outline-secondary font-weight-bold px-4" onclick="window.close()">
                Cerrar Ventana
            </button>
        </div>
    </div>

    <script src="{{ asset('assets/js/pos-hardware.js') }}?v=20261113"></script>
    <script>
        const statusAlert = document.getElementById('statusAlert');
        const baudSelect = document.getElementById('baudRateSelect');
        const portInfoText = document.getElementById('portInfoText');

        async function refreshHardwareStatus() {
            if (!window.ZyaPosHardware) return;

            baudSelect.value = String(ZyaPosHardware.getBaudRate());

            if (!ZyaPosHardware.isSecureSerialContext()) {
                statusAlert.className = 'alert alert-danger py-2 px-3 mb-3 font-size-13';
                statusAlert.innerHTML = '<i class="fa-solid fa-lock-open mr-1"></i> Requiere <strong>HTTPS</strong> o <code>localhost</code> para controlar USB-COM desde el navegador.';
                portInfoText.innerHTML = 'Puerto asignado: <span class="text-danger">Bloqueado por HTTP no seguro</span>';
                return;
            }

            if (!ZyaPosHardware.isWebSerialSupported()) {
                statusAlert.className = 'alert alert-warning py-2 px-3 mb-3 font-size-13';
                statusAlert.innerHTML = '<i class="fa-solid fa-triangle-exclamation mr-1"></i> Usa <strong>Google Chrome o Microsoft Edge</strong> de escritorio para Web Serial.';
                portInfoText.innerHTML = 'Puerto asignado: <span class="text-muted">No disponible en este navegador</span>';
                return;
            }

            const ports = await ZyaPosHardware.listAuthorizedPorts();
            const pref = ZyaPosHardware.getPreferredPortInfo();
            if (ports.length > 0) {
                const prefLabel = (pref && pref.usbVendorId !== null)
                    ? 'VID:' + pref.usbVendorId.toString(16).toUpperCase() + ' PID:' + (pref.usbProductId !== null ? pref.usbProductId.toString(16).toUpperCase() : '?')
                    : 'Puerto principal';
                statusAlert.className = 'alert alert-info py-2 px-3 mb-3 font-size-13';
                statusAlert.innerHTML = '<i class="fa-brands fa-usb mr-1"></i> ' + ports.length + ' puerto(s) USB-COM autorizado(s) y listo(s).';
                portInfoText.innerHTML = 'Puerto asignado: <strong>' + prefLabel + '</strong> a <strong>' + ZyaPosHardware.getBaudRate() + ' bps</strong>';
            } else {
                statusAlert.className = 'alert alert-warning py-2 px-3 mb-3 font-size-13';
                statusAlert.innerHTML = '<i class="fa-solid fa-plug mr-1"></i> Aún no hay puerto emparejado. Pulsa <strong>Abrir Cajón Físico</strong> o <strong>Seleccionar Puerto</strong>.';
                portInfoText.innerHTML = 'Puerto asignado: <span class="text-warning font-weight-bold">Sin emparejar</span>';
            }
        }

        baudSelect.addEventListener('change', function () {
            if (!window.ZyaPosHardware) return;
            ZyaPosHardware.setBaudRate(this.value);
            refreshHardwareStatus();
        });

        document.getElementById('btnKickUsb').addEventListener('click', async function () {
            if (!window.ZyaPosHardware) return;
            const ok = await ZyaPosHardware.manualOpenUsbDrawer({ silentSuccess: true });
            await refreshHardwareStatus();
            if (ok) {
                statusAlert.className = 'alert alert-success py-2 px-3 mb-3 font-size-13';
                statusAlert.innerHTML = '<i class="fa-solid fa-check-circle mr-1"></i> ¡Cajón Físico Abierto por USB-COM!';
            }
        });

        document.getElementById('btnSwitchUsbPort').addEventListener('click', async function () {
            if (!window.ZyaPosHardware) return;
            const ok = await ZyaPosHardware.manualOpenUsbDrawer({ forceSelect: true, silentSuccess: true });
            await refreshHardwareStatus();
            if (ok) {
                statusAlert.className = 'alert alert-success py-2 px-3 mb-3 font-size-13';
                statusAlert.innerHTML = '<i class="fa-solid fa-check-circle mr-1"></i> ¡Nuevo puerto guardado y cajón abierto!';
            }
        });

        window.addEventListener('load', async function () {
            await refreshHardwareStatus();
            const params = new URLSearchParams(window.location.search);
            if (params.get('auto') === '1' && window.ZyaPosHardware) {
                const ok = await ZyaPosHardware.autoOpenUsbDrawer();
                await refreshHardwareStatus();
                if (ok) {
                    statusAlert.className = 'alert alert-success py-2 px-3 mb-3 font-size-13';
                    statusAlert.innerHTML = '<i class="fa-solid fa-check-circle mr-1"></i> ¡Cajón Físico Abierto por USB-COM!';
                    setTimeout(function () {
                        try { window.close(); } catch (e) {}
                    }, 1200);
                }
            }
        });
    </script>
</body>
</html>
