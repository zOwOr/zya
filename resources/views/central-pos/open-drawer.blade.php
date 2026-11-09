<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apertura de Cajón USB - ZYA POS</title>
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
            max-width: 440px;
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
                <i class="fa-solid fa-cash-register mr-2"></i>Cajón de Dinero USB
            </h5>
            <small class="text-white-50">Control de Dispositivo Físico USB - ZYA POS</small>
        </div>

        <div class="p-4">
            <div id="statusAlert" class="alert alert-info py-2 px-3 mb-3 font-size-13">
                <i class="fa-brands fa-usb mr-1"></i> Conexión Directa USB
            </div>

            <p class="font-size-13 text-muted mb-4">
                El cajón de dinero opera automáticamente con cada venta y corte de caja. Usa este control para abrirlo manualmente si necesitas acceder a la gaveta.
            </p>

            <button type="button" class="btn btn-kick-usb mb-3" id="btnKickUsb">
                <i class="fa-solid fa-bolt mr-2"></i>Abrir Cajón Físico (USB)
            </button>

            <small class="text-muted d-block font-size-12 mb-3">
                Solo debes seleccionar el dispositivo USB una sola vez para que quede memorizado para siempre.
            </small>

            <button type="button" class="btn btn-sm btn-outline-secondary font-weight-bold px-4" onclick="window.close()">
                Cerrar
            </button>
        </div>
    </div>

    <script src="{{ asset('assets/js/pos-hardware.js') }}"></script>
    <script>
        const statusAlert = document.getElementById('statusAlert');

        document.getElementById('btnKickUsb').addEventListener('click', async function () {
            if (window.ZyaPosHardware) {
                const ok = await ZyaPosHardware.manualOpenUsbDrawer();
                if (ok) {
                    statusAlert.className = 'alert alert-success py-2 px-3 mb-3 font-size-13';
                    statusAlert.innerHTML = '<i class="fa-solid fa-check-circle mr-1"></i> ¡Cajón Físico Abierto por USB!';
                    setTimeout(() => {
                        try { window.close(); } catch(e) {}
                    }, 1400);
                }
            }
        });

        window.addEventListener('load', async function () {
            if (window.ZyaPosHardware) {
                const ok = await ZyaPosHardware.autoOpenUsbDrawer();
                if (ok) {
                    statusAlert.className = 'alert alert-success py-2 px-3 mb-3 font-size-13';
                    statusAlert.innerHTML = '<i class="fa-solid fa-check-circle mr-1"></i> ¡Cajón Físico Abierto por USB!';
                    setTimeout(() => {
                        try { window.close(); } catch(e) {}
                    }, 1200);
                }
            }
        });
    </script>
</body>
</html>
