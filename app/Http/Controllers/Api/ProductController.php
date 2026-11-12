<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductIndexRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    public function __construct(private ProductService $products) {}

    /** GET /products?category_id=&rarity=&search=&min_price=&max_price=&sort=&order=&page=&per_page= */
    public function index(ProductIndexRequest $request)
    {
        $isAdmin = (bool) $request->user('sanctum')?->isAdmin();

        return ProductResource::collection($this->products->list($request->validated(), $isAdmin));
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($product->load('category'));
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        return (new ProductResource($this->products->create($request->validated())))
            ->response()->setStatusCode(201);
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        return new ProductResource($this->products->update($product, $request->validated()));
    }

    public function destroy(Product $product): Response
    {
        $this->products->delete($product);

        return response()->noContent();
    }
}
