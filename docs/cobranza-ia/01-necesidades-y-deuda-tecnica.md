# Documento 1: Necesidades y Deuda Técnica a Resolver Antes de la Integración

**Proyecto:** ZYA — Sistema de Control de Ventas, Inventario y Financiamiento  
**Módulo a Desarrollar:** Agentes de Inteligencia Artificial para Cobranza por WhatsApp (Meta Cloud API)  
**Versión de Documentación:** 1.0  
**Fecha:** Septiembre 2026  

---

## 1. Resumen Ejecutivo del Diagnóstico

El sistema ZYA cuenta con una base sólida en **Laravel 10**, arquitectura MVC, gestión de permisos mediante `spatie/laravel-permission` y control detallado de clientes (`Customer`), ventas POS (`Order`), ventas a crédito (`FinSale`) y tandas (`Tanda`).

Sin embargo, para poder integrar un ecosistema automatizado de **Agentes de IA y la API Oficial de Meta (WhatsApp Business Cloud API)** de manera estable, escalable y sin bloquear las operaciones de las sucursales, existen **deudas técnicas críticas y vacíos estructurales** que deben corregirse antes de desplegar la integración.

---

## 2. Matriz de Deuda Técnica Crítica

| # | Área | Situación Actual en el Repositorio | Riesgo Operativo / Técnico | Acción Requerida |
|---|---|---|---|---|
| **1** | **Manejo de Colas (Queues)** | En el archivo `.env`, `QUEUE_CONNECTION=sync`. | **Bloqueo de UI (Fatal):** Las peticiones a Meta Graph API y a modelos LLM demoran entre 800ms y 4,000ms. Si se procesan en modo síncrono, la aplicación sufrirá cuelgues, errores de timeout HTTP 504 y pésima experiencia de usuario. | Migrar a `QUEUE_CONNECTION=database` (o `redis`), configurar workers supervisados (`systemd` o `supervisor`). |
| **2** | **Automatización y Scheduler** | `app/Console/Kernel.php` no tiene tareas programadas (`schedule()` vacío). | **Falta de Proactividad:** No existe ningún proceso en segundo plano que evalúe diariamente quién debe pagar hoy, quién debe mañana o quién cayó en mora hace 3 días. | Configurar el cron del servidor y programar comandos Artisan diarios (`cobranza:evaluar-vencimientos`). |
| **3** | **Estructura de Fechas de Vencimiento** | La tabla `orders` solo almacena `order_date`, `pay`, `due` y `payment_status`. No tiene `next_due_date` ni frecuencia de pago. | **Ambigüedad en el cálculo de mora:** El sistema sabe *cuánto* debe el cliente, pero no *cuándo* vencía su abono exacto en las órdenes regulares. | Agregar columnas de fecha de vencimiento y generar un esquema de cuotas o amortizaciones. |
| **4** | **Fragmentación del Modelo de Créditos** | La deuda está dispersa en 3 módulos no comunicados: `orders` (saldo `due`), `fin_sales` (abono semanal en financiera) y `tandas` (períodos). | Duplicidad de lógica al consultar el estado de cuenta y riesgo de que el Agente IA informe un saldo incompleto. | Crear un servicio unificador (`DebtService` / `DebtQueryInterface`) que devuelva el balance consolidado del cliente. |
| **5** | **Formato de Números Telefónicos** | Los campos `tit_phone`, `customer_phone` y teléfonos de referencias son texto plano libre (10 dígitos). En vistas se concatena a mano `wa.me/52...`. | **Rebotes en Meta API:** Meta rechaza peticiones a números sin formato E.164 internacional estricto o con caracteres residuales (`-`, espacios, paréntesis). | Crear Cast/Mutator en Eloquent para estandarizar a formato E.164 (`+521XXXXXXXXXX` o `+52XXXXXXXXXX`) y script de migración para sanitizar datos existentes. |
| **6** | **Capa API y Seguridad de Webhooks** | `routes/api.php` solo cuenta con la ruta por defecto de Sanctum (`/user`). | No hay receptor para los mensajes entrantes de Meta ni validación criptográfica de peticiones. | Crear endpoints con validación de tokens de verificación y firma de cabecera `X-Hub-Signature-256`. |
| **7** | **Inexistencia de Bitácora de Contacto** | No existen tablas para almacenar si ya se contactó a un cliente, qué respondió o cuántos intentos van en el mes. | Riesgo de enviar spam accidental al mismo cliente 10 veces en un día, provocando reportes y baneo de la línea en Meta. | Diseñar e implementar el modelo relacional de conversaciones, mensajes y promesas de pago con límites de frecuencia (*rate limiting*). |
| **8** | **Cumplimiento Legal y Consentimiento** | No se recaba formalmente el *Opt-In* de mensajería comercial ni se validan horarios permitidos de cobranza. | Sanciones legales por PROFECO/CONDUSEF en México y riesgo de degradación de calidad del número telefónico en Meta. | Incorporar checkbox de consentimiento en notas de venta y restringir el motor de envíos a la franja de 08:00 a 20:59 hrs. |

---

## 3. Plan de Acción Técnico Detallado

### Tarea 1: Migración de Colas a Asíncronas (Database o Redis)

**Paso 1:** Generar las tablas para colas en Laravel:
```bash
php artisan queue:table
php artisan queue:failed-table
php artisan migrate
```

**Paso 2:** Modificar `.env`:
```env
QUEUE_CONNECTION=database
```

**Paso 3:** Definir colas prioritarias en la configuración:
* `whatsapp-high`: Mensajes interactivos en vivo y respuestas del Agente IA al cliente.
* `whatsapp-outbound`: Notificaciones masivas de cobranza preventiva disparadas por cron.
* `whatsapp-vision`: Procesamiento pesado de comprobantes de pago e imágenes mediante OCR / Visión.

---

### Tarea 2: Normalización y Sanitización de Números Telefónicos

Los modelos `Customer`, `FinSale` y las tablas de referencias deben garantizar que cualquier teléfono pase a formato **E.164 México**.

**Trait de Formateo a implementar (`app/Models/Traits/HasE164Phone.php`):**
```php
namespace App\Models\Traits;

trait HasE164Phone
{
    public static function formatToE164(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        // Eliminar cualquier caracter no numérico
        $digits = preg_replace('/\D/', '', $phone);

        // Si son 10 dígitos (número estándar mexicano)
        if (strlen($digits) === 10) {
            return '52' . $digits;
        }

        // Si ya incluye 52 pero tiene 12 dígitos (52 + 10 dígitos)
        if (strlen($digits) === 12 && str_starts_with($digits, '52')) {
            return $digits;
        }

        // Si tiene formato antiguo con prefijo celular 1 (52 1 + 10 dígitos = 13 dígitos)
        if (strlen($digits) === 13 && str_starts_with($digits, '521')) {
            return '52' . substr($digits, 3);
        }

        return $digits;
    }
}
```

**Comando de limpieza de datos legados:**
Crear un comando `php artisan data:sanitize-phones` que recorra la tabla `customers` y `fin_sales` normalizando todos los teléfonos registrados hasta la fecha.

---

### Tarea 3: Extensión del Esquema de Vencimientos en Órdenes

Para que el motor de cobranza sepa exactamente a quién notificar, debemos dotar a la tabla `orders` de fechas y estados de vencimiento explícitos.

**Migración recomendada (`add_due_dates_to_orders_table`):**
```php
Schema::table('orders', function (Blueprint $table) {
    $table->date('due_date')->nullable()->after('order_date')
          ->comment('Fecha límite del próximo abono o liquidación');
    $table->enum('due_frequency', ['semanal', 'quincenal', 'mensual', 'pago_unico'])->default('semanal')->after('due_date');
    $table->decimal('installment_amount', 12, 2)->nullable()->after('due_frequency')
          ->comment('Monto pactado por cuota o abono');
    $table->date('last_payment_date')->nullable()->after('pay');
    $table->date('last_reminder_sent_at')->nullable()->after('last_payment_date');
    $table->integer('reminder_count')->default(0)->after('last_reminder_sent_at');
});
```

---

### Tarea 4: Capa de Servicio Unificada de Deuda (`DebtConsolidationService`)

Para que la IA no consulte tablas desvinculadas, se debe construir un servicio central:

```php
namespace App\Services\Cobranza;

class DebtConsolidationService
{
    /**
     * Obtiene el desglose consolidado de adeudos de un cliente por su teléfono o ID
     */
    public function getCustomerDebtSummary(string $phone): array
    {
        $phoneE164 = HasE164Phone::formatToE164($phone);
        
        // 1. Buscar en Clientes POS
        $customer = Customer::where('tit_phone', $phoneE164)
            ->orWhere('alternate_phone', $phoneE164)
            ->first();

        // 2. Buscar ventas POS con saldo pendiente
        $ordersDue = $customer ? Order::where('customer_id', $customer->id)->where('due', '>', 0)->get() : collect();

        // 3. Buscar ventas de Financiera con status activa
        $finSales = FinSale::where(function($q) use ($phoneE164) {
            $q->where('customer_phone', $phoneE164)
              ->orWhere('customer_chip', $phoneE164);
        })->where('status', 'activa')->get();

        return [
            'customer' => $customer,
            'total_pending_amount' => $ordersDue->sum('due'),
            'orders' => $ordersDue,
            'fin_sales' => $finSales,
            'has_due' => ($ordersDue->sum('due') > 0 || $finSales->count() > 0)
        ];
    }
}
```

---

### Tarea 5: Habilitación del Cron de Laravel en el Servidor

En el servidor de producción Linux (Ubuntu/Debian), el demonio de cron debe ejecutar el Scheduler de Laravel cada minuto:

```bash
crontab -e
```
Agregar la línea:
```cron
* * * * * cd /home/zowor/Proyectos/zya && php artisan schedule:run >> /dev/null 2>&1
```

Y en `app/Console/Kernel.php`:
```php
protected function schedule(Schedule $schedule): void
{
    // Ejecutar evaluación de cobranza todos los días a las 9:30 AM (hora CDMX)
    $schedule->command('cobranza:evaluar-vencimientos')
             ->dailyAt('09:30')
             ->timezone('America/Mexico_City')
             ->withoutOverlapping();

    // Re-evaluar promesas de pago vencidas a las 17:00 hrs
    $schedule->command('cobranza:revisar-promesas')
             ->dailyAt('17:00')
             ->timezone('America/Mexico_City');
}
```

---

## 4. Checklist Previo al Despliegue de la API de Meta

- [ ] Base de datos migrada con soporte de colas (`jobs`, `failed_jobs`).
- [ ] Supervisor o servicio systemd ejecutando `php artisan queue:work --queue=whatsapp-high,whatsapp-outbound,default`.
- [ ] Teléfonos de clientes existentes sanitizados a formato E.164.
- [ ] Tablas de órdenes adaptadas con `due_date` e `installment_amount`.
- [ ] Scheduler de Laravel activo en el crontab del servidor.
- [ ] Certificado SSL (HTTPS) válido en el dominio donde responderán los webhooks de Meta.
