<!-- Mulai Area Footer -->
	<footer class="footer">
		<!-- Footer Atas -->
		<div class="footer-top section">
			<div class="container">
				<div class="row">
					<div class="col-lg-5 col-md-6 col-12">
							<!-- Widget Tunggal -->
						<div class="single-footer about">
							<div class="logo">
								<a href="index.html"><img src="{{ asset('frontend/img/logo.png') }}" alt="#"></a>
							</div>
							<p class="text">Toko laptop online terpercaya yang menyediakan berbagai merek dan tipe laptop dengan harga kompetitif. Dapatkan kemudahan belanja, garansi resmi, dan layanan pengiriman cepat ke seluruh Indonesia.</p>
							<p class="call">Ada Pertanyaan? Hubungi kami 24/7<span><a href="tel:123456789">+62 857-8092-2290</a></span></p>
						</div>
							<!-- Akhir Widget Tunggal -->
					</div>
					<div class="col-lg-2 col-md-6 col-12">
							<!-- Widget Tunggal -->
						<div class="single-footer links">
							<h4>Informasi</h4>
							<ul>
								<li><a href="{{route('about-us')}}">Tentang Kami</a></li>
								<li><a href="#">FAQ</a></li>
								<li><a href="#">Syarat & Ketentuan</a></li>
								<li><a href="{{route('contact')}}">Hubungi Kami</a></li>
								<li><a href="#">Bantuan</a></li>
							</ul>
						</div>
							<!-- Akhir Widget Tunggal -->
					</div>
					<div class="col-lg-2 col-md-6 col-12">
							<!-- Widget Tunggal -->
						<div class="single-footer links">
							<h4>Layanan Pelanggan</h4>
							<ul>
								<li><a href="#">Metode Pembayaran</a></li>
								<li><a href="#">Pengembalian Dana</a></li>
								<li><a href="#">Pengembalian Barang</a></li>
								<li><a href="#">Pengiriman</a></li>
								<li><a href="#">Kebijakan Privasi</a></li>
							</ul>
						</div>
							<!-- Akhir Widget Tunggal -->
					</div>
					<div class="col-lg-3 col-md-6 col-12">
							<!-- Widget Tunggal -->
						<div class="single-footer social">
							<h4>Hubungi Kami</h4>
							<!-- Widget Tunggal -->
							<div class="contact">
								<ul>
									<li>Jl. Letjen S. Parman No.1 Kampus A, RT.6/RW.16, Grogol, Kec. Grogol petamburan, Kota Jakarta Barat, Daerah Khusus Ibukota Jakarta 11440</li>
									<li>tokocapstone@gmail.com</li>
									<li>+62 857-8092-2290</li>
								</ul>
							</div>
								<!-- Akhir Widget Tunggal -->
						</div>
							<!-- Akhir Widget Tunggal -->
					</div>
				</div>
			</div>
		</div>
			<!-- Akhir Footer Atas -->
		<div class="copyright">
			<div class="container">
				<div class="inner">
					<div class="row">
						<div class="col-lg-6 col-12">
							<div class="left">
								<p>Hak Cipta © {{date('Y')}} <a href="" target="_blank">Toko Capstone</a> - Seluruh Hak Dilindungi.</p>
							</div>
						</div>
						<div class="col-lg-6 col-12">
							<div class="right">
								<img src="{{asset('backend/img/payments.png')}}" alt="#">
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</footer>
	<!-- /Akhir Area Footer -->

	<!-- Jquery -->
    <script src="{{asset('frontend/js/jquery.min.js')}}"></script>
    <script src="{{asset('frontend/js/jquery-migrate-3.0.0.js')}}"></script>
	<script src="{{asset('frontend/js/jquery-ui.min.js')}}"></script>
	<!-- Popper JS -->
	<script src="{{asset('frontend/js/popper.min.js')}}"></script>
	<!-- Bootstrap JS -->
	<script src="{{asset('frontend/js/bootstrap.min.js')}}"></script>
	<!-- Color JS -->
	<script src="{{asset('frontend/js/colors.js')}}"></script>
	<!-- Slicknav JS -->
	<script src="{{asset('frontend/js/slicknav.min.js')}}"></script>
	<!-- Owl Carousel JS -->
	<script src="{{asset('frontend/js/owl-carousel.js')}}"></script>
	<!-- Magnific Popup JS -->
	<script src="{{asset('frontend/js/magnific-popup.js')}}"></script>
	<!-- Waypoints JS -->
	<script src="{{asset('frontend/js/waypoints.min.js')}}"></script>
	<!-- Countdown JS -->
	<script src="{{asset('frontend/js/finalcountdown.min.js')}}"></script>
	<!-- Nice Select JS -->
	<script src="{{asset('frontend/js/nicesellect.js')}}"></script>
	<!-- Flex Slider JS -->
	<script src="{{asset('frontend/js/flex-slider.js')}}"></script>
	<!-- ScrollUp JS -->
	<script src="{{asset('frontend/js/scrollup.js')}}"></script>
	<!-- Onepage Nav JS -->
	<script src="{{asset('frontend/js/onepage-nav.min.js')}}"></script>
	{{-- Isotope --}}
	<script src="{{asset('frontend/js/isotope/isotope.pkgd.min.js')}}"></script>
	<!-- Easing JS -->
	<script src="{{asset('frontend/js/easing.js')}}"></script>

	<!-- Active JS -->
	<script src="{{asset('frontend/js/active.js')}}"></script>


	@stack('scripts')
	<script>
		setTimeout(function(){
		  $('.alert').slideUp();
		},5000);
		$(function() {
		// ------------------------------------------------------- //
		// Multi Level dropdowns
		// ------------------------------------------------------ //
			$("ul.dropdown-menu [data-toggle='dropdown']").on("click", function(event) {
				event.preventDefault();
				event.stopPropagation();

				$(this).siblings().toggleClass("show");


				if (!$(this).next().hasClass('show')) {
				$(this).parents('.dropdown-menu').first().find('.show').removeClass("show");
				}
				$(this).parents('li.nav-item.dropdown.show').on('hidden.bs.dropdown', function(e) {
				$('.dropdown-submenu .show').removeClass("show");
				});

			});
		});
	  </script>
