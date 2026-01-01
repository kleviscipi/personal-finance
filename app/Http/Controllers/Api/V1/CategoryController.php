<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends ApiController
{
    public function index(Request $request)
    {
        $account = $this->resolveAccount($request);

        $categories = $account->categories()
            ->with(['subcategories' => function ($query) {
                $query->orderBy('order')->orderBy('name');
            }])
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        return CategoryResource::collection($categories);
    }

    public function show(Request $request, int $categoryId)
    {
        $account = $this->resolveAccount($request);

        $category = Category::where('account_id', $account->id)
            ->with(['subcategories' => function ($query) {
                $query->orderBy('order')->orderBy('name');
            }])
            ->findOrFail($categoryId);

        return new CategoryResource($category);
    }

    public function store(Request $request)
    {
        $account = $this->resolveAccount($request);
        $this->ensureAccountRole($request, $account, ['owner', 'admin', 'member']);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:7'],
            'type' => ['required', Rule::in(['expense', 'income'])],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $category = Category::create([
            'account_id' => $account->id,
            'name' => $validated['name'],
            'icon' => $validated['icon'] ?? null,
            'color' => $validated['color'] ?? null,
            'type' => $validated['type'],
            'is_system' => false,
            'order' => $validated['order'] ?? 0,
        ]);

        return (new CategoryResource($category))->response()->setStatusCode(201);
    }

    public function update(Request $request, int $categoryId)
    {
        $account = $this->resolveAccount($request);
        $this->ensureAccountRole($request, $account, ['owner', 'admin']);

        $category = Category::where('account_id', $account->id)
            ->findOrFail($categoryId);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:7'],
            'type' => ['sometimes', 'required', Rule::in(['expense', 'income'])],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $category->update($validated);

        return new CategoryResource($category->fresh());
    }

    public function destroy(Request $request, int $categoryId)
    {
        $account = $this->resolveAccount($request);
        $this->ensureAccountRole($request, $account, ['owner', 'admin']);

        $category = Category::where('account_id', $account->id)
            ->findOrFail($categoryId);

        if ($category->is_system) {
            abort(403, 'System categories cannot be deleted.');
        }

        $category->delete();

        return response()->json([], 204);
    }
}
