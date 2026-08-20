<?php

namespace App\Http\Controllers;

use App\Models\CreditInvoice;
use App\Models\CreditReminder;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\ReminderTemplate;
use App\Services\CreditService;
use Dompdf\Dompdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class CreditController extends Controller
{
    public function invoices(CreditService $service): View
    {
        $service->refreshStatuses();

        return view('credit.invoices', [
            'invoices' => CreditInvoice::query()->with(['customer', 'sale'])->latest()->paginate(20),
        ]);
    }

    public function show(CreditInvoice $invoice): View
    {
        return view('credit.show', [
            'invoice' => $invoice->load(['customer', 'sale.items.productVariant', 'allocations.payment']),
            'templates' => ReminderTemplate::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function payments(): View
    {
        return view('credit.payments', [
            'customers' => Customer::query()->where('outstanding_balance', '>', 0)->orderBy('name')->get(),
            'payments' => CustomerPayment::query()->with('customer')->latest('received_at')->paginate(20),
        ]);
    }

    public function storePayment(Request $request, CreditService $service): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'payment_method' => ['required', 'in:Cash,MoMo'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'momo_network' => ['nullable', 'string', 'max:50'],
            'transaction_reference' => ['nullable', 'string', 'max:120'],
            'received_at' => ['nullable', 'date'],
        ]);

        $payment = $service->recordPayment(Customer::query()->findOrFail($data['customer_id']), $data, $request->user());

        return redirect()->route('credit.payments')->with('status', 'Payment '.$payment->payment_number.' recorded.');
    }

    public function statement(Customer $customer): View
    {
        return view('credit.statement', [
            'customer' => $customer->load(['sales.creditInvoice', 'sales.payments']),
            'payments' => CustomerPayment::query()->where('customer_id', $customer->id)->with('allocations.invoice')->latest('received_at')->get(),
            'invoices' => CreditInvoice::query()->where('customer_id', $customer->id)->latest()->get(),
        ]);
    }

    public function paymentReceipt(CustomerPayment $payment): Response
    {
        $payment->load(['customer', 'allocations.invoice']);
        $html = view('credit.payment-receipt', ['payment' => $payment])->render();
        $pdf = new Dompdf(['isRemoteEnabled' => false]);
        $pdf->loadHtml($html);
        $pdf->setPaper('A4');
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$payment->payment_number.'.pdf"',
        ]);
    }

    public function aging(CreditService $service): View
    {
        $service->refreshStatuses();

        return view('credit.aging', [
            'invoices' => CreditInvoice::query()->with('customer')->where('balance', '>', 0)->orderBy('due_date')->get()->groupBy('status'),
        ]);
    }

    public function reminders(): View
    {
        return view('credit.reminders', [
            'templates' => ReminderTemplate::query()->latest()->get(),
            'reminders' => CreditReminder::query()->with('invoice.customer')->latest()->paginate(20),
        ]);
    }

    public function storeTemplate(Request $request): RedirectResponse
    {
        ReminderTemplate::query()->updateOrCreate(
            ['name' => $request->validate(['name' => ['required', 'string', 'max:255']])['name']],
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'channel' => ['required', 'in:local,sms,whatsapp'],
                'trigger_status' => ['required', 'in:due_soon,due_today,overdue'],
                'body' => ['required', 'string', 'max:2000'],
            ]) + ['is_active' => true]
        );

        return back()->with('status', 'Reminder template saved.');
    }

    public function sendReminder(Request $request, CreditInvoice $invoice, CreditService $service): RedirectResponse
    {
        $data = $request->validate([
            'reminder_template_id' => ['nullable', 'exists:reminder_templates,id'],
        ]);

        $service->sendReminder($invoice, filled($data['reminder_template_id'] ?? null) ? ReminderTemplate::query()->find($data['reminder_template_id']) : null, $request->user());

        return back()->with('status', 'Reminder logged through the local/test channel.');
    }
}
