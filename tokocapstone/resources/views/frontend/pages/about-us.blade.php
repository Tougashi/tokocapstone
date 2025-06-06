@extends('frontend.layouts.master')

@section('title','Toko Capstone')

@section('main-content')

	<!-- Breadcrumbs -->
	<div class="breadcrumbs">
		<div class="container">
			<div class="row">
				<div class="col-12">
					<div class="bread-inner">
						<ul class="bread-list">
							<li><a href="/">Beranda<i class="ti-arrow-right"></i></a></li>
							<li class="active"><a href="/about-us">Tentang Kami</a></li>
						</ul>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- End Breadcrumbs -->

	<section class="about-us section">
        <div class="container">
            <div class="row align-items-center">
                <!-- Kolom Teks -->
                <div class="col-lg-6 col-md-6 col-12">
                    <div class="about-content">
                        <h3>Selamat Datang di <span>Toko Capstone</span></h3>
                        <p>Solusi terbaik untuk kebutuhan teknologi Anda. Kami menawarkan beragam pilihan laptop dari merek-merek ternama seperti Dell, HP, Lenovo, Asus, Apple, dan Merek Lainnya yang dapat memenuhi berbagai kebutuhan, mulai dari pekerjaan profesional, gaming, hingga keperluan pendidikan.</p>
                        <p>Nikmati pengalaman berbelanja yang mudah dan aman dengan fitur pencarian yang intuitif, filter produk, serta dukungan customer service yang siap membantu Anda. Semua produk yang kami tawarkan dilengkapi dengan garansi resmi, memastikan kualitas dan kepuasan pelanggan.</p>
                        <div class="button">
                            <a href="{{route('contact')}}" class="btn primary">Hubungi Kami</a>
                        </div>
                    </div>
                </div>
                <!-- Kolom Laptop -->
                <div class="col-lg-6 col-md-6 col-12 text-center">
                    <div class="laptop">
                        <div class="screen">
                            <div class="header-laptop"></div>
                            <div class="text">Toko Capstone</div>
                        </div>
                        <div class="keyboard"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    @push('styles')

    <style>
        .laptop {
        transform: scale(0.8);
        }
        .screen {
        border-radius: 20px;
        box-shadow: inset 0 0 0 2px #c8cacb, inset 0 0 0 10px #000;
        height: 318px;
        width: 518px;
        margin: 0 auto;
        padding: 9px 9px 23px 9px;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        background-image: linear-gradient(
            15deg,
            #3f51b1 0%,
            #5a55ae 13%,
            #7b5fac 25%,
            #8f6aae 38%,
            #a86aa4 50%,
            #cc6b8e 62%,
            #f18271 75%,
            #f3a469 87%,
            #f7c978 100%
        );
        transform-style: preserve-3d;
        transform: perspective(1900px) rotateX(-88.5deg);
        transform-origin: 50% 100%;
        animation: open 4s infinite alternate;
        }
        @keyframes open {
        0% {
            transform: perspective(1900px) rotateX(-88.5deg);
        }
        100% {
            transform: perspective(1000px) rotateX(0deg);
        }
        }
        .screen::before {
        content: "";
        width: 518px;
        height: 12px;
        position: absolute;
        background: linear-gradient(#979899, transparent);
        top: -3px;
        transform: rotateX(90deg);
        border-radius: 5px 5px;
        }

        .text {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen,
            Ubuntu, Cantarell, "Open Sans", "Helvetica Neue", sans-serif;
        color: #fff;
        letter-spacing: 1px;
        text-shadow: 0 0 5px #fff;
        }
        .header-laptop {
        width: 100px;
        height: 12px;
        position: absolute;
        background-color: #000;
        top: 10px;
        left: 50%;
        transform: translate(-50%, -0%);
        border-radius: 0 0 6px 6px;
        }
        .screen::after {
        background: linear-gradient(to bottom, #272727, #0d0d0d);
        border-radius: 0 0 20px 20px;
        bottom: 2px;
        content: "";
        height: 24px;
        left: 2px;
        position: absolute;
        width: 514px;
        }
        .keyboard {
        background: radial-gradient(circle at center, #e2e3e4 85%, #a9abac 100%);
        border: solid #a0a3a7;
        border-radius: 2px 2px 12px 12px;
        border-width: 1px 2px 0 2px;
        box-shadow: inset 0 -2px 8px 0 #6c7074;
        height: 24px;
        margin-top: -10px;
        position: relative;
        width: 560px;
        z-index: 9;
        }
        .keyboard::after {
        background: #e2e3e4;
        border-radius: 0 0 10px 10px;
        box-shadow: inset 0 0 4px 2px #babdbf;
        content: "";
        height: 10px;
        left: 50%;
        margin-left: -60px;
        position: absolute;
        top: 0;
        width: 120px;
        }
        .keyboard::before {
        background: 0 0;
        border-radius: 0 0 3px 3px;
        bottom: -2px;
        box-shadow: -270px 0 #272727, 250px 0 #272727;
        content: "";
        height: 2px;
        left: 50%;
        margin-left: -10px;
        position: absolute;
        width: 40px;
        }

    </style>
@endpush

	<!-- Start Shop Services Area -->
	<section class="shop-services section">
		<div class="container">
			<div class="row">
				<div class="col-lg-3 col-md-6 col-12">
					<!-- Start Single Service -->
					<div class="single-service">
						<i class="ti-rocket"></i>
						<h4>Gratis Pengiriman</h4>
						<p>Untuk pembelian di atas Rp100.000</p>
					</div>
					<!-- End Single Service -->
				</div>
				<div class="col-lg-3 col-md-6 col-12">
					<!-- Start Single Service -->
					<div class="single-service">
						<i class="ti-reload"></i>
						<h4>Gratis Pengembalian</h4>
						<p>30 hari masa pengembalian</p>
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
						<p>Jaminan harga terbaik</p>
					</div>
					<!-- End Single Service -->
				</div>
			</div>
		</div>
	</section>
	<!-- End Shop Services Area -->

	@include('frontend.layouts.newsletter')
@endsection
