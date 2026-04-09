<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Seller;
use App\Models\Category;


class Product extends Model
{
    /**
     * The attribute that are mass assignable.
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'seller_id',
		'category_id',
        'name',
        'slug',
        'description',
    ];

    /**
     * Get the seller that owns the product.
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

	/**
	 * Get the category that owns the product.
	 */
	public function category(): BelongsTo 
	{
		return $this->belongsTo(Category::class);
	}

    /**
     * Get the variants for the product.
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }
}
