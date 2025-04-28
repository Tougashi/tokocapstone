<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $category=Category::getAllCategory();
        // return $category;
        return view('backend.category.index')->with('categories',$category);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $parent_cats=Category::where('is_parent',1)->orderBy('title','ASC')->get();
        return view('backend.category.create')->with('parent_cats',$parent_cats);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $this->validate($request, [
            'title' => 'string|required',
            'summary' => 'string|nullable',
            'photo' => 'image|nullable|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|in:active,inactive',
            'is_parent' => 'sometimes|in:1',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        $data = $request->all();

        if ($request->hasFile('photo')) {
            $image = $request->file('photo');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/photos/1/category', $filename);
            $data['photo'] = '/storage/photos/1/category/' . $filename;
        }

        $slug = Str::slug($request->title);
        $count = Category::where('slug', $slug)->count();
        if ($count > 0) {
            $slug = $slug . '-' . date('ymdis') . '-' . rand(0, 999);
        }
        $data['slug'] = $slug;

        $data['is_parent'] = $request->has('is_parent') ? 1 : 0;

        if ($data['is_parent'] == 1) {
            $data['parent_id'] = null;
        }

        $status = Category::create($data);

        if ($status) {
            $request->session()->flash('success', 'Kategori berhasil ditambahkan');
        } else {
            $request->session()->flash('error', 'Terjadi kesalahan, silakan coba lagi!');
        }

        return redirect()->route('category.index');
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $parent_cats=Category::where('is_parent',1)->get();
        $category=Category::findOrFail($id);
        return view('backend.category.edit')->with('category',$category)->with('parent_cats',$parent_cats);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $this->validate($request, [
            'title' => 'string|required',
            'summary' => 'string|nullable',
            'photo' => 'image|nullable|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|in:active,inactive',
            'is_parent' => 'sometimes|in:1',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        $data = $request->all();

        if ($request->hasFile('photo')) {
            // Delete old image if exists
            if ($category->photo) {
                $old_path = str_replace('/storage/', 'public/', $category->photo);
                if (Storage::exists($old_path)) {
                    Storage::delete($old_path);
                }
            }

            // Upload new image
            $image = $request->file('photo');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/photos/1/category', $filename);
            $data['photo'] = '/storage/photos/1/category/' . $filename;
        }

        $data['is_parent'] = $request->has('is_parent') ? 1 : 0;

        if ($data['is_parent'] == 1) {
            $data['parent_id'] = null;
        }

        $status = $category->fill($data)->save();

        if ($status) {
            $request->session()->flash('success', 'Kategori berhasil diperbarui');
        } else {
            $request->session()->flash('error', 'Terjadi kesalahan, silakan coba lagi!');
        }

        return redirect()->route('category.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        // Delete image if exists
        if ($category->photo) {
            $image_path = 'public/photos/1/category/' . $category->photo;
            if (Storage::exists($image_path)) {
                Storage::delete($image_path);
            }
        }

        $child_cat_id = Category::where('parent_id', $id)->pluck('id');
        $status = $category->delete();

        if ($status) {
            if (count($child_cat_id) > 0) {
                Category::shiftChild($child_cat_id);
            }
            request()->session()->flash('success', 'Kategori berhasil dihapus');
        } else {
            request()->session()->flash('error', 'Error saat menghapus kategori');
        }
        return redirect()->route('category.index');
    }

    public function getChildByParent(Request $request){
        // return $request->all();
        $category=Category::findOrFail($request->id);
        $child_cat=Category::getChildByParentID($request->id);
        // return $child_cat;
        if(count($child_cat)<=0){
            return response()->json(['status'=>false,'msg'=>'','data'=>null]);
        }
        else{
            return response()->json(['status'=>true,'msg'=>'','data'=>$child_cat]);
        }
    }
}
