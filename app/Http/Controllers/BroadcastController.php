<?php

namespace App\Http\Controllers;

use App\Models\CustomerType;
use App\Models\SaleType;
use App\Services\BroadcastService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BroadcastController extends Controller
{
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
        $data = $this->validatedBroadcast($request);
        $channels = $data['channels'];
        $summaries = [];

        foreach ($channels as $channel) {
            $broadcast = $service->createBroadcast(['channel' => $channel] + $data, $request->user());

            $summaries[] = $broadcast->status === 'scheduled'
                ? strtoupper($channel).': scheduled for '.$broadcast->scheduled_for->format('d M Y H:i').'.'
                : strtoupper($channel).": {$broadcast->total_logged} sent".($broadcast->total_failed ? ", {$broadcast->total_failed} failed" : '').($broadcast->total_skipped ? ", {$broadcast->total_skipped} skipped" : '').'.';
        }

        return redirect()->route('broadcasts.create')->with('status', implode(' ', $summaries));
    }

    private function formData(): array
    {
        return [
            'customerTypes' => CustomerType::query()->orderBy('name')->get(),
            'saleTypes' => SaleType::query()->orderBy('name')->get(),
        ];
    }

    private function validatedBroadcast(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => ['in:sms,email'],
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
