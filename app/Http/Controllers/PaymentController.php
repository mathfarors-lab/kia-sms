<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $payments) {}

    public function create(Invoice $invoice)
    {
        $this->authorize('payments.record');
        abort_if($invoice->isPaid(), 422, __('payment.already_paid'));
        return view('payments.create', compact('invoice'));
    }

    public function store(Request $request, Invoice $invoice)
    {
        $this->authorize('payments.record');

        $data = $request->validate([
            'amount'    => 'required|numeric|min:0.01',
            'method'    => 'required|in:cash,bank,khqr,aba,wing',
            'reference' => 'nullable|string|max:100',
        ]);

        try {
            $payment = $this->payments->record(
                $invoice,
                $data['amount'],
                $data['method'],
                $data['reference'] ?? null,
                Auth::user()
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()
            ->route('payments.receipt', $payment)
            ->with('success', __('payment.recorded'));
    }

    public function receipt(Payment $payment)
    {
        $this->authorize('payments.record');
        $payment->load(['invoice.student', 'invoice.items', 'receivedBy']);
        return view('payments.receipt', compact('payment'));
    }

    public function receiptPdf(Payment $payment)
    {
        $this->authorize('payments.record');
        $payment->load(['invoice.student', 'invoice.items', 'receivedBy']);

        $pdf = Pdf::loadView('pdf.receipt', compact('payment'))
            ->setPaper('a5', 'portrait')
            ->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false]);

        return $pdf->download("receipt-{$payment->id}.pdf");
    }
}
