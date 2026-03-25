<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Payment::class);

        $perPage = min($request->integer('per_page', 15), 50);

        $payments = Payment::with([
            'order:id,user_id,event_id,status',
            'order.user:id,name,email',
            'order.event:id,title',
        ])
            ->latest()
            ->paginate($perPage);

        return response()->json($payments);
    }

    public function show(Payment $payment): JsonResponse
    {
        $this->authorize('view', $payment);

        return response()->json([
            'payment' => $payment->load(['order.user:id,name,email', 'order.event:id,title']),
        ]);
    }
}
