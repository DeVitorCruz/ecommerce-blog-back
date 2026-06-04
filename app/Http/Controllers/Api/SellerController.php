<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use App\Http\Requests\StoreSellerRequest;
use App\Models\Seller;
use Illuminate\Http\JsonResponse;

class SellerController extends Controller
{
    /**
     * Store a newly created seller profile.
     */
    public function store(StoreSellerRequest $request): JsonResponse
    {
        $user = $request->user();
        
        if ($user->seller) {
            return response()->json([
                'message' => 'User is already a seller.',
            ], 409);
        }

        $validatedData = $request->validated();
        $validatedData['slug'] = Str::slug($validatedData['store_name']);
        $validatedData['user_id'] = $user->id;

        $seller = Seller::create($validatedData);

        return response()->json([
            'message' => 'Seller profile created successfully.',
            'seller' => $seller,
        ], 201);
    }
}
