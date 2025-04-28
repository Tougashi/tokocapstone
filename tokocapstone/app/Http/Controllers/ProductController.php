<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;


use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Menampilkan daftar produk.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $products=Product::getAllProduct();
        // return $products;
        return view('backend.product.index')->with('products',$products);
    }

    public function search(Request $request)
    {
        $query = Product::with('category');

        // if ($request->has('brand')) {
        //     $query->where('brand', 'like', '%' . $request->brand . '%');
        // }

        if ($request->has('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->kategori . '%');
            });
        }

        if ($request->has('min') && $request->has('max')) {
            $query->whereBetween('price', [$request->min, $request->max]);
        }

        $products = $query->orderBy('price', 'asc')->limit(5)->get();

        return response()->json($products);
    }

    /**
     * Menampilkan formulir untuk membuat produk baru.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

        $category=Category::where('is_parent',1)->get();
        // return $category;
        return view('backend.product.create')->with('categories',$category);
    }

    /**
     * Menyimpan produk baru ke database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // return $request->all();
        $this->validate($request,[
            'title'=>'string|required',
            'summary'=>'string|required',
            'description'=>'string|nullable',
            'photo'=>'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'stock'=>"required|numeric",
            'cat_id'=>'required|exists:categories,id',
            'child_cat_id'=>'nullable|exists:categories,id',
            'is_featured'=>'sometimes|in:1',
            'status'=>'required|in:active,inactive',
            'price'=>'required|numeric'
        ]);

        $data=$request->all();
        $slug=Str::slug($request->title);
        $count=Product::where('slug',$slug)->count();
        if($count>0){
            $slug=$slug.'-'.date('ymdis').'-'.rand(0,999);
        }
        $data['slug']=$slug;
        $data['is_featured']=$request->input('is_featured',0);

        if ($request->hasFile('photo')) {
            $image = $request->file('photo');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $path = '/storage/photos/1/category/' . $filename;
            $image->move(public_path('storage/photos/1/category'), $filename);
            $data['photo'] = $path;
        }

        $status=Product::create($data);
        if($status){
            request()->session()->flash('success','Produk berhasil ditambahkan');
        }
        else{
            request()->session()->flash('error','Silakan coba lagi!!');
        }
        return redirect()->route('product.index');

    }

    /**
     * Menampilkan produk spesifik.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Menampilkan formulir untuk mengedit produk.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $product=Product::findOrFail($id);
        $category=Category::where('is_parent',1)->get();
        $items=Product::where('id',$id)->get();
        // return $items;
        return view('backend.product.edit')->with('product',$product)
                    ->with('categories',$category)->with('items',$items);
    }

    /**
     * Memperbarui produk di database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $this->validate($request, [
            'title' => 'string|required',
            'summary' => 'string|required',
            'description' => 'string|nullable',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'stock' => "required|numeric",
            'cat_id' => 'required|exists:categories,id',
            'child_cat_id' => 'nullable|exists:categories,id',
            'is_featured' => 'sometimes|in:1',
            'status' => 'required|in:active,inactive',
            'price' => 'required|numeric'
        ]);

        $data = $request->all();

        $data['is_featured'] = $request->has('is_featured') ? 1 : 0;

        if ($request->hasFile('photo')) {
            // Delete old image if exists
            if ($product->photo && file_exists(public_path($product->photo))) {
                unlink(public_path($product->photo));
            }

            $image = $request->file('photo');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $path = '/storage/photos/1/category/' . $filename;
            $image->move(public_path('storage/photos/1/category'), $filename);
            $data['photo'] = $path;
        }

        $status = $product->fill($data)->save();

        if ($status) {
            request()->session()->flash('success', 'Produk berhasil diperbarui');
        } else {
            request()->session()->flash('error', 'Silakan coba lagi!!');
        }

        return redirect()->route('product.index');
    }

    /**
     * Menghapus produk dari database.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        // Delete the image if exists
        if ($product->photo && file_exists(public_path($product->photo))) {
            unlink(public_path($product->photo));
        }

        $status = $product->delete();

        if ($status) {
            request()->session()->flash('success', 'Produk berhasil dihapus');
        } else {
            request()->session()->flash('error', 'Terjadi kesalahan saat menghapus produk');
        }
        return redirect()->route('product.index');
    }


}
