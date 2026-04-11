<?php
  namespace App\Http\Controllers\Api;

  use App\Http\Controllers\Controller;
  use App\Http\Requests\StoreProductRequest;
  use App\Http\Resources\ProductResource;
  use App\Models\Product;
  use App\Models\Attribute;
  use App\Models\AttributeValue;
  use Illuminate\Http\JsonResponse;
  use Illuminate\Support\Facades\DB;
  use Illuminate\Support\Facades\Storage;
  use Illuminate\Support\Str;

  class SellerProductController extends Controller
  {
      public function store(StoreProductRequest $request): JsonResponse
      {
         try {
             DB::beginTransaction();

             $product = Product::create([
                 'seller_id' => $request->user()->seller->id,
                 'category_id' => $request->validated('category_id'),
                 'name' => $request->validated('name'),
                 'slug' => Str::slug($request->validated('slug') ?? $request->validated('name')),
                 'description' => $request->validated('description'),
             ]);

             foreach ($request->validated('variants') as $index => $variantData) {
                $imagePath = null;
                if ($request->hasFile("variants.$index.image_path")) {
                    $imagePath = $request->file("variants.$index.image_path")
                        ->store('products', 'public');
                }

                $variant = $product->variants()->create([
                    'sku' => $variantData['sku'],
                    'price' => $variantData['price'],
                    'stock_quantity' => $variantData['stock_quantity'],
                    'image_path' => $imagePath,
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

             $product->load('variants.attributeValues.attribute', 'category', 'seller');

             return response()->json([
                'message' => 'Product created successfully.',
                'product' => new ProductResource($product),
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
