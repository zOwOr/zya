<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ModulePermissionTrait;
use App\Services\FinBitacoraService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class FinBitacoraController extends Controller
{
    use ModulePermissionTrait;

    protected ?string $permissionResource = 'financieras.bitacora';

    protected array $permissionMapping = [
        'index' => 'read',
        'exportPdf' => 'export_pdf',
        'exportExcel' => 'export_excel',
    ];

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $action = $request->route()->getActionMethod();
            $user = auth()->user();

            if ($action === 'index') {
                $allowed = $user && (
                    $user->isSuperAdmin()
                    || $user->can('financieras.bitacora.read')
                    || $user->can('financieras.read')
                    || $user->can('financieras.ventas.read')
                );
                if (!$allowed) {
                    abort(403);
                }
                return $next($request);
            }

            $permission = $this->permissionForAction($action);
            if ($permission && !$user?->can($permission)) {
                abort(403);
            }

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $bitacora = FinBitacoraService::build($request, [
            'prefix' => '',
            'paginate' => true,
            'per_page' => 20,
            'history_permissions' => ['financieras.filter_all_dates'],
        ]);

        return view('financieras.bitacora.index', compact('bitacora'));
    }

    public function exportPdf(Request $request)
    {
        $prefix = $request->filled('prefix') ? (string) $request->input('prefix') : '';
        $bitacora = FinBitacoraService::build($request, [
            'prefix' => $prefix,
            'paginate' => false,
            'history_permissions' => ['financieras.filter_all_dates', 'dashboard.filter_all_dates'],
        ]);

        $pdf = Pdf::loadView('financieras.bitacora.pdf', compact('bitacora'))
            ->setPaper('letter', 'landscape');

        return $pdf->download("Bitacora_Financieras_Abonos_Enganches_{$bitacora['fileSuffix']}.pdf");
    }

    public function exportExcel(Request $request)
    {
        $prefix = $request->filled('prefix') ? (string) $request->input('prefix') : '';
        $bitacora = FinBitacoraService::build($request, [
            'prefix' => $prefix,
            'paginate' => false,
            'history_permissions' => ['financieras.filter_all_dates', 'dashboard.filter_all_dates'],
        ]);

        $spreadsheet = FinBitacoraService::buildSpreadsheet($bitacora);
        $tempFile = tempnam(sys_get_temp_dir(), 'fin_bitacora_xlsx_');
        (new Xlsx($spreadsheet))->save($tempFile);

        return response()
            ->download($tempFile, "Bitacora_Financieras_Abonos_Enganches_{$bitacora['fileSuffix']}.xlsx")
            ->deleteFileAfterSend(true);
    }
}
