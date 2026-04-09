<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Models\Product;
use App\Models\Attribute;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Http\Resources\ProductResource;
use App\Models\AttributeValue;

class SellerProductController extends Controller
{
    /**
     * Store a newly created product.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $product = Product::create([
                'seller_id' => $request->user()->seller->id,
                'name' => $request->validated('name'),
                'slug' => Str::slug($request->validated('slug') ?? $request->validated('name')),
                'description' => $request->validated('description'),
            ]);

            foreach ($request->validated('variants') as $variantData) {
                $variant = $product->variants()->create([
                    'sku' => $variantData['sku'],
                    'price' => $variantData['price'],
                    'stock_quantity' => $variantData['stock_quantity'],
                    'image_url' => $variantData['image_url'],
                ]);

                foreach ($variantData['attributes'] as $attributeData) {
                    $attribute = Attribute::firstOrCreate([
                        'name' => $attributeData['name'],
                    ]);

                    $attributeValue = AttributeValue::firstOrCreate([
                        'attribute_id' => $attribute->id,
                        'value' => $attributeData['value'],
                    ]);

                    $variant->attributeValues()->attach($attributeValue->id);
                }
            }

            DB::commit();

            $productResource = new ProductResource($product->load('variants.attributeValues.attribute'));

            return response()->json([
                'message' => 'Product created successfully.',
                'product' => $productResource,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to create product.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
