<!DOCTYPE html>
<html lang="en">
<head>
  <title>Toko Capstone || Forgot Password</title>
  @include('backend.layouts.head')
</head>

<body class="bg-gradient-primary">
  <div class="container h-100">
    <!-- Outer Row -->
    <div class="row justify-content-center align-items-center min-vh-100">
      <div class="col-xl-6 col-lg-8 col-md-8">
        <div class="card o-hidden border-0 shadow-lg">
          <div class="card-body p-0">
            <!-- Nested Row within Card Body -->
            <div class="row">
              <div class="col-lg-2 d-none d-lg-block bg-password-image"></div>
              <div class="col-lg-8">
                <div class="p-4">
                  <div class="text-center">
                    <h1 class="h4 text-gray-900 mb-2">Lupa Password?</h1>
                    <p class="mb-4">Masukkan email Anda di bawah ini dan kami akan mengirimkan link untuk mengatur ulang password Anda!</p>
                  </div>
                  @if (session('status'))
                      <div class="alert alert-success" role="alert">
                          {{ session('status') }}
                      </div>
                  @endif
                  <form class="user"  method="POST" action="{{ route('password.email') }}">
                    @csrf
                    <div class="form-group">
                      <input type="email" class="form-control form-control-user @error('email') is-invalid @enderror" id="exampleInputEmail" aria-describedby="emailHelp" placeholder="Enter Email Address..." name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                        @error('email')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary btn-user btn-block">
                      Reset Password
                    </button>
                  </form>
                  <hr>
                  <div class="text-center">
                    <a class="small" href="{{route('login')}}">Sudah punya akun? Login!</a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
