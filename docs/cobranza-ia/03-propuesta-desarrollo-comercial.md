# Documento 3: Propuesta de Desarrollo con Alcances y Objetivos del Sistema de Cobranza ZYA

**Proyecto:** ZYA Cobranza Inteligente (ZYA PayAgent)  
**Naturaleza:** Sistema de Automatización de Cobranza, Negociación de Cartera, Recordatorios y Módulo de Conversaciones en Vivo con Agentes de Inteligencia Artificial por WhatsApp (Arquitectura QR Multi-Sucursal)  
**Versión de Propuesta:** 2.1 (Enfoque Operativo Interno ZYA)  
**Fecha:** Octubre 2026  

---

## 1. Visión Estratégica del Producto

**ZYA PayAgent** transforma la cobranza tradicional (manual, invasiva y costosa) en un proceso **automatizado, empático, medible y altamente persuasivo**, integrado de forma nativa en el ERP de ventas e inventario de ZYA.

El sistema opera bajo un modelo de **cooperación Humano + IA (*Human-in-the-Loop*) y arquitectura Multi-Sucursal por Código QR**:
* **Cada sucursal física vincula su propio teléfono celular de tienda** mediante un escaneo QR en pantalla, permitiendo que los recordatorios y negociaciones salgan desde el número local que los clientes ya conocen y tienen guardado.
* **Costo $0.00 MXN por mensaje:** Al utilizar emulación multi-dispositivo (Evolution API), se eliminan por completo las tarifas por plantilla de Meta y la restricción de la ventana de 24 horas.
* La Inteligencia Artificial maneja el volumen masivo de recordatorios, atiende consultas rutinarias de saldo, resuelve objeciones frecuentes y concreta promesas de pago.
* Cuando un cliente requiere una atención particular, tiene dudas sobre garantías o envía un comprobante, el sistema **abre un espacio de conversación en vivo dentro del Dashboard de ZYA**, permitiendo a cajeros o gestores intervenir, responder en tiempo real por WhatsApp y dar seguimiento continuo al cliente.

---

## 2. Objetivos de Negocio y Retorno de Inversión (ROI)

```
       [40% - 60%]                              [75%]                                   [100%]
Reducción de Cartera Vencida          Ahorro en Horas-Hombre de Cobranza          Control y Atención Híbrida
Recuperación más rápida de abonos     La IA atiende el 80% de consultas;          Los gestores pueden tomar el chat
semanales en los primeros 5 días.     los humanos intervienen en casos clave.     en cualquier momento para cerrar pagos.
```

### 2.1 Objetivos de Negocio Cuantificables
1. **Reducción de Cartera Vencida (30 a 60 días):** Disminución estimada del **45% al 60%** en la tasa de morosidad temprana mediante el esquema de contacto preventivo D-2 y D-0 desde el número de la sucursal.
2. **Optimización del Costo Operativo:** Ahorro de más del **75% del tiempo** que actualmente destinan los gerentes de sucursal y cajeros en mandar mensajes individuales manuales mediante enlaces `wa.me`.
3. **Incremento en Tasa de Contactabilidad Efectiva:** WhatsApp en México ostenta tasas de apertura superiores al **95%**, muy por encima de llamadas telefónicas (rechazadas por identificadores de spam) y SMS tradicionales.

---

## 3. Alcance del Desarrollo (Scope of Work)

### 3.1 Lo que SÍ Incluye el Proyecto (In-Scope)

#### A. Motor de Inteligencia y Segmentación de Cartera
* **Clasificador Automático de Morosidad:** Segmentación dinámica diaria de clientes según días de mora (Preventiva: D-2/D-0; Temprana: D+1 a D+5; Negociación: D+6 a D+14; Escalada a Referencias: D+15+).
* **Consolidador de Adeudos:** Capacidad de extraer y calcular el saldo consolidado entre ventas POS con adeudo (`Order`), ventas por financiera (`FinSale`) y cuotas de tandas para informar al cliente de manera precisa.

#### B. Agente Conversacional Cognitivo (LLM)
* **Atención Empática y Persuasiva:** Modelo conversacional configurado con directivas de cobranza ética (conforme a lineamientos de CONDUSEF / PROFECO y buenas prácticas anti-bloqueo).
* **Manejo de Objeciones:** Capacidad de responder a argumentos comunes (*"me pagan el viernes"*, *"no tengo para el abono completo"*, *"mi teléfono falló"*, *"perdí mi tarjeta"*).
* **Herramientas de Consulta y Registro (*Function Calling*):**
  * `consultar_estado_cuenta`: desglose de cuotas pendientes para informar al cliente.
  * `registrar_promesa_pago`: guarda fecha y monto comprometido en la base de datos, silenciando recordatorios automáticos hasta el vencimiento del compromiso.
  * `solicitar_datos_pago`: entrega CLABE interbancaria, número de tarjeta o convenio de la sucursal correspondiente.
  * `escalar_a_humano`: pausar la IA y transferir a un asesor en caso de disputa, aclaración o solicitud explícita del cliente.

#### C. Recepción de Comprobantes para Revisión Humana
* **Depósito Seguro de Archivos:** Cuando el cliente envía una imagen o PDF de su comprobante por WhatsApp, el sistema lo descarga y asocia automáticamente al expediente de la conversación y del cliente.
* **Alerta Inmediata a Caja:** Notificación al personal de la sucursal indicando: *"El cliente [Nombre] envió comprobante de pago para la orden/crédito [Folio] pendiente de revisión"*.
* **Canalización a Ventanilla:** El cliente recibe confirmación de recepción: *"Hemos recibido tu comprobante. Nuestro equipo administrativo validará el depósito y aplicará tu abono a la brevedad"*.

#### D. Módulo de Conversaciones y Seguimiento en Vivo (*Live Agent Handover*)
Este módulo convierte el Dashboard de ZYA en una **consola de atención y seguimiento tipo WhatsApp Web**, permitiendo a los usuarios del sistema tomar el control de la conversación en múltiples escenarios operativos:

```
[Cliente chatea en WhatsApp] <---> [Microservicio Evolution API] <---> [ZYA Live Chat Inbox]
                                                                          |
                     +----------------------------------------------------+------------------------------------+
                     |                                                                                         |
                     v                                                                                         v
          [MODO IA AUTÓNOMO]                                                                        [MODO GESTOR HUMANO]
      - Responde saldos y fechas                                                                - El asesor toma el control
      - Negocia promesas de pago                                                                - Pausa temporal de la IA
      - Solicita comprobantes                                                                   - Envía mensajes y notas internas
```

##### 1. Escenarios de Uso Contemplados para Seguimiento Humano:
* **Escenario 1: El cliente solicita hablar con una persona:**
  * Si el cliente escribe *"quiero hablar con un asesor"*, *"necesito una aclaración"* o manifiesta inconformidad, la IA responde cordialmente: *"Te comunico de inmediato con un asesor de la sucursal para atenderte"*, se auto-pausa y marca el chat en estado `Requiere Atención`.
* **Escenario 2: Intervención proactiva del cajero o vendedor:**
  * El cajero de la sucursal puede entrar a la bandeja, ver la conversación que el cliente está teniendo con la IA y presionar el botón **"Tomar control del chat"**. La IA se silencia y el cajero continúa dialogando con el cliente directamente por WhatsApp desde su computadora.
* **Escenario 3: Seguimiento posterior al envío de comprobante:**
  * Tras recibir la foto de una transferencia, el cajero valida el dinero en la cuenta bancaria, registra el abono en el sistema mediante [`OrderController::updateDue`](file:///home/zowor/Proyectos/zya/app/Http/Controllers/Dashboard/OrderController.php#L428) y responde por el chat: *"Listo estimado Don Juan, ya quedó asentado tu abono de $500. Tu nuevo saldo es de $1,200. ¡Que tengas excelente día!"*.
* **Escenario 4: Negociaciones complejas o convenios especiales:**
  * En atrasos graves donde se requiere una reestructuración de deuda o descuento de intereses no contemplado por las reglas automáticas de la IA, el gerente de crédito retoma el chat y pacta el convenio directamente.
* **Escenario 5: Reactivación de la IA:**
  * Una vez resuelta la duda o acordado el pago, el usuario presiona **"Reanudar IA"** para que el bot vuelva a encargarse de la rutina de recordatorios y confirmación de promesas futuras.

##### 2. Funcionalidades de la Interfaz del Módulo de Conversaciones:
* **Chat en Tiempo Real sin recargar pantalla:** Integración con **Laravel Reverb / WebSockets** (ya presente en el proyecto) para que los mensajes del cliente y las respuestas aparezcan instantáneamente en la pantalla del usuario.
* **Ficha Lateral del Cliente (Sidebar 360°):** Al seleccionar un chat, la pantalla muestra:
  * Saldo pendiente actual (`due`), lista de órdenes o ventas de financiera activas.
  * Botón de acceso rápido para **"Abonar Pago"** sin salir de la pantalla de chat.
  * Datos de aval y teléfonos de las 3 referencias personales para contacto rápido.
  * Galería de comprobantes recibidos con vista ampliada (zoom).
* **Notas Internas de Seguimiento (Bitácora Privada):**
  * El personal puede escribir notas de color amarillo que solo ven los colaboradores del sistema (no se envían al WhatsApp del cliente), por ejemplo: *"El cliente avisó que vendrá a pagar a las 4:00 PM sucursal Norte"* o *"Hablé con su aval, prometieron liquidar el viernes"*.
* **Comunicación Libre y Permanente (Sin Restricción de 24 Horas):**
  * Al operar con la tecnología de escaneo QR, el asesor o la IA pueden iniciar o retomar la conversación en cualquier momento (día 1, 10, 20 o 45 de mora) sin depender de plantillas HSM pre-aprobadas.
* **Respuestas Rápidas Predefinidas (*Canned Responses*):**
  * Botones con mensajes predefinidos para agilizar la respuesta de los cajeros (cuentas bancarias oficiales, horarios de apertura, ubicación de sucursales).

#### E. Módulo de Promesas de Pago y Reportes
* **Control de Compromisos:** Listado administrativo con fechas prometidas por los clientes para dar seguimiento y verificar su cumplimiento.
* **Métricas y KPIs de Cobranza:** Tasa de lectura, tasa de respuesta, promesas cumplidas y volumen de mensajes atendidos por IA vs humanos.

---

### 3.2 Lo que NO Incluye el Proyecto (Out-of-Scope)
* **Verificación y Asentamiento Automático de Pagos:** La validación de los comprobantes bancarios y el descuento del saldo deudor (`due`) en el sistema ZYA permanecerán bajo revisión y confirmación manual de los cajeros/usuarios autorizados a través del flujo habitual de abonos (`updateDue`), garantizando seguridad total.
* **Pasarela de Cobro Nativa dentro de WhatsApp (WhatsApp Pay México):** Se opera mediante entrega de datos de transferencia (SPEI), convenios o cobro en ventanilla física.
* **Cobranza Judicial / Extrajudicial Presencial:** La solución abarca la gestión digital preventiva, persuasiva y de seguimiento.

---

## 4. Indicadores Clave de Desempeño (KPIs)

| KPI | Fórmula / Métrica | Meta Esperada |
|---|---|---|
| **Tasa de Contactabilidad** | Mensajes entregados / Total enviados | > 96% |
| **Tasa de Respuesta (Engagement)** | Clientes que responden o interactúan / Mensajes leídos | > 40% |
| **Tasa de Cierre de Acuerdos** | Promesas de pago registradas / Clientes en mora contactados | > 35% |
| **Cumplimiento de Promesas (PTP)** | Promesas pagadas en fecha / Total de promesas registradas | > 65% |
| **Tiempo de Atención Humana (First Response Time)** | Minutos desde que el chat pide atención humana hasta respuesta del asesor | < 15 minutos en horario comercial |
| **Tiempo Medio de Recuperación** | Días promedio transcurridos entre vencimiento y pago | Reducción de 8 a 3 días |

---

## 5. Estructura de Costos Operativos de la Solución (Arquitectura QR Multi-Sucursal)

Al operar mediante el microservicio de **WhatsApp Web por Código QR (Evolution API)**, se eliminan por completo los costos por mensaje de Meta:

```
+------------------------------------+---------------------------------------+
| Concepto                           | Costo Unitario Estimado               |
+------------------------------------+---------------------------------------+
| Envío de Mensajes por WhatsApp     | $0.00 MXN (100% Gratis e Ilimitado)   |
| Ventana de Conversación            | $0.00 MXN (Sin restricción de 24 hrs) |
| Consumo de Tokens LLM (IA Mini)    | ~$0.001 USD (~$0.02 MXN por respuesta)|
| Infraestructura Microservicio      | $0.00 MXN (Corre en el mismo VPS ZYA) |
+------------------------------------+---------------------------------------+
```

* **Ejemplo Práctico:**  
  Gestionar a un cliente moroso completo (envío de recordatorio inicial + 4 a 6 mensajes de diálogo y negociación con IA + transferencia a un asesor humano que responde manual y valida su pago) tiene un costo de escasos **$0.005 USD (~$0.10 MXN)** únicamente por consumo de tokens de IA.  
  *Comparativa:* El tiempo de un empleado en piso llamando por teléfono cuesta entre **$15 y $35 MXN por gestión**, representando un **ahorro de más del 98% en costos de cobranza**.
