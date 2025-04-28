@extends('backend.layouts.master')

@section('main-content')

<div class="card">
    <h5 class="card-header">Edit Kategori</h5>
    <div class="card-body">
      <form method="post" action="{{route('category.update',$category->id)}}" enctype="multipart/form-data">
        @csrf
        @method('PATCH')
        <div class="form-group">
          <label for="inputTitle" class="col-form-label">Judul <span class="text-danger">*</span></label>
          <input id="inputTitle" type="text" name="title" placeholder="Masukkan judul"  value="{{$category->title}}" class="form-control">
          @error('title')
          <span class="text-danger">{{$message}}</span>
          @enderror
        </div>

        <div class="form-group">
          <label for="summary" class="col-form-label">Ringkasan</label>
          <textarea class="form-control" id="summary" name="summary">{{$category->summary}}</textarea>
          @error('summary')
          <span class="text-danger">{{$message}}</span>
          @enderror
        </div>

        <div class="form-group">
            <label for="is_parent">Kategori Utama</label><br>
            <input type="checkbox" name="is_parent" id="is_parent" value="1" {{ $category->is_parent == 1 ? 'checked' : '' }}> Ya
        </div>

        <div class="form-group {{ $category->is_parent == 1 ? 'd-none' : '' }}" id="parent_cat_div">
            <label for="parent_id">Sub Kategori Dari</label>
            <select name="parent_id" id="parent_id" class="form-control">
                <option selected disabled>--Pilih kategori--</option>
                @foreach ($parent_cats as $key => $parent_cat)
                    <option value="{{ $parent_cat->id }}" {{ $parent_cat->id == $category->parent_id ? 'selected' : '' }}>
                        {{ $parent_cat->title }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
          <label for="inputPhoto" class="col-form-label">Foto</label>
          <input type="file" class="form-control" id="image" name="photo">
          @if($category->photo)
            <img src="{{ asset($category->photo) }}" class="img-fluid mt-2" style="max-width: 200px" alt="{{$category->title}}">
          @endif
          @error('photo')
          <span class="text-danger">{{$message}}</span>
          @enderror
        </div>

        <div class="form-group">
          <label for="status" class="col-form-label">Status <span class="text-danger">*</span></label>
          <select name="status" class="form-control">
              <option value="active" {{(($category->status=='active')? 'selected' : '')}}>Aktif</option>
              <option value="inactive" {{(($category->status=='inactive')? 'selected' : '')}}>Tidak Aktif</option>
          </select>
          @error('status')
          <span class="text-danger">{{$message}}</span>
          @enderror
        </div>
        <div class="form-group mb-3">
           <button class="btn btn-success" type="submit">Perbarui</button>
        </div>
      </form>
    </div>
</div>

@endsection

@push('styles')
<link rel="stylesheet" href="{{asset('backend/summernote/summernote.min.css')}}">
@endpush
@push('scripts')
<script src="/vendor/laravel-filemanager/js/stand-alone-button.js"></script>
<script src="{{asset('backend/summernote/summernote.min.js')}}"></script>
<script>
    $('#lfm').filemanager('image');

    $(document).ready(function() {
    $('#summary').summernote({
      placeholder: "Tulis deskripsi singkat.....",
        tabsize: 2,
        height: 150
    });
    });
</script>
<script>
  $('#is_parent').change(function(){
    var is_checked=$('#is_parent').prop('checked');
    // alert(is_checked);
    if(is_checked){
      $('#parent_cat_div').addClass('d-none');
      $('#parent_cat_div').val('');
    }
    else{
      $('#parent_cat_div').removeClass('d-none');
    }
  })
</script>
@endpush
