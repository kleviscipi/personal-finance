<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Support\ActiveAccount;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $account = ActiveAccount::resolve($request);
        if (!$account) {
            return redirect()->route('accounts.create');
        }

        $categories = Category::where('account_id', $account->id)
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
        ]);
    }

    public function create(Request $request): Response|RedirectResponse
    {
        $this->authorize('create', Category::class);

        $account = ActiveAccount::resolve($request);
        if (!$account) {
            return redirect()->route('accounts.create');
        }

        return Inertia::render('Categories/Create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Category::class);

        $account = ActiveAccount::resolve($request);
        if (!$account) {
            return redirect()->route('accounts.create');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['expense', 'income'])],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:7'],
        ]);

        Category::create([
            'account_id' => $account->id,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'icon' => $validated['icon'] ?? null,
            'color' => $validated['color'] ?? null,
            'is_system' => false,
            'order' => 0,
        ]);

        return redirect()
            ->route('categories.index')
            ->with('message', 'Category created successfully.');
    }

    public function edit(Request $request, Category $category): Response|RedirectResponse
    {
        $this->authorize('update', $category);

        $account = ActiveAccount::resolve($request);
        if (!$account) {
            return redirect()->route('accounts.create');
        }

        return Inertia::render('Categories/Edit', [
            'category' => $category->load(['subcategories' => function ($query) {
                $query->orderBy('order')->orderBy('name');
            }]),
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $this->authorize('update', $category);

        $account = ActiveAccount::resolve($request);
        if (!$account) {
            return redirect()->route('accounts.create');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['expense', 'income'])],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:7'],
        ]);

        $category->update($validated);

        return redirect()
            ->route('categories.index')
            ->with('message', 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        $this->authorize('delete', $category);

        $category->delete();

        return redirect()
            ->route('categories.index')
            ->with('message', 'Category deleted successfully.');
    }

    public function reorder(Request $request)
    {
        $account = ActiveAccount::resolve($request);
        if (!$account) {
            return redirect()->route('accounts.create');
        }

        $validated = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', Rule::exists('categories', 'id')->where('account_id', $account->id)],
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['order'] as $index => $categoryId) {
                Category::where('id', $categoryId)->update(['order' => $index]);
            }
        });

        return redirect()->route('categories.index');
    }
}
