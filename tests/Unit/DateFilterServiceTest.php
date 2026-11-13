<?php

namespace Tests\Unit;

use App\Services\DateFilterService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Tests\TestCase;

class DateFilterServiceTest extends TestCase
{
    public function test_user_with_history_permission_can_filter_specific_day_range_and_all_records(): void
    {
        $today = Carbon::parse('2026-11-13');

        // 1. Día específico histórico
        $diaReq = Request::create('/test', 'GET', [
            'filter_type' => 'dia',
            'date' => '2026-01-15',
        ]);
        $dia = DateFilterService::resolve($diaReq, [
            'can_all_dates' => true,
            'today' => $today,
        ]);

        $this->assertSame('dia', $dia['filter_type']);
        $this->assertSame('2026-01-15', $dia['date']);
        $this->assertSame('2026-01-15', $dia['start_date']);
        $this->assertSame('2026-01-15', $dia['end_date']);
        $this->assertFalse($dia['was_restricted']);
        $this->assertSame('2026-01-15', DateFilterService::fileSuffix($dia));
        $this->assertSame('Día: 15/01/2026', DateFilterService::periodLabel($dia));

        // 2. Entre fechas (incluye intercambio automático si start > end)
        $rangoReq = Request::create('/test', 'GET', [
            'filter_type' => 'rango',
            'start_date' => '2026-03-20',
            'end_date' => '2026-03-05',
        ]);
        $rango = DateFilterService::resolve($rangoReq, [
            'can_all_dates' => true,
            'today' => $today,
        ]);

        $this->assertSame('rango', $rango['filter_type']);
        $this->assertSame('2026-03-05', $rango['start_date']);
        $this->assertSame('2026-03-20', $rango['end_date']);
        $this->assertFalse($rango['was_restricted']);
        $this->assertSame('2026-03-05_a_2026-03-20', DateFilterService::fileSuffix($rango));

        // 3. Todos los registros
        $todosReq = Request::create('/test', 'GET', [
            'filter_type' => 'todos',
        ]);
        $todos = DateFilterService::resolve($todosReq, [
            'can_all_dates' => true,
            'today' => $today,
        ]);

        $this->assertSame('todos', $todos['filter_type']);
        $this->assertNull($todos['start_date']);
        $this->assertNull($todos['end_date']);
        $this->assertNull($todos['query_start']);
        $this->assertNull($todos['query_end']);
        $this->assertFalse($todos['was_restricted']);
        $this->assertSame('todos_los_registros', DateFilterService::fileSuffix($todos));
        $this->assertSame('Todos los registros (Histórico completo)', DateFilterService::periodLabel($todos));
    }

    public function test_restricted_user_is_capped_at_two_days_prior_and_cannot_select_todos(): void
    {
        $today = Carbon::parse('2026-11-13'); // Límite: 2026-11-11

        // 1. Día antiguo (> 2 días) se ajusta a 2026-11-11 y marca was_restricted
        $oldDayReq = Request::create('/test', 'GET', [
            'filter_type' => 'dia',
            'date' => '2026-11-01',
        ]);
        $oldDay = DateFilterService::resolve($oldDayReq, [
            'can_all_dates' => false,
            'today' => $today,
        ]);

        $this->assertSame('2026-11-11', $oldDay['min_allowed_date']);
        $this->assertSame('2026-11-11', $oldDay['date']);
        $this->assertSame('2026-11-11', $oldDay['start_date']);
        $this->assertSame('2026-11-11', $oldDay['end_date']);
        $this->assertTrue($oldDay['was_restricted']);

        // 2. Borde exacto (2 días atrás) sí se permite sin marcar restricción
        $edgeReq = Request::create('/test', 'GET', [
            'filter_type' => 'dia',
            'date' => '2026-11-11',
        ]);
        $edge = DateFilterService::resolve($edgeReq, [
            'can_all_dates' => false,
            'today' => $today,
        ]);

        $this->assertSame('2026-11-11', $edge['date']);
        $this->assertFalse($edge['was_restricted']);

        // 3. Todos los registros se degrada a rango [min_allowed_date .. hoy]
        $todosReq = Request::create('/test', 'GET', [
            'filter_type' => 'todos',
        ]);
        $todos = DateFilterService::resolve($todosReq, [
            'can_all_dates' => false,
            'today' => $today,
        ]);

        $this->assertSame('rango', $todos['filter_type']);
        $this->assertSame('2026-11-11', $todos['start_date']);
        $this->assertSame('2026-11-13', $todos['end_date']);
        $this->assertTrue($todos['was_restricted']);

        // 4. Rango con ambas fechas fuera de ventana se recorta a 2026-11-11
        $oldRangeReq = Request::create('/test', 'GET', [
            'filter_type' => 'rango',
            'start_date' => '2026-10-01',
            'end_date' => '2026-11-08',
        ]);
        $oldRange = DateFilterService::resolve($oldRangeReq, [
            'can_all_dates' => false,
            'today' => $today,
        ]);

        $this->assertSame('2026-11-11', $oldRange['start_date']);
        $this->assertSame('2026-11-11', $oldRange['end_date']);
        $this->assertTrue($oldRange['was_restricted']);

        // 5. Carga inicial sin parámetros con default_start_date antiguo recorta silenciosamente (sin alerta)
        $emptyReq = Request::create('/test', 'GET', []);
        $silent = DateFilterService::resolve($emptyReq, [
            'can_all_dates' => false,
            'default_type' => 'todos',
            'default_start_date' => '2026-11-01',
            'default_end_date' => '2026-11-13',
            'today' => $today,
        ]);

        $this->assertSame('rango', $silent['filter_type']);
        $this->assertSame('2026-11-11', $silent['start_date']);
        $this->assertSame('2026-11-13', $silent['end_date']);
        $this->assertFalse($silent['was_restricted']);
    }

    public function test_prefix_isolation_fallback_invalid_date_and_period_aliases(): void
    {
        $today = Carbon::parse('2026-11-13');

        // Prefijo mov_ ignora parámetros fin_ sin prefijo ni viceversa
        $req = Request::create('/test', 'GET', [
            'fin_filter_type' => 'dia',
            'fin_date' => '2026-11-10',
            'mov_filter_type' => 'rango',
            'mov_start_date' => 'invalid-date',
            'mov_end_date' => '2026-11-12',
        ]);

        $mov = DateFilterService::resolve($req, [
            'prefix' => 'mov_',
            'can_all_dates' => true,
            'today' => $today,
        ]);

        $this->assertSame('rango', $mov['filter_type']);
        $this->assertSame('2026-11-12', $mov['start_date']); // invalid start falls back to today (13) then swapped <= 12
        $this->assertSame('2026-11-13', $mov['end_date']);

        // Period aliases (mes / semana) en Gastos cuando no tiene permiso histórico
        $mesReq = Request::create('/test', 'GET', [
            'period' => 'mes',
            'date' => '2026-11-13',
        ]);
        $mes = DateFilterService::resolve($mesReq, [
            'can_all_dates' => false,
            'allow_period_aliases' => true,
            'today' => $today,
        ]);

        $this->assertSame('mes', $mes['filter_type']);
        $this->assertSame('2026-11-11', $mes['start_date']);
        $this->assertSame('2026-11-30', $mes['end_date']);
        $this->assertTrue($mes['was_restricted']);
    }

    public function test_apply_enforces_defensive_min_date_if_todos_reaches_without_permission(): void
    {
        $calls = [];
        $fakeQuery = new class($calls) {
            public array $calls;

            public function __construct(&$calls)
            {
                $this->calls = &$calls;
            }

            public function where($col, $op, $val)
            {
                $this->calls[] = ['where', $col, $op, (string) $val];
                return $this;
            }

            public function whereBetween($col, $range)
            {
                $this->calls[] = ['whereBetween', $col, $range];
                return $this;
            }

            public function whereDate($col, $val)
            {
                $this->calls[] = ['whereDate', $col, $val];
                return $this;
            }
        };

        DateFilterService::apply($fakeQuery, 'order_date', [
            'filter_type' => 'todos',
            'can_all_dates' => false,
            'min_allowed_date' => '2026-11-11',
        ], true);

        $this->assertCount(1, $calls);
        $this->assertSame(['where', 'order_date', '>=', '2026-11-11'], $calls[0]);
    }
}
