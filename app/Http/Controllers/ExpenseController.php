<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\ExpenseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        return view('expenses.index', [
            'expenses' => Expense::query()
                ->with(['category', 'creator', 'approver'])
                ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
                ->when($request->filled('category_id'), fn ($query) => $query->where('expense_category_id', $request->integer('category_id')))
                ->latest('incurred_on')
                ->paginate(20)
                ->withQueryString(),
            'categories' => ExpenseCategory::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('expenses.create', [
            'categories' => ExpenseCategory::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, ExpenseService $service): RedirectResponse
    {
        $data = $request->validate([
            'expense_category_id' => ['required', 'exists:expense_categories,id'],
            'incurred_on' => ['required', 'date'],
            'payment_method' => ['required', 'in:Cash,MoMo,Bank,Other'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['required', 'string', 'max:2000'],
            'receipt_path' => ['nullable', 'string', 'max:255'],
            'receipt_original_name' => ['nullable', 'string', 'max:255'],
        ]);

        $expense = $service->createExpense($data, $request->user());

        return redirect()->route('expenses.show', $expense)->with('status', 'Expense '.$expense->expense_number.' recorded for review.');
    }

    public function show(Expense $expense): View
    {
        return view('expenses.show', [
            'expense' => $expense->load(['category', 'creator', 'approver']),
        ]);
    }

    public function approve(Request $request, Expense $expense, ExpenseService $service): RedirectResponse
    {
        $service->approve($expense, $request->user());

        return back()->with('status', 'Expense approved.');
    }

    public function reject(Request $request, Expense $expense, ExpenseService $service): RedirectResponse
    {
        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $service->reject($expense, $data['rejection_reason'], $request->user());

        return back()->with('status', 'Expense rejected.');
    }

    public function categories(): View
    {
        return view('expenses.categories', [
            'categories' => ExpenseCategory::query()->withCount('expenses')->orderBy('name')->get(),
        ]);
    }

    public function storeCategory(Request $request, ExpenseService $service): RedirectResponse
    {
        $service->createCategory($request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:expense_categories,name'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]), $request->user());

        return back()->with('status', 'Expense category created.');
    }

    public function report(Request $request): View
    {
        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now()->endOfMonth();

        $expenses = Expense::query()
            ->with('category')
            ->whereBetween('incurred_on', [$from->toDateString(), $to->toDateString()])
            ->where('status', 'approved')
            ->orderBy('incurred_on')
            ->get();

        return view('expenses.report', [
            'expenses' => $expenses,
            'from' => $from,
            'to' => $to,
            'totalsByCategory' => $expenses->groupBy('expense_category_id')->map(fn ($items) => [
                'category' => $items->first()->category,
                'total' => $items->sum('amount'),
            ]),
            'total' => $expenses->sum('amount'),
        ]);
    }
}
