/**
 * ZYA POS Hardware Manager
 * Control unificado de apertura física de cajón de dinero vía Web Serial (USB-COM / RS-232).
 */
(function (global) {
    'use strict';

    const STORAGE_KEYS = {
        PORT_PREF: 'zya_serial_port_pref',
        BAUD_RATE: 'zya_serial_baud_rate',
        LAST_KICK_AT: 'zya_drawer_last_kick_at',
    };

    const VALID_BAUD_RATES = [9600, 19200, 38400, 57600, 115200];
    const DEFAULT_BAUD_RATE = 9600;
    const KICK_PULSE_MS = 120;
    const LOCAL_DEBOUNCE_MS = 450;

    function safeLocalGet(key, fallback) {
        try {
            const val = global.localStorage ? global.localStorage.getItem(key) : null;
            return val !== null && val !== undefined ? val : fallback;
        } catch (e) {
            return fallback;
        }
    }

    function safeLocalSet(key, val) {
        try {
            if (global.localStorage) {
                global.localStorage.setItem(key, String(val));
            }
        } catch (e) {}
    }

    function safeLocalRemove(key) {
        try {
            if (global.localStorage) {
                global.localStorage.removeItem(key);
            }
        } catch (e) {}
    }

    function safeSessionGet(key, fallback) {
        try {
            const val = global.sessionStorage ? global.sessionStorage.getItem(key) : null;
            return val !== null && val !== undefined ? val : fallback;
        } catch (e) {
            return fallback;
        }
    }

    function safeSessionSet(key, val) {
        try {
            if (global.sessionStorage) {
                global.sessionStorage.setItem(key, String(val));
            }
        } catch (e) {}
    }

    function getParentHardware() {
        try {
            if (
                global.parent &&
                global.parent !== global &&
                global.parent.ZyaPosHardware &&
                global.parent.ZyaPosHardware !== global.ZyaPosHardware
            ) {
                return global.parent.ZyaPosHardware;
            }
        } catch (e) {}
        return null;
    }

    function extractPortMeta(port, index) {
        let info = {};
        try {
            if (port && typeof port.getInfo === 'function') {
                info = port.getInfo() || {};
            }
        } catch (e) {}
        return {
            usbVendorId: typeof info.usbVendorId === 'number' ? info.usbVendorId : null,
            usbProductId: typeof info.usbProductId === 'number' ? info.usbProductId : null,
            index: typeof index === 'number' ? index : 0,
        };
    }

    function formatHardwareError(err) {
        const name = (err && err.name) ? err.name : '';
        const msg = (err && err.message) ? err.message : String(err || '');

        if (name === 'SecurityError') {
            return 'El navegador bloqueó el acceso USB/Serial porque la conexión no es segura (requiere HTTPS o localhost).';
        }
        if (name === 'NetworkError' || /failed to open serial port/i.test(msg)) {
            return 'El puerto COM está ocupado por otro programa o por la cola de impresión de Windows, o no responde.';
        }
        if (name === 'InvalidStateError') {
            return 'El puerto serial quedó bloqueado por una operación previa. Recarga la pestaña o cambia el puerto.';
        }
        return msg || 'Error desconocido de comunicación serial.';
    }

    const KICK_BYTES = new Uint8Array([
        0x1B, 0x70, 0x00, 0x19, 0xFA,
        0x1B, 0x70, 0x01, 0x19, 0xFA,
        0x10, 0x14, 0x01, 0x00, 0x05,
        0x07, 0x0A
    ]);

    const ZyaPosHardware = {
        // Secuencia binaria ESC/POS estándar (pin 0, pin 1, DLE DC4, BEL, LF)
        KICK_DRAWER_BYTES: KICK_BYTES,
        KICK_BYTES: KICK_BYTES,
        VALID_BAUD_RATES: VALID_BAUD_RATES,

        _kickQueue: Promise.resolve(),
        _lastKickAt: 0,
        _lastError: null,

        isWebSerialSupported: function () {
            return Boolean(global.navigator && 'serial' in global.navigator);
        },

        isSecureSerialContext: function () {
            if (typeof global.isSecureContext === 'boolean') {
                return global.isSecureContext;
            }
            const proto = (global.location && global.location.protocol) || '';
            const host = (global.location && global.location.hostname) || '';
            return proto === 'https:' || host === 'localhost' || host === '127.0.0.1';
        },

        getBaudRate: function () {
            const raw = parseInt(safeLocalGet(STORAGE_KEYS.BAUD_RATE, DEFAULT_BAUD_RATE), 10);
            return VALID_BAUD_RATES.indexOf(raw) !== -1 ? raw : DEFAULT_BAUD_RATE;
        },

        setBaudRate: function (baud) {
            const parsed = parseInt(baud, 10);
            const target = VALID_BAUD_RATES.indexOf(parsed) !== -1 ? parsed : DEFAULT_BAUD_RATE;
            safeLocalSet(STORAGE_KEYS.BAUD_RATE, target);
            return target;
        },

        getPreferredPortInfo: function () {
            const raw = safeLocalGet(STORAGE_KEYS.PORT_PREF, '');
            if (!raw) return null;
            try {
                const parsed = JSON.parse(raw);
                return parsed && typeof parsed === 'object' ? parsed : null;
            } catch (e) {
                return null;
            }
        },

        savePreferredPort: function (port, index) {
            if (!port) return;
            safeLocalSet(STORAGE_KEYS.PORT_PREF, JSON.stringify(extractPortMeta(port, index)));
        },

        clearPreferredPort: function () {
            safeLocalRemove(STORAGE_KEYS.PORT_PREF);
        },

        recordCrossPageKick: function () {
            safeSessionSet(STORAGE_KEYS.LAST_KICK_AT, Date.now());
        },

        wasRecentlyKickedAcrossPages: function (windowMs) {
            const threshold = typeof windowMs === 'number' ? windowMs : 4000;
            const last = parseInt(safeSessionGet(STORAGE_KEYS.LAST_KICK_AT, '0'), 10);
            if (!last || isNaN(last)) return false;
            return (Date.now() - last) < threshold;
        },

        _orderCandidatePorts: function (ports) {
            if (!ports || !ports.length) return [];
            const pref = this.getPreferredPortInfo();
            if (!pref) return Array.from(ports);

            const scored = Array.from(ports).map(function (port, idx) {
                const meta = extractPortMeta(port, idx);
                let score = 0;
                if (
                    pref.usbVendorId !== null &&
                    meta.usbVendorId === pref.usbVendorId &&
                    pref.usbProductId === meta.usbProductId
                ) {
                    score += 100;
                } else if (pref.usbVendorId !== null && meta.usbVendorId === pref.usbVendorId) {
                    score += 50;
                }
                if (typeof pref.index === 'number' && idx === pref.index) {
                    score += 10;
                }
                return { port: port, idx: idx, score: score };
            });

            scored.sort(function (a, b) {
                if (b.score !== a.score) return b.score - a.score;
                return a.idx - b.idx;
            });

            return scored.map(function (item) {
                return item.port;
            });
        },

        listAuthorizedPorts: async function () {
            if (!this.isWebSerialSupported()) return [];
            try {
                const ports = await global.navigator.serial.getPorts();
                return this._orderCandidatePorts(ports);
            } catch (e) {
                return [];
            }
        },

        hasPairedPort: async function () {
            const parentHw = getParentHardware();
            if (parentHw && typeof parentHw.hasPairedPort === 'function') {
                return parentHw.hasPairedPort();
            }
            const ports = await this.listAuthorizedPorts();
            return ports.length > 0;
        },

        _runSerialized: function (fn) {
            const run = this._kickQueue.then(fn, fn);
            this._kickQueue = run.then(
                function () {},
                function () {}
            );
            return run;
        },

        waitUntilIdle: function (timeoutMs) {
            const parentHw = getParentHardware();
            if (parentHw && typeof parentHw.waitUntilIdle === 'function') {
                return parentHw.waitUntilIdle(timeoutMs);
            }
            const limit = typeof timeoutMs === 'number' && timeoutMs > 0 ? timeoutMs : 700;
            return Promise.race([
                this._kickQueue.catch(function () {}),
                new Promise(function (resolve) {
                    setTimeout(resolve, limit);
                })
            ]);
        },

        /**
         * Abre el puerto con el baudrate configurado, envía bytes ESC/POS y garantiza el cierre en finally.
         */
        _kickPort: async function (port, options) {
            options = options || {};
            const baudRate = parseInt(options.baudRate || this.getBaudRate(), 10) || DEFAULT_BAUD_RATE;
            let writer = null;

            // Si quedó abierto tras una interrupción previa en la misma pestaña, cerrar primero
            if (port && (port.readable || port.writable)) {
                try {
                    await port.close();
                } catch (preCloseErr) {}
            }

            await port.open({
                baudRate: baudRate,
                dataBits: 8,
                stopBits: 1,
                parity: 'none',
                flowControl: 'none',
            });

            try {
                try {
                    await port.setSignals({ dataTerminalReady: true, requestToSend: true });
                } catch (sigErr) {}

                if (!port.writable) {
                    throw new Error('El puerto serial no expone canal de escritura.');
                }

                writer = port.writable.getWriter();
                await writer.write(this.KICK_DRAWER_BYTES);
            } finally {
                if (writer) {
                    try {
                        writer.releaseLock();
                    } catch (relErr) {}
                }
                await new Promise(function (resolve) {
                    setTimeout(resolve, KICK_PULSE_MS);
                });
                try {
                    await port.setSignals({ dataTerminalReady: false, requestToSend: false });
                } catch (sigOffErr) {}
                try {
                    await port.close();
                } catch (closeErr) {}
            }
        },

        /**
         * Apertura AUTOMÁTICA silenciosa. Prueba el puerto preferido y hace fallback si hay otro COM autorizado.
         */
        autoOpenUsbDrawer: async function (options) {
            options = options || {};
            const parentHw = getParentHardware();
            if (parentHw && typeof parentHw.autoOpenUsbDrawer === 'function') {
                return parentHw.autoOpenUsbDrawer(options);
            }

            if (!this.isWebSerialSupported()) {
                return false;
            }

            const dedupMs = typeof options.dedupWindowMs === 'number' ? options.dedupWindowMs : 0;
            if (dedupMs > 0 && this.wasRecentlyKickedAcrossPages(dedupMs)) {
                return true;
            }

            const now = Date.now();
            if (!options.force && (now - this._lastKickAt) < LOCAL_DEBOUNCE_MS) {
                return true;
            }

            const self = this;
            return this._runSerialized(async function () {
                if (!options.force && (Date.now() - self._lastKickAt) < LOCAL_DEBOUNCE_MS) {
                    return true;
                }

                try {
                    const candidates = await self.listAuthorizedPorts();
                    if (!candidates.length) {
                        self._lastError = 'No hay puerto USB-COM emparejado en este navegador.';
                        return false;
                    }

                    let lastErr = null;
                    for (let i = 0; i < candidates.length; i++) {
                        try {
                            await self._kickPort(candidates[i], options);
                            self._lastKickAt = Date.now();
                            self._lastError = null;
                            self.recordCrossPageKick();
                            return true;
                        } catch (portErr) {
                            lastErr = portErr;
                        }
                    }

                    if (lastErr) {
                        self._lastError = formatHardwareError(lastErr);
                        console.warn('[ZyaPosHardware] Error en apertura automática USB:', lastErr);
                    }
                } catch (err) {
                    self._lastError = formatHardwareError(err);
                    console.warn('[ZyaPosHardware] Error en apertura automática USB:', err);
                }

                return false;
            });
        },

        /**
         * Versión acotada en tiempo para esperar el pulso antes de imprimir o navegar sin bloquear la UI.
         */
        autoOpenUsbDrawerBounded: async function (timeoutMs, options) {
            const limit = typeof timeoutMs === 'number' && timeoutMs > 0 ? timeoutMs : 650;
            try {
                return await Promise.race([
                    this.autoOpenUsbDrawer(options),
                    new Promise(function (resolve) {
                        setTimeout(function () {
                            resolve(false);
                        }, limit);
                    })
                ]);
            } catch (e) {
                return false;
            }
        },

        /**
         * Apertura MANUAL desde POS o Caja de Sucursal.
         * Con { forceSelect: true } permite elegir/cambiar puerto cuando existen varios puertos COM.
         */
        manualOpenUsbDrawer: async function (options) {
            options = options || {};
            const parentHw = getParentHardware();
            if (parentHw && typeof parentHw.manualOpenUsbDrawer === 'function') {
                return parentHw.manualOpenUsbDrawer(options);
            }

            if (!this.isSecureSerialContext()) {
                alert(
                    'El control directo del cajón USB/COM requiere abrir el sistema con HTTPS (o localhost).\n' +
                    'Actualmente estás accediendo por HTTP no seguro y el navegador desactiva Web Serial.'
                );
                return false;
            }

            if (!this.isWebSerialSupported()) {
                alert(
                    'Este navegador no soporta comunicación directa USB-COM (Web Serial).\n' +
                    'Usa Google Chrome o Microsoft Edge en escritorio, o configura la apertura automática desde las Preferencias del driver de impresora en Windows.'
                );
                return false;
            }

            const self = this;
            return this._runSerialized(async function () {
                try {
                    const candidates = await self.listAuthorizedPorts();
                    let port = (!options.forceSelect && candidates.length > 0) ? candidates[0] : null;
                    let selectedViaPrompt = false;

                    if (!port) {
                        port = await global.navigator.serial.requestPort();
                        selectedViaPrompt = true;
                    }

                    if (!port) {
                        return false;
                    }

                    const updatedPorts = await self.listAuthorizedPorts();
                    const chosenIdx = Math.max(0, updatedPorts.indexOf(port));
                    if (selectedViaPrompt) {
                        self.savePreferredPort(port, chosenIdx);
                    }

                    await self._kickPort(port, options);
                    self._lastKickAt = Date.now();
                    self._lastError = null;
                    self.recordCrossPageKick();

                    if (!options.silentSuccess && typeof global.Swal !== 'undefined') {
                        global.Swal.fire({
                            icon: 'success',
                            title: '¡Cajón Abierto!',
                            text: 'Pulso físico enviado correctamente (' + self.getBaudRate() + ' bps).',
                            timer: 1600,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end',
                        });
                    }
                    return true;
                } catch (err) {
                    if (err && err.name === 'NotFoundError') {
                        return false;
                    }
                    const friendly = formatHardwareError(err);
                    self._lastError = friendly;
                    console.error('[ZyaPosHardware] Error al abrir cajón USB:', err);
                    if (!options.silentError) {
                        alert(
                            'No se pudo abrir el cajón por USB-COM: ' + friendly + '\n\n' +
                            'Tip: Si la impresora está en puerto USB001 (Windows), activa "Abrir cajón" en las Propiedades de la impresora en Windows o habilita su puerto Virtual COM y vuelve a seleccionarlo.'
                        );
                    }
                    return false;
                }
            });
        }
    };

    global.ZyaPosHardware = ZyaPosHardware;
})(window);
