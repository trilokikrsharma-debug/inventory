<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Category;
use App\Models\Tenant\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()
            ->orderBy('name')
            ->paginate(20);

        $productCountByCategory = Product::query()
            ->selectRaw('category_id, COUNT(*) as total')
            ->whereNotNull('category_id')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        return view('tenant.categories.index', compact('categories', 'productCountByCategory'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:160', Rule::unique('categories', 'slug')],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Category::query()->create([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? Str::slug($validated['name']).'-'.Str::lower(Str::random(4)),
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return back()->with('status', 'Category created successfully.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:160', Rule::unique('categories', 'slug')->ignore($category->id)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $category->update([
            ...$validated,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return back()->with('status', 'Category updated successfully.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if (Product::query()->where('category_id', $category->id)->exists()) {
            return back()->withErrors([
                'category' => 'Cannot delete category linked to products.',
            ]);
        }

        $category->delete();

        return back()->with('status', 'Category deleted successfully.');
    }
}
