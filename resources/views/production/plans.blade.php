<x-layouts.app title="Production Plans">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <a href="{{ route('production.batches') }}" class="btn-secondary">Batches</a>
        @can('production.plan')
            <a href="{{ route('production.plans.create') }}" class="btn-primary">Create plan</a>
        @endcan
    </div>
    <div class="rounded-md border border-slate-200 bg-white shadow-sm">
        <div class="divide-y divide-slate-100">
            @forelse ($plans as $plan)
                <article class="p-5">
                    <p class="font-bold text-ivy-navy">{{ $plan->productVariant->name }}</p>
                    <p class="mt-1 text-sm text-slate-600">{{ $plan->planned_date->format('d M Y') }} · {{ $plan->planned_quantity }} planned · {{ ucfirst($plan->status) }}</p>
                </article>
            @empty
                <div class="px-5 py-12 text-center text-slate-500">No production plans yet.</div>
            @endforelse
        </div>
    </div>
    <div class="mt-5">{{ $plans->links() }}</div>
</x-layouts.app>
