<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SubcategoryController extends Controller
{
    public function store(Request $request, Category $category): RedirectResponse
    {
        $this->authorize('update', $category);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        Subcategory::create([
            'category_id' => $category->id,
            'name' => $validated['name'],
            'is_system' => false,
            'order' => 0,
        ]);

        return redirect()
            ->route('categories.edit', $category->id)
            ->with('message', 'Subcategory created successfully.');
    }

    public function update(Request $request, Category $category, Subcategory $subcategory): RedirectResponse
    {
        $this->authorize('update', $category);
        if ($subcategory->category_id !== $category->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $subcategory->update([
            'name' => $validated['name'],
        ]);

        return redirect()
            ->route('categories.edit', $category->id)
            ->with('message', 'Subcategory updated successfully.');
    }

    public function destroy(Category $category, Subcategory $subcategory): RedirectResponse
    {
        $this->authorize('update', $category);
        if ($subcategory->category_id !== $category->id) {
            abort(404);
        }

        $subcategory->delete();

        return redirect()
            ->route('categories.edit', $category->id)
            ->with('message', 'Subcategory deleted successfully.');
    }

    public function reorder(Request $request, Category $category): RedirectResponse
    {
        $this->authorize('update', $category);

        $validated = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', Rule::exists('subcategories', 'id')->where('category_id', $category->id)],
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['order'] as $index => $subcategoryId) {
                Subcategory::where('id', $subcategoryId)->update(['order' => $index]);
            }
        });

        return redirect()->route('categories.edit', $category->id);
    }
}
