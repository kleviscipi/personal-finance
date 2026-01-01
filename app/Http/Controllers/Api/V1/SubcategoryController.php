<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\SubcategoryResource;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Http\Request;

class SubcategoryController extends ApiController
{
    public function store(Request $request, int $categoryId)
    {
        $account = $this->resolveAccount($request);
        $this->ensureAccountRole($request, $account, ['owner', 'admin']);

        $category = Category::where('account_id', $account->id)->findOrFail($categoryId);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $subcategory = Subcategory::create([
            'category_id' => $category->id,
            'name' => $validated['name'],
            'is_system' => false,
            'order' => $validated['order'] ?? 0,
        ]);

        return (new SubcategoryResource($subcategory))->response()->setStatusCode(201);
    }

    public function update(Request $request, int $categoryId, int $subcategoryId)
    {
        $account = $this->resolveAccount($request);
        $this->ensureAccountRole($request, $account, ['owner', 'admin']);

        $category = Category::where('account_id', $account->id)->findOrFail($categoryId);

        $subcategory = Subcategory::where('category_id', $category->id)
            ->findOrFail($subcategoryId);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $subcategory->update($validated);

        return new SubcategoryResource($subcategory->fresh());
    }

    public function destroy(Request $request, int $categoryId, int $subcategoryId)
    {
        $account = $this->resolveAccount($request);
        $this->ensureAccountRole($request, $account, ['owner', 'admin']);

        $category = Category::where('account_id', $account->id)->findOrFail($categoryId);

        $subcategory = Subcategory::where('category_id', $category->id)
            ->findOrFail($subcategoryId);

        if ($subcategory->is_system) {
            abort(403, 'System subcategories cannot be deleted.');
        }

        $subcategory->delete();

        return response()->json([], 204);
    }
}
