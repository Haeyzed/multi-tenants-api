<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Hierarchical folder for organizing media library files.
 *
 * @property int $id
 * @property string|null $name
 * @property int|null $parent_id
 * @property string|null $path
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder<MediaFolder>|MediaFolder search(?string $search)
 */
class MediaFolder extends Model
{
    use HasFactory;

    protected $table = 'media_folders';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'parent_id',
        'path',
        'sort_order',
    ];

    /**
     * @return BelongsTo<MediaFolder, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<MediaFolder, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * @return HasMany<Media, $this>
     */
    public function media(): HasMany
    {
        return $this->hasMany(Media::class, 'folder_id')
            ->where('collection_name', 'library');
    }

    /**
     * @param  Builder<MediaFolder>  $query
     */
    public function scopeSearch(Builder $query, ?string $search): void
    {
        $query->when($search, function (Builder $q, string $search): void {
            $q->where('name', 'like', "%{$search}%");
        });
    }

    /**
     * @param  Builder<MediaFolder>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<MediaFolder>
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when(! empty($filters['search']), function (Builder $q) use ($filters): void {
                $q->search((string) $filters['search']);
            })
            ->when(array_key_exists('parent_id', $filters), function (Builder $q) use ($filters): void {
                $q->where('parent_id', $filters['parent_id']);
            });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
