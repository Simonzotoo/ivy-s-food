<x-layouts.app title="Expense Categories">
    <div class="grid gap-6 lg:grid-cols-[360px_1fr]">
        <form method="POST" action="{{ route('expenses.categories.store') }}" class="space-y-4 rounded-md border border-slate-200 bg-white p-5 shadow-sm">
            @csrf
            <div>
                <label class="form-label" for="name">Name</label>
                <input class="form-input" id="name" name="name" value="{{ old('name') }}" required>
            </div>
            <div>
                <label class="form-label" for="description">Description</label>
                <textarea class="form-input" id="description" name="description" rows="3">{{ old('description') }}</textarea>
            </div>
            <label class="inline-flex items-center gap-2 text-sm font-semibold text-ivy-navy">
                <input type="checkbox" name="is_active" value="1" checked>
                Active
            </label>
            <button class="btn-primary w-full" type="submit">Create category</button>
        </form>

        <section class="rounded-md border border-slate-200 bg-white shadow-sm">
            <div class="divide-y divide-slate-100">
                @forelse ($categories as $category)
                    <article class="flex flex-col gap-2 p-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-semibold text-ivy-navy">{{ $category->name }}</p>
                            <p class="text-sm text-slate-500">{{ $category->description ?: 'No description yet.' }}</p>
                        </div>
                        <p class="text-sm text-slate-600">{{ $category->expenses_count }} expenses · {{ $category->is_active ? 'Active' : 'Inactive' }}</p>
                    </article>
                @empty
                    <div class="px-5 py-12 text-center text-slate-500">No expense categories configured.</div>
                @endforelse
            </div>
        </section>
    </div>
</x-layouts.app>
