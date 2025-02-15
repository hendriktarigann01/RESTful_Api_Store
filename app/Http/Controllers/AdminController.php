<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
// use App\Models\Admin;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Product::all(), 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'group' => 'required|string|max:255',
            'subgroup' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'price' => 'required|integer',
            'stock' => 'required|integer|min:0',
        ]);

        $uploadedFileUrl = Cloudinary::upload(
            $request->file('image')->getRealPath(),
            ['folder' => 'store_images']
        )->getSecurePath();

        $product = Product::create([
            'name' => $request->name,
            'group' => $request->group,
            'subgroup' => $request->subgroup,
            'description' => $request->description,
            'image' => $uploadedFileUrl,
            'price' => $request->price,
            'stock' => $request->stock
        ]);

        return response()->json($product, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::findOrFail($id);
        return response()->json($product, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'name' => 'string|max:255',
            'group' => 'string|max:255',
            'subgroup' => 'string|max:255',
            'description' => 'string|max:255',
            'image' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'price' => 'integer',
            'stock' => 'integer|min:0',
        ]);

        $data = $request->only(['name', 'group', 'subgroup', 'description', 'price', 'stock']);

        if ($request->hasFile('image')) {
            $uploadedFileUrl = Cloudinary::upload(
                $request->file('image')->getRealPath(),
                ['folder' => 'store_images']
            )->getSecurePath();

            $data['image'] = $uploadedFileUrl;
        }

        $product->update($data);

        return response()->json($product, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json(['message' => 'Produk berhasil dihapus'], 200);
    }
}
