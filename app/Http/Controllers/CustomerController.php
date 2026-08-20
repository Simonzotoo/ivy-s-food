<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\SaleType;
use App\Services\CustomerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $customers = Customer::query()
            ->with(['type', 'defaultSaleType'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search');
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('normalized_phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('type'), fn ($query) => $query->where('customer_type_id', $request->integer('type')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('customers.index', [
            'customers' => $customers,
            'types' => CustomerType::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('customers.create', $this->formData());
    }

    public function store(Request $request, CustomerService $service): RedirectResponse
    {
        $customer = $service->create($this->validatedCustomer($request), $request->user());

        return redirect()->route('customers.show', $customer)->with('status', 'Customer created.');
    }

    public function show(Customer $customer): View
    {
        return view('customers.show', [
            'customer' => $customer->load(['type', 'defaultSaleType', 'communications']),
        ]);
    }

    public function edit(Customer $customer): View
    {
        return view('customers.edit', $this->formData() + ['customer' => $customer]);
    }

    public function update(Request $request, Customer $customer, CustomerService $service): RedirectResponse
    {
        $service->update($customer, $this->validatedCustomer($request), $request->user());

        return redirect()->route('customers.show', $customer)->with('status', 'Customer updated.');
    }

    public function logCommunication(Request $request, Customer $customer, CustomerService $service): RedirectResponse
    {
        $service->logCommunication($customer, $request->validate([
            'channel' => ['required', 'in:phone,sms,whatsapp,email,in_person'],
            'direction' => ['required', 'in:inbound,outbound'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
            'occurred_at' => ['nullable', 'date'],
        ]), $request->user());

        return back()->with('status', 'Communication logged.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'types' => CustomerType::query()->orderBy('name')->get(),
            'saleTypes' => SaleType::query()->orderBy('name')->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedCustomer(Request $request): array
    {
        $data = $request->validate([
            'customer_type_id' => ['required', 'exists:customer_types,id'],
            'default_sale_type_id' => ['nullable', 'exists:sale_types,id'],
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'in:active,inactive,suspended'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'communication_consent' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! $request->user()->can('customers.manage_credit')) {
            unset($data['credit_limit']);
        }

        return $data + ['communication_consent' => $request->boolean('communication_consent')];
    }
}
