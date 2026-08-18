<x-layouts.app title="System Setup">
    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">Users</p>
            <p class="mt-2 text-3xl font-bold text-ivy-navy">{{ $userCount }}</p>
        </div>
        <div class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">Roles</p>
            <p class="mt-2 text-3xl font-bold text-ivy-navy">{{ $roleCount }}</p>
        </div>
        <div class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">Audit events</p>
            <p class="mt-2 text-3xl font-bold text-ivy-navy">{{ $auditCount }}</p>
        </div>
    </div>

    <section class="mt-6 rounded-md border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-bold text-ivy-navy">Module 1 foundation</h2>
                <p class="mt-1 text-sm text-slate-600">Authentication, staff access, company settings and audit controls are the only active modules.</p>
            </div>
            <img src="{{ asset('images/ivys-logo.png') }}" alt="Ivy's Food" class="h-20 w-20 object-contain">
        </div>
        <dl class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <dt class="text-xs font-semibold uppercase text-slate-500">Trading name</dt>
                <dd class="mt-1 text-sm font-semibold text-ivy-black">{{ $settings->trading_name }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase text-slate-500">Currency</dt>
                <dd class="mt-1 text-sm font-semibold text-ivy-black">{{ $settings->currency }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase text-slate-500">Time zone</dt>
                <dd class="mt-1 text-sm font-semibold text-ivy-black">{{ $settings->time_zone }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase text-slate-500">Credit period</dt>
                <dd class="mt-1 text-sm font-semibold text-ivy-black">{{ $settings->default_credit_period_days }} days</dd>
            </div>
        </dl>
    </section>
</x-layouts.app>
