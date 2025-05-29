@extends('frontend.layouts.master')
@section('title','Toko Capstone')
@section('main-content')
<!-- Slider Area -->
@if(count($banners) > 0)
    <section id="Gslider" class="carousel slide" data-ride="carousel">
        <ol class="carousel-indicators">
            @foreach($banners as $key => $banner)
                <li data-target="#Gslider" data-slide-to="{{$key}}" class="{{ $key == 0 ? 'active' : '' }}"></li>
            @endforeach
        </ol>
        <div class="carousel-inner" role="listbox" style="height: 100vh; max-height: 600px;">
            @foreach($banners as $key => $banner)
                <div class="carousel-item {{ $key == 0 ? 'active' : '' }}" style="height: 100%;">
                    <div class="position-relative" style="height: 100%;">
                        <img class="d-block w-100" src="{{$banner->photo}}" alt="Slide {{$key + 1}}"
                             style="height: 100%; width: 100%; object-fit: cover;">
                             <div class="carousel-caption"
                             style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); bottom: auto; width: 100%;">
                            <div class="container">
                                <div class="row justify-content-center">
                                    <div class="col-lg-8 text-center">
                                        <h1 class="text-white mb-3">{{$banner->title}}</h1>
                                        <p class="text-white mb-4">{!! html_entity_decode($banner->description) !!}</p>
                                        <a class="btn btn-lg btn-success text-white" href="{{ route('product-grids') }}">
                                            Belanja Sekarang <i class="far fa-arrow-alt-circle-right ms-2"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <a class="carousel-control-prev" href="#Gslider" role="button" data-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="sr-only">Previous</span>
        </a>
        <a class="carousel-control-next" href="#Gslider" role="button" data-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="sr-only">Next</span>
        </a>
    </section>
@endif

<!--/ End Slider Area -->

<!-- Start Small Banner  -->
<section class="small-banner section">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="owl-carousel category-carousel">
                    @php
                    $category_lists=DB::table('categories')->where('status','active')->get();
                    $chunks = $category_lists->chunk(3);
                    @endphp
                    @foreach($chunks as $chunk)
                    <div class="item">
                        <div class="row">
                            @foreach($chunk as $cat)
                                @if($cat->is_parent==1)
                                <!-- Single Banner  -->
                                <div class="col-lg-4 col-md-4 col-12">
                                    <div class="single-banner" style="text-align: center;">
                                        <div class="banner-image" style="height: 300px; overflow: hidden;">
                                            @if($cat->photo)
                                            <img src="{{$cat->photo}}" alt="{{$cat->photo}}" style="width: 100%; height: 100%; object-fit: cover;">
                                            @else
                                            <img src="https://via.placeholder.com/600x370" alt="#" style="width: 100%; height: 100%; object-fit: cover;">
                                            @endif
                                        </div>
                                        <div class="content" style="position: static; padding: 15px;">
                                            <h3 style="color: #333; margin-bottom: 10px; margin-top: 50px;" >{{$cat->title}}</h3>
                                            <a href="{{route('product-cat',$cat->slug)}}" class="btn" style="display: inline-block; padding: 8px 20px; background: #36d100; color: #fff; border-radius: 4px;">Lihat Produk</a>
                                        </div>
                                    </div>
                                </div>
                                <!-- /End Single Banner  -->
                                @endif
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Tombol Bulat Chat -->
<div id="chat-toggle" onclick="toggleChat()" style="position: fixed; bottom: 20px; left: 20px; background: #28a745; border-radius: 50%; width: 60px; height: 60px; display: flex; justify-content: center; align-items: center; cursor: pointer; z-index: 999;">
    <img src="{{ asset('frontend/img/live-chat.png') }}" alt="Chat" style="width: 30px; height: 30px;">
</div>

  <!-- Modal Chat -->
<div id="chat-modal" style="display: none; position: fixed; bottom: 90px; left: 20px; width: 300px; height: 400px; background: #fff; border: 1px solid #ccc; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.2); z-index: 999;">
    <div style="background: #28a745; color: white; padding: 10px; border-top-left-radius: 10px; border-top-right-radius: 10px;">
        <strong>Live Chat</strong>
        <span onclick="toggleChat()" style="float: right; cursor: pointer;">✖</span>
    </div>
    <div id="chat-box" style="padding: 10px; height: 300px; overflow-y: auto;"></div>
    <div style="display: flex; padding: 10px;">
        <input type="text" id="chat-input" placeholder="Tulis pesan..." style="flex: 1; padding: 5px;">
        <button onclick="sendMessage()" style="margin-left: 5px; background: #28a745; color: white; border: none; padding: 5px 10px;">Kirim</button>
    </div>
</div>

@push('scripts')

<script>
    function toggleChat() {
      const modal = document.getElementById("chat-modal");
      modal.style.display = (modal.style.display === "none") ? "block" : "none";
    }

    async function sendMessage() {
      const input = document.getElementById("chat-input");
      const chatBox = document.getElementById("chat-box");
      const message = input.value.trim();
      if (!message) return;

      chatBox.innerHTML += `<p><strong>Anda:</strong> ${message}</p>`;
      input.value = "";

      const response = await fetch("http://localhost:5005/webhooks/rest/webhook", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          sender: "user1",
          message: message
        }),
      });

      const data = await response.json();
      data.forEach((res) => {
        chatBox.innerHTML += `<p><strong>Bot:</strong> ${res.text}</p>`;
      });

      chatBox.scrollTop = chatBox.scrollHeight;
    }

    // Add event listener for Enter key
    document.getElementById("chat-input").addEventListener("keypress", function(event) {
      if (event.key === "Enter") {
        event.preventDefault(); // Prevent default form submission
        sendMessage();
      }
    });
</script>


<script>
    $(document).ready(function(){
        $(".category-carousel").owlCarousel({
            loop: true,
            margin: 30,
            nav: true,
            dots: false,
            autoplay: true,
            autoplayTimeout: 5000,
            autoplayHoverPause: true,
            navText: ['<i class="fa fa-angle-left"></i>', '<i class="fa fa-angle-right"></i>'],
            responsive:{
                0:{
                    items: 1
                },
                768:{
                    items: 1
                },
                992:{
                    items: 1
                }
            }
        });
    });
</script>
@endpush
<!-- End Small Banner -->

<div class="product-area section">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="section-title">
                    <h2>Produk Tersedia</h2>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="product-info">
                    <div class="nav-main">
                        <!-- Tab Nav -->
                        <ul class="nav nav-tabs filter-tope-group" id="myTab" role="tablist">
                            @php
                                $categories=DB::table('categories')->where('status','active')->where('is_parent',1)->get();
                            @endphp
                            @if($categories)
                            <button class="btn category-btn active" style="background:black; color:white;" data-filter="*">
                                Semua Produk
                            </button>
                                @foreach($categories as $key=>$cat)
                                <button class="btn category-btn" style="background:none; color:black;" data-filter=".{{$cat->id}}">
                                    {{$cat->title}}
                                </button>
                                @endforeach
                            @endif
                        </ul>
                        <!--/ End Tab Nav -->
                    </div>
                    <div class="tab-content isotope-grid" id="myTabContent">
                        <!-- Start Single Tab -->
                        @if($product_lists)
                            @php $hasProducts = false; @endphp
                            @foreach($product_lists as $key=>$product)
                                @if($product->stock > 0)
                                    @php $hasProducts = true; @endphp
                                    <div class="col-sm-6 col-md-4 col-lg-3 p-b-35 isotope-item {{$product->cat_id}}">
                                        <div class="single-product">
                                            <div class="product-img">
                                                <a href="{{route('product-detail',$product->slug)}}">
                                                    @php
                                                        $photo = explode(',', $product->photo);
                                                    @endphp
                                                    <img class="default-img" src="{{$photo[0]}}" alt="{{$photo[0]}}">
                                                    <img class="hover-img" src="{{$photo[0]}}" alt="{{$photo[0]}}">
                                                    @if($product->condition == 'BARU')
                                                        <span class="new">Baru</span>
                                                    @elseif($product->condition == 'TERLARIS')
                                                        <span class="hot">Terlaris</span>
                                                    @endif
                                                </a>
                                                <div class="button-head">
                                                    <div class="product-action">
                                                        <a title="Wishlist" href="{{route('add-to-wishlist',$product->slug)}}"><i class=" ti-heart "></i><span>Tambah ke Wishlist</span></a>
                                                    </div>
                                                    <div class="product-action-2">
                                                        <a title="Tambah ke keranjang" href="{{route('add-to-cart',$product->slug)}}">Tambah ke Keranjang</a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="product-content">
                                                <h3><a href="{{route('product-detail',$product->slug)}}">{{$product->title}}</a></h3>
                                                <div class="product-price">
                                                    <span>Rp{{number_format($product->price)}}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach

                            @if(!$hasProducts)
                                <div class="no-products">
                                    <h3 class="text-center text-secondary mt-4">Produk tidak tersedia</h3>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- End Product Area -->

<!-- Start Shop Home List  -->
<section class="shop-home-list section">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 col-md-12 col-12">
                <div class="row">
                    <div class="col-12">
                        <div class="shop-section-title">
                            <h1>Produk Terbaru</h1>
                        </div>
                    </div>
                </div>
                <div class="row">
                    @php
                        // Mengambil 10 produk terbaru berdasarkan created_at
                        $product_lists = DB::table('products')
                            ->where('status', 'active')
                            ->orderBy('created_at', 'DESC') // Mengurutkan berdasarkan created_at secara menurun
                            ->limit(10) // Membatasi hasil query hanya 10
                            ->get();
                    @endphp

                    @foreach($product_lists as $product)
                        <div class="col-md-4">
                            <!-- Start Single List  -->
                            <div class="single-list">
                                <div class="row">
                                    <div class="col-lg-6 col-md-6 col-12">
                                        <div class="list-image overlay">
                                            @php
                                                $photo = explode(',', $product->photo);
                                            @endphp
                                            <img src="{{$photo[0]}}" alt="{{$photo[0]}}">
                                            <a href="{{route('add-to-cart', $product->slug)}}" class="buy"><i class="fa fa-shopping-bag"></i></a>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-12 no-padding">
                                        <div class="content">
                                            <h4 class="title"><a href="#">{{$product->title}}</a></h4>
                                            <p class="price with-discount">Rp{{number_format($product->price)}}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- End Single List  -->
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
<!-- End Shop Home List  -->


<!-- Start Shop Services Area -->
<section class="shop-services section home">
    <div class="container">
        <div class="row">
            <div class="col-lg-3 col-md-6 col-12">
                <!-- Start Single Service -->
                <div class="single-service">
                    <i class="ti-rocket"></i>
                    <h4>Gratis Pengiriman</h4>
                    <p>Untuk pembelian di atas Rp 10.000.000</p>
                </div>
                <!-- End Single Service -->
            </div>
            <div class="col-lg-3 col-md-6 col-12">
                <!-- Start Single Service -->
                <div class="single-service">
                    <i class="ti-reload"></i>
                    <h4>Gratis Pengembalian</h4>
                    <p>30 hari pengembalian</p>
                </div>
                <!-- End Single Service -->
            </div>
            <div class="col-lg-3 col-md-6 col-12">
                <!-- Start Single Service -->
                <div class="single-service">
                    <i class="ti-lock"></i>
                    <h4>Pembayaran Aman</h4>
                    <p>100% pembayaran aman</p>
                </div>
                <!-- End Single Service -->
            </div>
            <div class="col-lg-3 col-md-6 col-12">
                <!-- Start Single Service -->
                <div class="single-service">
                    <i class="ti-tag"></i>
                    <h4>Harga Terbaik</h4>
                    <p>Harga termurah yang dijamin</p>
                </div>
                <!-- End Single Service -->
            </div>
        </div>
    </div>
</section>
<!-- End Shop Services Area -->

@include('frontend.layouts.newsletter')


@endsection

@push('styles')

    <style>
        /* Banner Sliding */
        #Gslider .carousel-inner {
        background: #000000;
        color:black;
        }

        #Gslider .carousel-inner{
        height: 550px;
        }
        #Gslider .carousel-inner img{
            width: 100% !important;
            opacity: .8;
        }

        #Gslider .carousel-inner .carousel-caption {
        bottom: 60%;
        }

        #Gslider .carousel-inner .carousel-caption h1 {
        font-size: 50px;
        font-weight: bold;
        line-height: 100%;
        color: #36d100;
        }

        #Gslider .carousel-inner .carousel-caption p {
        font-size: 18px;
        color: black;
        margin: 28px 0 28px 0;
        }

        #Gslider .carousel-indicators {
        bottom: 70px;
        }
    </style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
<script>
    /*==================================================================
    [ Isotope ]*/
    let $topeContainer = $('.isotope-grid');
    let $filter = $('.filter-tope-group');

    // filter items on button click
    $filter.each(function () {
        $filter.on('click', 'button', function () {
            let filterValue = $(this).attr('data-filter');

            // Remove active class and reset styles from all buttons
            $('.category-btn').removeClass('active')
                .css({'background': 'none', 'color': 'black'});

            // Add active class and set styles to clicked button
            $(this).addClass('active')
                .css({'background': 'black', 'color': 'white'});

            $topeContainer.isotope({
                filter: filterValue,
                onLayout: function() {
                    // Check if any items are visible after filtering
                    let visibleItemCount = $topeContainer.data('isotope').filteredItems.length;

                    // Remove existing message if it exists
                    $('.no-products-message').remove();

                    // If no items are visible, show message
                    if (visibleItemCount === 0) {
                        $topeContainer.append('<div class="col-12 text-center mt-4 no-products-message"><h2 class="fw-bold mt-3">Produk tidak tersedia</h2></div>');
                    }
                }
            });
        });
    });

    // init Isotope
    $(window).on('load', function () {
        let $grid = $topeContainer.each(function () {
            $(this).isotope({
                itemSelector: '.isotope-item',
                layoutMode: 'fitRows',
                percentPosition: true,
                animationEngine: 'best-available',
                masonry: {
                    columnWidth: '.isotope-item'
                }
            });
        });
    });

    function cancelFullScreen(el) {
        let requestMethod = el.cancelFullScreen || el.webkitCancelFullScreen ||
                        el.mozCancelFullScreen || el.exitFullscreen;
        if (requestMethod) { // cancel full screen.
            requestMethod.call(el);
        } else if (typeof window.ActiveXObject !== "undefined") { // Older IE.
            let wscript = new ActiveXObject("WScript.Shell");
            if (wscript !== null) {
                wscript.SendKeys("{F11}");
            }
        }
    }

    function requestFullScreen(el) {
        // Supports most browsers and their versions.
        let requestMethod = el.requestFullScreen || el.webkitRequestFullScreen ||
                        el.mozRequestFullScreen || el.msRequestFullscreen;

        if (requestMethod) { // Native full screen.
            requestMethod.call(el);
        } else if (typeof window.ActiveXObject !== "undefined") { // Older IE.
            let wscript = new ActiveXObject("WScript.Shell");
            if (wscript !== null) {
                wscript.SendKeys("{F11}");
            }
        }
        return false;
    }

</script>

@endpush
