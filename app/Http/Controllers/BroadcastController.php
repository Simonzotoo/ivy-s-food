<?php

namespace App\Http\Controllers;

use App\Models\Broadcast;
use App\Models\BroadcastTemplate;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\SaleType;
use App\Services\BroadcastService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BroadcastController extends Controller
{
    public function index(): View
    {
        return view('broadcasts.index', [
            'broadcasts' => Broadcast::query()->with(['creator', 'template'])->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('broadcasts.create', $this->formData());
    }

    public function preview(Request $request, BroadcastService $service): View
    {
        $data = $this->validatedBroadcast($request);

        return view('broadcasts.preview', $this->formData() + [
            'preview' => $data,
            'recipients' => $service->previewRecipients($data),
        ]);
    }

    public function store(Request $request, BroadcastService $service): RedirectResponse
    {
        $broadcast = $service->createBroadcast($this->validatedBroadcast($request), $request->user());

        return redirect()->route('broadcasts.show', $broadcast)->with('status', 'Broadcast '.$broadcast->broadcast_number.' prepared through the local/test channel.');
    }

    public function show(Broadcast $broadcast): View
    {
        return view('broadcasts.show', [
            'broadcast' => $broadcast->load(['creator', 'template', 'recipients.customer.type']),
        ]);
    }

    public function send(Request $request, Broadcast $broadcast, BroadcastService $service): RedirectResponse
    {
        $service->send($broadcast, $request->user());

        return back()->with('status', 'Broadcast logged through the local/test channel.');
    }

    public function templates(): View
    {
        return view('broadcasts.templates', [
            'templates' => BroadcastTemplate::query()->latest()->paginate(20),
        ]);
    }

    public function storeTemplate(Request $request, BroadcastService $service): RedirectResponse
    {
        $service->createTemplate($request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:broadcast_templates,name'],
            'channel' => ['required', 'in:local,sms,whatsapp'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:2000'],
        ]), $request->user());

        return back()->with('status', 'Broadcast template saved.');
    }

    public function consent(): View
    {
        return view('broadcasts.consent', [
            'customers' => Customer::query()->with('type')->orderBy('name')->paginate(30),
        ]);
    }

    public function optOut(Request $request, Customer $customer, BroadcastService $service): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $service->optOut($customer, $data['reason'], $request->user());

        return back()->with('status', $customer->name.' opted out of broadcasts.');
    }

    private function formData(): array
    {
        return [
            'templates' => BroadcastTemplate::query()->where('is_active', true)->orderBy('name')->get(),
            'customerTypes' => CustomerType::query()->orderBy('name')->get(),
            'saleTypes' => SaleType::query()->orderBy('name')->get(),
        ];
    }

    private function validatedBroadcast(Request $request): array
    {
        return $request->validate([
            'broadcast_template_id' => ['nullable', 'exists:broadcast_templates,id'],
            'name' => ['required', 'string', 'max:255'],
            'channel' => ['required', 'in:local,sms,whatsapp'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:2000'],
            'scheduled_for' => ['nullable', 'date'],
            'customer_type_id' => ['nullable', 'exists:customer_types,id'],
            'sale_type_id' => ['nullable', 'exists:sale_types,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'purchase_history' => ['required', 'in:any,has_purchases,no_purchases'],
        ]);
    }
}
