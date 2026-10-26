# PROMPT MAESTRO: SOLICITUD DE GENERACIÓN DE PDF DE COTIZACIÓN COMERCIAL Y PROPUESTA ECONÓMICA (ZYA ERP)

> **Instrucciones de uso:** Copia y pega este prompt en un LLM o herramienta de generación documental para producir la propuesta comercial formal en formato Markdown y código HTML/CSS imprimible (optimizado para DomPDF / exportación a PDF), lista para ser presentada al cliente o dirección.

---

```markdown
Actúa como un Consultor Comercial y Director de Proyectos de Software Empresarial.

Genera una **PROPUESTA ECONÓMICA Y COTIZACIÓN FORMAL DE DESARROLLO DE SOFTWARE** para el sistema ERP comercial de la empresa **ZYA**, con un diseño corporativo impecable, lenguaje profesional y estructurada para ser convertida directamente a documento PDF (incluye tanto la versión en Markdown ejecutivo como la plantilla HTML/CSS imprimible con clases para DomPDF / print-ready).

---

### DATOS COMERCIALES Y FINANCIEROS OBLIGATORIOS:
- **Nombre del Proyecto:** Módulo Centralizado de Cobranza, Préstamos, Tandas, Caja Avanzada y Control de Gifts Multi-Sucursal
- **Monto Total de Inversión:** $15,000.00 MXN (Quince mil pesos 00/100 M.N.)
- **Condiciones de Pago:** 
  - **50% de Anticipo ($7,500.00 MXN):** Para inicio de análisis, diseño de bases de datos y desarrollo del Sprint 1.
  - **50% de Finiquito ($7,500.00 MXN):** Contra entrega final, pruebas en entorno de pruebas y despliegue en producción.
- **Tiempo de Ejecución y Entrega:** 3 a 4 semanas calendario distribuidas en 4 hitos semanales.
- **Vigencia de la Cotización:** 15 días naturales a partir de la fecha de emisión.
- **Garantía Técnica:** 30 días naturales posteriores a la entrega final contra cualquier defecto o ajuste imputable al desarrollo.

---

### ALCANCE DETALLADO DE LOS SERVICIOS Y MÓDULOS COTIZADOS:

1. **Módulo Centralizado de Cobros y Ventas Unificado:**
   - Panel de control de caja donde el operador centraliza en un único flujo de cobro: abonos de financieras, préstamos, tandas, productos de inventario y productos libres.
   - Posibilidad de interactuar con las 4 secciones simultáneamente antes de formalizar el ticket de cobro.

2. **Gestión de Abonos de Financieras por Equipo (Device ID / TAG):**
   - Búsqueda y afectación del abono vinculada estrictamente al Device ID o TAG del contrato en la sección de Clientes/Ventas de financieras.
   - Integración automática con Caja (`CashFlow`) con registro por método de pago (Efectivo, Tarjeta, Transferencia) y cómputo para corte.
   - Manejo de campo opcional de Comisión por gestión.

3. **Módulo Completo de Préstamos:**
   - Creación y administración de préstamos vinculados al módulo de clientes.
   - Detección automática en tiempo real de clientes con préstamos abiertos o cuotas en mora, con sistema de advertencia al operador.
   - Soporte para distintos tipos de préstamos (personales, con prenda/garantía, tasas fijas, esquemas semanales/quincenales) adaptando los campos requeridos.
   - Regla contable: El desembolso del préstamo NO afecta egreso de caja; únicamente ingresa a caja la **Comisión por Apertura** y los abonos posteriores.
   - Soporte opcional de carga de video de evidencia al originar el préstamo, restringiendo su visualización y edición exclusivamente al rol `superadmin`.
   - Tabla de amortización con seguimiento de fecha programada de pago vs. fecha real de abono.
   - Emisión de ticket de abono con omisión de saldo pendiente por política de privacidad.
   - Cierre manual/anticipado o con quita/mora justificada del préstamo.
   - Módulo de expediente con generación de Pagaré para firma y carga digital del pagaré firmado.

4. **Optimización y Desacoplamiento de Tandas:**
   - Eliminación de la dependencia forzosa de registro formal en el módulo de clientes.
   - Registro ágil de participantes con información básica directamente asociada a la tanda (misma metodología utilizada en Financieras).

5. **Venta de Productos Dinámicos (Sin Afectación de Stock):**
   - Interfaz de captura rápida de artículos no inventariados (Nombre, Precio, Marca, Modelo, IMEI/Serie, Estado/Categoría, Tiempo de Garantía, Observaciones) sin alterar el stock del catálogo `products`.

6. **Interfaz de Venta Multi-Pestaña (Multi-Tabs en Tiempo Real):**
   - Sistema de pestañas dinámicas (agregar, alternar, cerrar) para mantener múltiples procesos de venta o cobro abiertos en paralelo sin pérdida de información.

7. **Dashboard Analítico, Consultas y Reportes:**
   - Panel de consulta de pagos de financieras filtrable por fecha y rango.
   - Exportación de información en formatos **PDF** y **Excel** con formato ejecutivo.
   - Vista de auditoría de movimientos diarios agrupados por proceso de venta.

8. **Reestructuración Integral de Caja, Cortes y Seguridad Física:**
   - Soporte para **Pagos Mixtos** (combinación libre de efectivo, tarjeta y transferencia en un mismo pago).
   - Reporte de movimientos diarios con desglose nominal por cliente.
   - Implementación de **Pre-Corte (Corte X)** y **Corte Final (Corte Z)** manual y programado automáticamente a fin de jornada.
   - Integración de comando para **Apertura Física del Cajón de Dinero** (pulso ESC/POS a través de impresora térmica) al registrar pagos o ventas.

9. **Módulo de Gestión de Gifts y Promocionales de Proveedores:**
   - Control de inventario de regalos y artículos promocionales entregados por proveedores.
   - Registro de entradas, control de existencias y bitácora de entrega a clientes en sucursal.

10. **Aislamiento Multi-Sucursal Estricto:**
    - Arquitectura que garantiza la segregación de datos, inventarios, cajas y reportes por `branch_id`, manteniendo la consistencia en toda la red de sucursales.

---

### ESTRUCTURA DEL DOCUMENTO A GENERAR:

El documento debe presentar un estándar de diseño corporativo impecable con los siguientes apartados:

1. **Carátula / Encabezado Formal:**
   - Logotipo/Isotipo simulado, Folio Único de Cotización (`COT-ZYA-2026-001`), Fecha de Emisión, Validez, Datos del Cliente y Datos del Consultor.
2. **Resumen Ejecutivo del Proyecto:**
   - Propósito, impacto operativo, reducción de mermas y aumento de eficiencia en caja.
3. **Tabla de Desglose de Entregables y Alcance:**
   - Módulos, descripción de funcionalidades y entregables verificables en formato de tabla profesional.
4. **Plan de Trabajo y Cronograma de Entregas (3 a 4 Semanas):**
   - **Semana 1:** Modelo de datos, Módulo de Préstamos (pagarés, tipos, video superadmin) y Desacoplamiento de Tandas.
   - **Semana 2:** Módulo Centralizado de Cobros (financieras con TAG/Device, productos dinámicos y multi-tabs).
   - **Semana 3:** Reingeniería de Caja (pagos mixtos, pre-corte, corte Z automatizado, apertura física de cajón y gifts).
   - **Semana 4:** Multi-sucursal integral, Dashboard analítico con exportaciones PDF/Excel, pruebas de estrés y despliegue final.
5. **Propuesta Económica y Calendario de Pagos:**
   - Resumen financiero de $15,000.00 MXN con tabla de anticipo (50%) y finiquito (50%).
   - Cuentas bancarias para depósito/transferencia (espacio para CLABE/Banco).
6. **Políticas de Garantía, Soporte y Términos:**
   - Cláusulas de confidencialidad de código, 30 días de soporte post-liberación, exclusiones y procedimiento para solicitudes de cambio fuera de alcance.
7. **Espacios para Aceptación y Firmas:**
   - Bloque de firmas de conformidad para el Cliente (ZYA) y el Desarrollador/Consultor.
8. **Plantilla HTML + CSS Inline (Print-Ready):**
   - Código HTML limpio con hoja de estilos optimizada para impresión en papel carta (A4/Letter) y renderizado perfecto con librerías tipo DomPDF / Weasyprint / Navegador `window.print()`.
```
