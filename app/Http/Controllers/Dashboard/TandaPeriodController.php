<?php 

namespace App\Http\Controllers\Dashboard;
use App\Http\Controllers\Controller;

use App\Models\TandaPeriod;
use Illuminate\Http\Request;

class TandaPeriodController extends Controller
{
    public function updatePayment(Request $request, TandaPeriod $period)
    {
        $data = $request->validate([
            'paid_amount' => 'nullable|numeric|min:0',
            'is_paid' => 'nullable|boolean',
        ]);

        $period->paid_amount = $data['paid_amount'] ?? null;
        $period->is_paid = $data['is_paid'] ?? false;
        $period->paid_date = $period->is_paid ? now() : null;
        $period->save();

        return response()->json(['message' => 'Pago actualizado correctamente']);
    }
}
