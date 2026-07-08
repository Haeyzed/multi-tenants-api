<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Enums\Tenant\ProductStatus;
use App\Models\Tenant\Category;
use App\Models\Tenant\Product;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Manages product categories within a tenant store.
 *
 * Coordinates hierarchical category CRUD, tree navigation, attribute-set links,
 * product relationships, visibility/featured toggles, and sort reordering.
 * Slugs, depth, and path values are maintained automatically by this service.
 */
class CategoryService
{
    /**
     * @var list<string>
     */
    private const array MEDIA_RELATIONS = ['imageMedia', 'bannerMedia', 'iconMedia'];

    /**
     * @var list<string>
     */
    private const array LIST_RELATIONS = ['parent', 'imageMedia', 'bannerMedia', 'iconMedia'];

    /**
     * @var list<string>
     */
    private const array DETAIL_RELATIONS = ['parent', 'children', 'attributeSets', 'imageMedia', 'bannerMedia', 'iconMedia'];

    /**
     * Paginate the categories.
     *
     * @param  array<string, mixed>  $filters
     * @param int $perPage
     * @return LengthAwarePaginator<int, Category>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Category::query()
            ->with(self::LIST_RELATIONS)
            ->filter($filters)
            ->orderBy('sort_order')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Find a category by ID.
     *
     * @param int $id
     * @return Category
     */
    public function find(int $id): Category
    {
        return Category::query()
            ->with(self::DETAIL_RELATIONS)
            ->findOrFail($id);
    }

    /**
     * Find a category by slug.
     *
     * @param string $slug
     * @return Category
     */
    public function findBySlug(string $slug): Category
    {
        return Category::query()
            ->with(self::DETAIL_RELATIONS)
            ->where('slug', $slug)
            ->firstOrFail();
    }

    /**
     * Create a new category.
     *
     * @param  array<string, mixed>  $data
     * @return Category
     */
    public function create(array $data): Category
    {
        if (! empty($data['parent_id'])) {
            $parent = Category::query()->findOrFail($data['parent_id']);
            $data['depth'] = $parent->depth + 1;
        }

        $category = Category::query()->create($data);
        $this->updatePath($category);

        return $category->fresh(self::MEDIA_RELATIONS);
    }

    /**
     * Update a category.
     *
     * @param Category $category
     * @param  array<string, mixed>  $data
     * @return Category
     */
    public function update(Category $category, array $data): Category
    {
        $parentChanged = array_key_exists('parent_id', $data)
            && $data['parent_id'] != $category->parent_id;

        if ($parentChanged) {
            $parent = $data['parent_id']
                ? Category::query()->findOrFail($data['parent_id'])
                : null;
            $data['depth'] = $parent ? $parent->depth + 1 : 0;
        }

        $category->update($data);
        $this->updatePath($category);

        if ($parentChanged) {
            $this->updateChildrenDepth($category);
            $this->updateDescendantPaths($category);
        }

        return $category->fresh(self::MEDIA_RELATIONS);
    }

    /**
     * Delete a category.
     *
     * @param Category $category
     * @return void
     * @throws DomainException
     */
    public function delete(Category $category): void
    {
        if ($category->children()->exists()) {
            throw new DomainException('Cannot delete category with children.');
        }

        $category->delete();
    }

    /**
     * Delete multiple categories by ID.
     *
     * @param  list<int>  $ids
     * @return int
     */
    public function deleteMany(array $ids): int
    {
        return Category::query()
            ->whereIn('id', $ids)
            ->whereDoesntHave('children')
            ->delete();
    }

    /**
     * Force delete a category permanently.
     *
     * @param Category $category
     * @return void
     */
    public function forceDelete(Category $category): void
    {
        $category->forceDelete();
    }

    /**
     * Restore a soft-deleted category.
     *
     * @param Category $category
     * @return Category
     */
    public function restore(Category $category): Category
    {
        $category->restore();

        return $category->fresh(self::MEDIA_RELATIONS);
    }

    /**
     * Restore multiple soft-deleted categories by ID.
     *
     * @param  list<int>  $ids
     * @return int
     */
    public function restoreMany(array $ids): int
    {
        return Category::query()->onlyTrashed()->whereIn('id', $ids)->restore();
    }

    /**
     * @param  list<int>|null  $ids
     * @param string|null $startDate
     * @param string|null $endDate
     * @return Collection<int, Category>
     */
    public function exportQuery(
        ?array $ids = null,
        ?string $startDate = null,
        ?string $endDate = null,
    ): Collection {
        $query = Category::query()->orderBy('sort_order')->latest();

        if ($ids !== null && $ids !== []) {
            $query->whereIn('id', $ids);
        }

        if ($startDate !== null) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate !== null) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return $query->get();
    }

    /**
     * @return array{total: int, visible: int, hidden: int, root: int}
     */
    public function statistics(): array
    {
        return [
            'total' => Category::query()->count(),
            'visible' => Category::query()->where('is_visible', true)->count(),
            'hidden' => Category::query()->where('is_visible', false)->count(),
            'root' => Category::query()->whereNull('parent_id')->count(),
        ];
    }

    /**
     * @return Collection<int, array{label: string, value: int}>
     */
    public function getOptions(): Collection
    {
        return Category::query()
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Category $category) => [
                'label' => $category->name,
                'value' => $category->id,
            ]);
    }

    /**
     * @return list<array{id: int, name: string, slug: string, children: list<mixed>}>
     */
    public function getTree(): array
    {
        $categories = Category::query()->orderBy('sort_order')->get();

        return $this->buildTree($categories);
    }

    /**
     * @return array<int, string>
     */
    public function getTreeForSelect(): array
    {
        $categories = Category::query()->orderBy('sort_order')->get();

        return $this->buildTreeForSelect($categories);
    }

    /**
     * @param Category $category
     * @return Collection<int, Category>
     */
    public function getBreadcrumbs(Category $category): Collection
    {
        $breadcrumbs = collect();
        $current = $category->loadMissing('parent');

        while ($current !== null) {
            $breadcrumbs->prepend($current);
            $current = $current->parent;
        }

        return $breadcrumbs;
    }

    /**
     * @param int $parentId
     * @return EloquentCollection<int, Category>
     */
    public function getChildren(int $parentId): EloquentCollection
    {
        return Category::query()
            ->where('parent_id', $parentId)
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * @param Category $category
     * @return Collection<int, Category>
     */
    public function getDescendants(Category $category): Collection
    {
        $category->loadMissing('children');
        $descendants = collect();
        $this->collectDescendants($category, $descendants);

        return $descendants;
    }

    /**
     * Move a category under a new parent.
     *
     * @param Category $category
     * @param int|null $parentId
     * @return Category
     */
    public function move(Category $category, ?int $parentId): Category
    {
        return $this->update($category, ['parent_id' => $parentId]);
    }

    /**
     * @param  list<int>  $orderedIds
     * @return void
     */
    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            Category::query()->where('id', $id)->update(['sort_order' => $index + 1]);
        }
    }

    /**
     * @param Category $category
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Product>
     */
    public function getProducts(Category $category, array $filters = []): LengthAwarePaginator
    {
        $query = $category->products();

        if (isset($filters['is_visible'])) {
            $query->where('is_visible', $filters['is_visible']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate((int) ($filters['per_page'] ?? 20));
    }

    /**
     * Assign a single attribute set to the category without removing existing links.
     *
     * @param Category $category
     * @param int $attributeSetId
     * @return void
     */
    public function assignAttributeSet(Category $category, int $attributeSetId): void
    {
        $category->attributeSets()->syncWithoutDetaching([$attributeSetId]);
    }

    /**
     * Detach an attribute set from the category.
     *
     * @param Category $category
     * @param int $attributeSetId
     * @return void
     */
    public function removeAttributeSet(Category $category, int $attributeSetId): void
    {
        $category->attributeSets()->detach($attributeSetId);
    }

    /**
     * Replace all attribute set links for the category.
     *
     * @param Category $category
     * @param  list<int>  $attributeSetIds
     * @return void
     */
    public function syncAttributeSets(Category $category, array $attributeSetIds): void
    {
        $category->attributeSets()->sync($attributeSetIds);
    }

    /**
     * Recalculate and persist the active product count for a category.
     *
     * @param Category $category
     * @return void
     */
    public function updateProductsCount(Category $category): void
    {
        $count = $category->products()
            ->where('status', ProductStatus::Active->value)
            ->count();

        $category->update(['products_count' => $count]);
    }

    /**
     * Flip the category visibility flag.
     *
     * @param Category $category
     * @return Category
     */
    public function toggleVisibility(Category $category): Category
    {
        $category->update(['is_visible' => ! $category->is_visible]);

        return $category->fresh(self::MEDIA_RELATIONS);
    }

    /**
     * Flip the category featured flag.
     *
     * @param Category $category
     * @return Category
     */
    public function toggleFeatured(Category $category): Category
    {
        $category->update(['is_featured' => ! $category->is_featured]);

        return $category->fresh(self::MEDIA_RELATIONS);
    }

    /**
     * Build the materialized path string for a category and its ancestors.
     *
     * @param Category $category
     * @return void
     */
    private function updatePath(Category $category): void
    {
        $category->loadMissing('parent');

        $path = (string) $category->id;
        $parent = $category->parent;

        while ($parent !== null) {
            $path = $parent->id.'/'.$path;
            $parent = $parent->parent;
        }

        $category->update(['path' => $path]);
    }

    /**
     * Recursively update depth for all descendants after a parent move.
     *
     * @param Category $category
     * @return void
     */
    private function updateChildrenDepth(Category $category): void
    {
        $category->loadMissing('children');

        foreach ($category->children as $child) {
            $child->update(['depth' => $category->depth + 1]);
            $this->updateChildrenDepth($child);
        }
    }

    /**
     * Recursively rebuild path values for all descendants after a parent move.
     *
     * @param Category $category
     * @return void
     */
    private function updateDescendantPaths(Category $category): void
    {
        $category->loadMissing('children');

        foreach ($category->children as $child) {
            $this->updatePath($child);
            $this->updateDescendantPaths($child);
        }
    }

    /**
     * @param  EloquentCollection<int, Category>  $categories
     * @param int|null $parentId
     * @return list<array{id: int, name: string, slug: string, children: list<mixed>}>
     */
    private function buildTree(EloquentCollection $categories, ?int $parentId = null): array
    {
        $tree = [];

        foreach ($categories->where('parent_id', $parentId) as $category) {
            $tree[] = [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'children' => $this->buildTree($categories, $category->id),
            ];
        }

        return $tree;
    }

    /**
     * @param  EloquentCollection<int, Category>  $categories
     * @param int|null $parentId
     * @param string $prefix
     * @return array<int, string>
     */
    private function buildTreeForSelect(EloquentCollection $categories, ?int $parentId = null, string $prefix = ''): array
    {
        $tree = [];

        foreach ($categories->where('parent_id', $parentId) as $category) {
            $tree[$category->id] = $prefix.$category->name;
            $tree += $this->buildTreeForSelect($categories, $category->id, $prefix.'-- ');
        }

        return $tree;
    }

    /**
     * @param Category $category
     * @param  Collection<int, Category>  $descendants
     * @return void
     */
    private function collectDescendants(Category $category, Collection $descendants): void
    {
        foreach ($category->children as $child) {
            $descendants->push($child);
            $this->collectDescendants($child, $descendants);
        }
    }
}
