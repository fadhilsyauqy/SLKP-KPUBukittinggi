<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SLKP KPU Bukittinggi</title>
    <link rel="icon" href="{{ asset('assets/img/logo-kpu.png') }}" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            padding: 2rem;
        }
        .login-logo {
            width: 90px;
            height: auto;
            margin-bottom: 1rem;
        }
        .btn-kpu {
            background-color: #b71c1c;
            color: #ffffff;
            font-weight: 600;
        }
        .btn-kpu:hover {
            background-color: #8e0000;
            color: #ffffff;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-4">
            <img src="{{ asset('assets/img/logo-kpu.png') }}" alt="Logo KPU" class="login-logo">
            <h4 class="fw-bold mb-1">SLKP KPU BUKITTINGGI</h4>
            <p class="text-muted small">Sistem Laporan Kinerja Pegawai</p>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="username" class="form-label fw-semibold">Username</label>
                <input type="text"
                       name="username"
                       id="username"
                       class="form-control @error('username') is-invalid @enderror"
                       value="{{ old('username') }}"
                       placeholder="Masukkan username"
                       required
                       autofocus
                       autocomplete="username">
            </div>

            <div class="mb-3">
                <label for="password" class="form-label fw-semibold">Password</label>
                <input type="password"
                       name="password"
                       id="password"
                       class="form-control @error('password') is-invalid @enderror"
                       placeholder="Masukkan password"
                       required
                       autocomplete="current-password">
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="remember" name="remember" value="1">
                <label class="form-check-label text-muted" for="remember">Ingat Saya</label>
            </div>

            <button type="submit" class="btn btn-kpu w-100 py-2">
                Masuk
            </button>
        </form>

        <div class="text-center mt-4 text-muted small">
            &copy; {{ date('Y') }} Komisi Pemilihan Umum Kota Bukittinggi
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
