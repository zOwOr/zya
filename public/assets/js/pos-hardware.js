/**
 * ZYA POS Hardware Manager
 * Apertura Física Automática de Cajón de Dinero mediante Conexión USB Directa
 */
(function (global) {
    'use strict';

    const ZyaPosHardware = {
        // Secuencia binaria estándar ESC/POS para disparo de solenoide de cajón USB
        // ESC p 0 25 250 + ESC p 1 25 250 + DLE DC4 + BEL
        KICK_DRAWER_BYTES: new Uint8Array([
            0x1B, 0x70, 0x00, 0x19, 0xFA,
            0x1B, 0x70, 0x01, 0x19, 0xFA,
            0x10, 0x14, 0x01, 0x00, 0x05,
            0x07, 0x0A
        ]),

        isWebSerialSupported: function () {
            return 'serial' in navigator;
        },

        /**
         * Envía el pulso físico al puerto USB
         */
        _kickPort: async function (port) {
            await port.open({ baudRate: 9600, dataBits: 8, stopBits: 1, parity: 'none' });
            try {
                await port.setSignals({ dataTerminalReady: true, requestToSend: true });
            } catch (e) {}

            const writer = port.writable.getWriter();
            await writer.write(this.KICK_DRAWER_BYTES);
            writer.releaseLock();

            // Esperar duración del pulso electromagnético
            await new Promise(resolve => setTimeout(resolve, 120));

            try {
                await port.setSignals({ dataTerminalReady: false, requestToSend: false });
            } catch (e) {}

            await port.close();
        },

        /**
         * Apertura AUTOMÁTICA de cajón por USB:
         * Se ejecuta silenciosamente en procesos de venta, cobros y cortes de caja.
         */
        autoOpenUsbDrawer: async function () {
            if (!this.isWebSerialSupported()) {
                console.warn('[ZyaPosHardware] WebSerial no soportado en este navegador.');
                return false;
            }

            try {
                const ports = await navigator.serial.getPorts();
                if (ports && ports.length > 0) {
                    await this._kickPort(ports[0]);
                    return true;
                } else {
                    console.info('[ZyaPosHardware] Cajón USB no emparejado aún. Use el botón independiente en Punto de Venta.');
                }
            } catch (err) {
                console.warn('[ZyaPosHardware] Error en apertura automática USB:', err);
            }
            return false;
        },

        /**
         * Apertura MANUAL desde el botón independiente del Punto de Venta:
         * Si ya está emparejado, lo abre de inmediato.
         * Si es la primera vez, solicita al usuario seleccionar el dispositivo USB.
         */
        manualOpenUsbDrawer: async function () {
            if (!this.isWebSerialSupported()) {
                alert('Para controlar el cajón USB se recomienda usar Google Chrome o Microsoft Edge.');
                return false;
            }

            try {
                let ports = await navigator.serial.getPorts();
                let port = (ports && ports.length > 0) ? ports[0] : null;

                if (!port) {
                    // Si aún no está emparejado, el navegador solicita seleccionar el dispositivo USB
                    port = await navigator.serial.requestPort();
                }

                await this._kickPort(port);

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Cajón Abierto!',
                        text: 'Señal física enviada correctamente por USB.',
                        timer: 1500,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                }
                return true;
            } catch (err) {
                if (err.name !== 'NotFoundError') {
                    console.error('[ZyaPosHardware] Error al abrir cajón USB:', err);
                    alert('No se pudo comunicar con el cajón USB: ' + (err.message || err));
                }
                return false;
            }
        }
    };

    global.ZyaPosHardware = ZyaPosHardware;
})(window);
