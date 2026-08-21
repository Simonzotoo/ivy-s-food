<x-layouts.app title="Record Production">
    <form method="POST" action="{{ route('production.batches.store') }}" class="space-y-5 rounded-md border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        <p class="text-sm text-slate-500">Starting a batch reserves the recipe's materials. Come back here to complete it once production finishes.</p>
        <div>
            <label class="form-label" for="recipe_id">Approved recipe <span class="text-ivy-red">*</span></label>
            <select class="form-input" id="recipe_id" name="recipe_id" required>
                @foreach ($recipes as $recipe)
                    <option value="{{ $recipe->id }}">{{ $recipe->productVariant->name }} v{{ $recipe->version }}</option>
                @endforeach
            </select>
            @if ($recipes->isEmpty())
                <p class="mt-1 text-xs text-amber-700">No approved recipes yet - ask a manager to approve one first.</p>
            @endif
        </div>
        <div>
            <label class="form-label" for="production_plan_id">Production plan</label>
            <select class="form-input" id="production_plan_id" name="production_plan_id">
                <option value="">No plan - record this batch directly</option>
                @foreach ($plans as $plan)
                    <option value="{{ $plan->id }}">{{ $plan->productVariant->name }} · {{ $plan->planned_date->format('d M Y') }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid gap-5 md:grid-cols-3">
            <div>
                <label class="form-label" for="production_date">Production date</label>
                <input class="form-input" id="production_date" name="production_date" type="date" value="{{ now()->toDateString() }}" required>
            </div>
            <div>
                <label class="form-label" for="expiry_date">Expiry date</label>
                <input class="form-input" id="expiry_date" name="expiry_date" type="date">
            </div>
            <div>
                <label class="form-label" for="planned_quantity">Planned output</label>
                <input class="form-input" id="planned_quantity" name="planned_quantity" type="number" min="0.001" step="0.001" required>
            </div>
        </div>
        <label class="form-label" for="notes">Notes</label>
        <textarea class="form-input" id="notes" name="notes" rows="3"></textarea>
        <div class="flex justify-end gap-3">
            <a href="{{ route('production.batches') }}" class="btn-secondary">Cancel</a>
            <button class="btn-primary" type="submit">Create batch</button>
        </div>
    </form>
</x-layouts.app>
