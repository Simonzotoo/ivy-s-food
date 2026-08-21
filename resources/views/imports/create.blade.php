<x-layouts.app title="Upload: {{ $label }}">
    <div class="mx-auto max-w-2xl rounded-md border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-base font-bold text-ivy-navy">Upload {{ $label }}</h2>
        <p class="mt-1 text-sm text-slate-500">Accepted files: .csv or .xlsx, up to 5 MB. Download the template first if you have not filled one in yet.</p>

        <a class="metric-link mt-2 inline-flex" href="{{ route('imports.template', $type) }}">Download the {{ $label }} template</a>

        <form method="POST" action="{{ route('imports.store') }}" enctype="multipart/form-data" class="mt-6 space-y-4">
            @csrf
            <input type="hidden" name="import_type" value="{{ $type }}">
            <div>
                <label class="form-label" for="file">File <span class="text-ivy-red">*</span></label>
                <input class="form-input" id="file" name="file" type="file" accept=".csv,.xlsx" required>
                @error('file')
                    <p class="mt-1 text-sm text-ivy-red">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex justify-end gap-3">
                <a class="btn-secondary" href="{{ route('imports.index') }}">Cancel</a>
                <button class="btn-primary" type="submit">Upload and continue</button>
            </div>
        </form>
    </div>
</x-layouts.app>
