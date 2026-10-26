# PROMPT MAESTRO: PLAN DE IMPLEMENTACIÓN TÉCNICA - SISTEMA INTEGRADO DE COBRANZA, PRÉSTAMOS, TANDAS, CAJA MULTI-SUCURSAL Y GIFTS (ZYA ERP)

> **Instrucciones de uso:** Copia y pega este prompt directamente en una nueva sesión de IA o entrégaselo a tu equipo técnico/arquitecto de software para generar el documento de especificación técnica y plan de ejecución paso a paso más riguroso posible.

---

```markdown
Eres un Arquitecto de Software Senior y Tech Lead especializado en Laravel, arquitectura de bases de datos relacionales, sistemas de punto de venta (POS) y ERPs comerciales multi-sucursal.

El proyecto es un ERP desarrollado en **Laravel** con Blade, TailwindCSS, Alpine.js/JavaScript, Spatie Laravel-Permission, DomPDF, PhpSpreadsheet y servicios de flujo de efectivo. Actualmente cuenta con módulos de:
- Financieras (`FinSale`, `FinDevice`, `FinFinanciera`)
- Tandas (`Tanda`, `TandaClient`, `TandaPeriod`)
- Clientes (`Customer`)
- Productos e Inventario (`Product`, `Category`, `Supplier`, `Order`, `OrderDetails`)
- Caja y Cortes (`CashFlow`, `DailyCut`, `CashController`, `CashFlowService`)
- Multi-Sucursal (`Branch`, filtrado por `branch_id`)

Tu objetivo es diseñar un **PLAN DE IMPLEMENTACIÓN TÉCNICA Y ARQUITECTÓNICA COMPLETA** para satisfacer un conjunto crítico de nuevas funcionalidades empresariales.

---

### REQUERIMIENTOS Y ESPECIFICACIONES FUNCIONALES A INTEGRAR:

#### 1. Módulo Centralizado de Cobros y Ventas Unificado (POS Multipropósito)
- Diseñar una interfaz interactiva donde el cajero/operador pueda centralizar y combinar en un único ticket o transacción operaciones de:
  1. **Abono de Financieras** (PayJoy, KrediYa, etc., seleccionando la financiera del catálogo activo).
  2. **Creación o Abono de Préstamos**.
  3. **Abono / Cuotas de Tandas**.
  4. **Venta de Productos de Inventario** (con afectación real de stock).
  5. **Venta de Productos Dinámicos / Libres** (sin registro previo en inventario y sin afectación de stock).
- Permitir interactuar con las 4 secciones simultáneamente antes de confirmar el pago o registrar el movimiento final.

#### 2. Abonos de Financieras (Dispositivos / Equipos)
- Búsqueda y vinculación del abono obligatoriamente por identificador de equipo: **Device_id / TAG / IMEI / Folio de Contrato** (`tag_contrato` en `fin_sales`).
- Impacto y sincronización directa e inmediata en la sección de Clientes/Ventas del módulo de financieras (`fin_sales`, `FinSaleNote` o nueva tabla `fin_sale_payments`).
- **Impacto estricto en Caja (`CashFlow`)**: El abono debe registrarse con desglose de método de pago (Efectivo, Tarjeta, Transferencia), computar en el balance de caja y aplicar al corte diario.
- Incluir un campo opcional para **Comisión de Cobro** (con trazabilidad de si la comisión se retiene en caja o se transfiere).

#### 3. Módulo Integral de Préstamos
- **Relación con Clientes:** Vinculación directa con el módulo de clientes (`customers`).
- **Alerta de Morosidad / Préstamos Activos:** Al seleccionar o registrar un cliente para un nuevo préstamo, el sistema debe consultar en tiempo real y emitir una **alerta visual bloqueante/informativa** si el cliente cuenta con préstamos abiertos o cuotas vencidas.
- **Tipos de Préstamos Parametrizables:** Soportar un catálogo de tipos de préstamo (Personal, Con Garantía/Empeño, Tasa Fija, Cuota Semanal, Quincenal, etc.) con campos dinámicos específicos según el tipo elegido.
- **Regla Financiera de Desembolso vs Caja:**
  - El monto del préstamo otorgado **NO genera egreso automático en caja**.
  - En caja **ÚNICAMENTE impacta el campo "Comisión por Apertura"** (si aplica) como ingreso.
  - Los **Abonos posteriores al préstamo SÍ entran en caja** e impactan el corte.
- **Carga Opcional de Video de Otorgamiento:**
  - Posibilidad de subir video de evidencia al momento de crear el préstamo.
  - **Permiso estricto:** Únicamente usuarios con rol `superadmin` tienen visibilidad y facultad de editar/reemplazar el archivo de video.
- **Tabla de Amortización y Pagos:**
  - Visualización tabular de cada cuota con: Número de pago, Monto programado, **Fecha programada de pago** y **Fecha real en que se realizó el pago**.
- **Ticket de Pago de Préstamo:**
  - Por política de confidencialidad comercial, en el ticket impreso para el cliente **NO debe mostrarse el saldo pendiente**.
- **Cierre Forzado / Manual de Préstamo:**
  - Permitir a un usuario autorizado cerrar/liquidar el préstamo de forma manual, independientemente de si la suma de abonos es inferior (descuento/condonación) o superior (intereses moratorios) al monto convenido, solicitando motivo/justificación obligatoria.
- **Expediente Digital y Pagaré:**
  - Generador de Pagaré legal en PDF prellenado con datos del cliente y condiciones del préstamo para impresión y firma física.
  - Sección de carga del archivo del Pagaré firmado (PDF o imagen escaneada) asociado al expediente del préstamo.

#### 4. Reingeniería y Desacoplamiento del Módulo de Tandas
- Desvincular la obligatoriedad de que los participantes de una tanda existan previamente en la tabla `customers`.
- Implementar la misma lógica ágil utilizada en el módulo de Financieras: captura de información básica del cliente al vuelo (nombre, teléfono, INE, notas) asociada a la tanda sin ensuciar el catálogo maestro de clientes si no se desea.

#### 5. Productos Dinámicos (Sin Afectación de Inventario)
- En el módulo de venta/POS, habilitar la creación de ítems dinámicos con campos:
  - Nombre del producto / concepto.
  - Precio de venta.
  - Marca.
  - Modelo.
  - IMEI / Número de Serie.
  - Estado / Categoría (Nuevo, Usado, Reparado, etc.).
  - Tiempo de garantía.
  - Observaciones.
- Estos productos se venden y facturan sin alterar el stock de la tabla `products`.

#### 6. Sistema de Venta Multi-Pestaña (Tabs Simultáneos)
- Implementar interfaz de múltiples pestañas estilo navegador o POS moderno (Tab 1, Tab 2, Tab 3...), permitiendo al cajero dejar una venta o cobro pausado mientras atiende a otro cliente, pudiendo:
  - Agregar nuevas pestañas.
  - Alternar entre pestañas sin perder el estado del carrito ni los formularios en curso.
  - Eliminar pestañas canceladas.
  - Persistir el estado ante recargas involuntarias mediante almacenamiento en frontend (Alpine.js / Pinia / LocalStorage / Session).

#### 7. Dashboard Analítico y Reportes de Financieras
- Consulta filtrada por rango de fechas de pagos de financieras.
- Exportación nativa a **PDF** (usando DomPDF) y **Excel** (usando PhpSpreadsheet).
- Monitor de procesos de venta y movimientos multiconcepto por día y fecha seleccionada.

#### 8. Auditoría, Pre-Corte, Corte Final y Pagos Mixtos en Caja
- **Pagos Mixtos:** Permitir liquidar cualquier operación combinando métodos de pago (ejemplo: $300 Efectivo + $500 Transferencia + $200 Tarjeta).
- **Desglose Diario por Cliente:** Reporte de corte con agrupación detallada de cada movimiento clasificado por cliente, hora y concepto.
- **Pre-Corte (Corte X):** Arqueo ciego preliminar para validación del cajero a mitad de turno.
- **Corte Final (Corte Z):** Cierre oficial con opción manual y cierre automatizado por comando programado (Laravel Task Scheduling / Cron diario a las 23:59).
- **Apertura Física del Cajón de Dinero:** Integración del pulso de apertura de cajón (código ESC/POS `\x1B\x70\x00\x19\xFA` o protocolo de impresora térmica por JS / WebPrint / QZ Tray) en cada confirmación de cobro.

#### 9. Módulo de Gifts / Promocionales por Proveedor
- Sistema de control de inventario de regalos/promocionales entregados por proveedores (ejemplo: termos, mochilas, audífonos, chips).
- Catálogo de gifts por proveedor (`Supplier` / `FinSupplier`).
- Registro de entradas de stock de gifts.
- Módulo de asignación/entrega de gifts al cliente en ventas de financieras o promociones, con trazabilidad de qué sucursal, empleado y fecha lo entregó.

#### 10. Multi-Sucursal Estricto
- Todo dato, consulta, corte, movimiento de caja, préstamo, tanda y stock debe quedar estrictamente aislado y filtrado por `branch_id`, protegiendo la visibilidad entre sucursales mediante Global Scopes o políticas de autorización (`BranchScope` / Middleware).

---

### ESTRUCTURA REQUERIDA EN EL PLAN DE IMPLEMENTACIÓN:

Por favor genera un documento técnico exhaustivo dividido en las siguientes secciones:

1. **Arquitectura General y Diagrama Entidad-Relación (MER):**
   - Esquema completo de tablas nuevas y modificadas (`loans`, `loan_types`, `loan_payments`, `loan_documents`, `gift_items`, `gift_deliveries`, `daily_cuts`, `fin_sale_payments`, `order_payment_methods`, etc.).
   - Tipos de datos, llaves primarias, foráneas, índices de alto rendimiento y soft-deletes.

2. **Diseño de Modelos, Scopes y Eventos en Laravel:**
   - Modelos Eloquent con relaciones (`hasMany`, `belongsTo`, `morphTo` si aplica).
   - Global Scope de Sucursal (`BranchScope`).
   - Observers o Events para auditoría y apertura de caja.

3. **Arquitectura del Frontend (Módulo Unificado & Multi-Tabs):**
   - Estructura de componentes Blade + Alpine.js / Vanilla JS.
   - Manejo de estado del carrito multiproceso y tabs en el cliente (LocalStorage / Reactive Store).
   - Protocolo de disparo para la apertura física de cajón de dinero (ESC/POS vía driver de impresión térmica o WebSocket local).

4. **Flujos Lógicos Detallados (Step-by-Step):**
   - Flujo de cobro de financiera por TAG/Device_id.
   - Flujo de originación y abono de préstamo (con validación de morosidad previa, video para superadmin, pagaré e impacto exclusivo de comisión en caja).
   - Flujo de pagos mixtos y su consolidación en `CashFlow`.
   - Flujo del Pre-Corte y Corte Z automatizado mediante Scheduler.

5. **Matriz de Permisos y Seguridad (Spatie Permission):**
   - Definición de nuevos permisos (`loans.create`, `loans.video.view`, `loans.close.manual`, `cash.pre_cut`, `cash.final_cut`, `gifts.manage`, etc.) y asignación a roles (Cajero, Gerente Sucursal, Administrador, Superadmin).

6. **Estrategia de Pruebas y Validación:**
   - Casos de prueba unitarios y de integración para validar que la caja cuadre perfectamente con pagos mixtos y que las sucursales no mezclen información.

7. **Cronograma y Fases de Desarrollo Sugeridas:**
   - Desglose ordenado por etapas para minimizar el impacto en la operación actual.
```
