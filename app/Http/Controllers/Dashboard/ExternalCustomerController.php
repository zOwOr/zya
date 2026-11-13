<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ModulePermissionTrait;
use App\Models\Branch;
use App\Models\ExternalCustomer;
use App\Services\ExternalCustomerService;
use Illuminate\Http\Request;

class ExternalCustomerController extends Controller
{
    use ModulePermissionTrait;

    protected ?string $permissionResource = 'external_customer';

    protected array $permissionMapping = [
        'index' => 'read',
        'show' => 'read',
        'create' => 'create',
        'store' => 'create',
        'edit' => 'edit',
        'update' => 'edit',
        'destroy' => 'delete',
        'convert' => 'convert',
    ];

    public function __construct(protected ExternalCustomerService $service)
    {
        $this->initializeModulePermission();
    }

    public function index(Request $request)
    {
        $row = (int) $request->input('row', 15);
        if ($row < 1 || $row > 100000) {
            abort(400, 'El parámetro de filas debe estar entre 1 y 100000.');
        }

        $externalCustomers = ExternalCustomer::with(['branch', 'creator', 'convertedCustomer', 'convertedBy'])
            ->withCount(['posTransactions', 'cashFlows', 'financieraPayments'])
            ->withSum('posTransactions as total_paid_pos', 'total_amount')
            ->filter($request->only(['search', 'status']))
            ->latest('id')
            ->paginate($row)
            ->appends($request->query());

        $stats = [
            'total' => ExternalCustomer::count(),
            'active' => ExternalCustomer::where('status', ExternalCustomer::STATUS_ACTIVE)->count(),
            'converted' => ExternalCustomer::where('status', ExternalCustomer::STATUS_CONVERTED)->count(),
        ];

        return view('external-customers.index', [
            'externalCustomers' => $externalCustomers,
            'stats' => $stats,
            'statusOptions' => ExternalCustomer::TIT_STATUS_OPTIONS,
        ]);
    }

    public function create()
    {
        $branches = Branch::orderBy('name')->get();
        $statusOptions = ExternalCustomer::TIT_STATUS_OPTIONS;

        return view('external-customers.create', compact('branches', 'statusOptions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate(
            ExternalCustomer::validationRules(),
            ExternalCustomer::validationMessages()
        );

        $validated['branch_id'] = ($request->filled('branch_id') && (auth()->user()->isSuperAdmin() || auth()->user()->can('cobros.all_branches')))
            ? (int) $request->input('branch_id')
            : (auth()->user()->branch_id ?? Branch::value('id'));
        $validated['created_by'] = auth()->id();
        $validated['status'] = ExternalCustomer::STATUS_ACTIVE;

        $externalCustomer = ExternalCustomer::create($validated);

        return redirect()
            ->route('external-customers.show', $externalCustomer)
            ->with('success', "Cliente externo '{$externalCustomer->tit_name}' registrado correctamente.");
    }

    public function show(ExternalCustomer $externalCustomer)
    {
        $externalCustomer->load([
            'branch',
            'creator',
            'convertedCustomer',
            'convertedBy',
        ]);

        $transactions = $externalCustomer->posTransactions()
            ->with(['branch', 'user', 'financieraPayments.financiera'])
            ->latest('id')
            ->get();

        $cashFlows = $externalCustomer->cashFlows()
            ->with(['branch', 'user', 'posTransaction'])
            ->latest('id')
            ->get();

        $financieraPayments = $externalCustomer->financieraPayments()
            ->with(['financiera', 'branch', 'user', 'posTransaction'])
            ->latest('payment_date')
            ->latest('id')
            ->get();

        $duplicateInternal = $externalCustomer->isConverted()
            ? null
            : $this->service->findMatchingInternalCustomer($externalCustomer);

        return view('external-customers.show', compact(
            'externalCustomer',
            'transactions',
            'cashFlows',
            'financieraPayments',
            'duplicateInternal'
        ));
    }

    public function edit(ExternalCustomer $externalCustomer)
    {
        $branches = Branch::orderBy('name')->get();
        $statusOptions = ExternalCustomer::TIT_STATUS_OPTIONS;

        return view('external-customers.edit', compact('externalCustomer', 'branches', 'statusOptions'));
    }

    public function update(Request $request, ExternalCustomer $externalCustomer)
    {
        $validated = $request->validate(
            ExternalCustomer::validationRules($externalCustomer->id),
            ExternalCustomer::validationMessages()
        );

        unset($validated['status']);
        if ($externalCustomer->isConverted()) {
            unset($validated['converted_customer_id'], $validated['converted_at'], $validated['converted_by']);
        } elseif ($request->filled('status') && in_array($request->input('status'), [ExternalCustomer::STATUS_ACTIVE, ExternalCustomer::STATUS_INACTIVE], true)) {
            $validated['status'] = $request->input('status');
        }

        $externalCustomer->update($validated);

        return redirect()
            ->route('external-customers.show', $externalCustomer)
            ->with('success', 'Datos del cliente externo actualizados correctamente.');
    }

    public function destroy(ExternalCustomer $externalCustomer)
    {
        $externalCustomer->delete();

        return redirect()
            ->route('external-customers.index')
            ->with('success', 'Cliente externo eliminado del catálogo (se conserva su historial contable en caja y cortes).');
    }

    public function convert(Request $request, ExternalCustomer $externalCustomer)
    {
        $linkExisting = $request->boolean('link_existing');

        try {
            $result = $this->service->promoteToInternal(
                $externalCustomer,
                auth()->id(),
                $linkExisting
            );
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }

        $customer = $result['customer'];

        if ($result['outcome'] === 'already_converted') {
            return redirect()
                ->route('external-customers.show', $externalCustomer)
                ->with('info', "Este cliente externo ya había sido convertido al Cliente Interno #{$customer->id} ({$customer->tit_name}).");
        }

        if ($result['outcome'] === 'duplicate_found') {
            return redirect()
                ->route('external-customers.show', $externalCustomer)
                ->with('error', "Se detectó un Cliente Interno existente con los mismos datos (#{$customer->id} - {$customer->tit_name} / {$customer->tit_phone}). No se creó un duplicado; puedes vincularlo directamente o actualizar el teléfono antes de convertir.")
                ->with('duplicate_internal_match', [
                    'external_id' => $externalCustomer->id,
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->tit_name,
                    'customer_phone' => $customer->tit_phone,
                ]);
        }

        if ($result['outcome'] === 'linked_existing') {
            return redirect()
                ->route('external-customers.show', $externalCustomer)
                ->with('success', "Cliente externo vinculado correctamente al Cliente Interno existente #{$customer->id} ({$customer->tit_name}). Su historial previo se conserva intacto.");
        }

        return redirect()
            ->route('external-customers.show', $externalCustomer)
            ->with('success', "¡Cliente externo promovido exitosamente! Se creó el Cliente Interno #{$customer->id} ({$customer->tit_name}) disponible ahora en el POS estándar, conservando su historial previo.");
    }
}

