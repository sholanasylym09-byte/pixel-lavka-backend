<?php

namespace App\Models;

use App\Enums\Rarity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    use HasFactory;

    /** Белый список полей сортировки каталога. */
    public const SORTABLE = ['price', 'name', 'created_at', 'stock'];

    public const ICONS = [
        'sword', 'shield', 'potion', 'hat', 'ring', 'gem', 'chest', 'coin', 'key', 'scroll', 'bow', 'axe',
        'cloak_blue', 'cloak_red', 'cloak_orange', 'cloak_violet', 'cloak_snow', 'cloak_night',
    ];

    protected $fillable = [
        'category_id', 'name', 'slug', 'description', 'price', 'stock', 'rarity', 'icon', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'stock' => 'integer',
            'rarity' => Rarity::class,
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class)->withPivot(['quantity', 'price'])->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Поиск по части названия или описания. */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, fn (Builder $q) => $q->where(function (Builder $w) use ($term) {
            $w->where('name', 'like', '%'.$term.'%')->orWhere('description', 'like', '%'.$term.'%');
        }));
    }

    /** Фильтрация каталога: категория, редкость, диапазон цены, наличие. */
    public function scopeFilter(Builder $query, array $f): Builder
    {
        return $query
            ->when($f['category_id'] ?? null, fn ($q, $v) => $q->where('category_id', $v))
            ->when($f['rarity'] ?? null, fn ($q, $v) => $q->where('rarity', $v))
            ->when(isset($f['min_price']), fn ($q) => $q->where('price', '>=', (int) $f['min_price']))
            ->when(isset($f['max_price']), fn ($q) => $q->where('price', '<=', (int) $f['max_price']))
            ->when(! empty($f['in_stock']), fn ($q) => $q->where('stock', '>', 0));
    }
}
