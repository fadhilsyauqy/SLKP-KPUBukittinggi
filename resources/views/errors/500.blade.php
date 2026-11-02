<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Kesalahan Server | SLKP KPU Kota Bukittinggi</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/logo-kpu.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .error-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(119, 20, 20, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.05);
            max-width: 580px;
            width: 100%;
            overflow: hidden;
            text-align: center;
        }
        .error-header {
            background: linear-gradient(135deg, #771414 0%, #a71d1d 100%);
            color: #ffffff;
            padding: 2.5rem 2rem 2rem;
        }
        .error-code {
            font-size: 5rem;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -2px;
            color: #ffd700;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .btn-kpu {
            background: #771414;
            color: #ffffff;
            border-radius: 50px;
            font-weight: 600;
            padding: 0.6rem 1.75rem;
            border: none;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn-kpu:hover {
            background: #550c0c;
            color: #ffffff;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-header">
            <img src="{{ asset('assets/img/logo-kpu.png') }}" alt="Logo KPU" height="60" class="mb-3">
            <div class="error-code">500</div>
            <h4 class="fw-bold mt-2 mb-1">Terjadi Kesalahan Server</h4>
            <p class="text-white-50 mb-0 small">Sistem Laporan Kinerja Pegawai (SLKP) KPU Kota Bukittinggi</p>
        </div>
        <div class="p-4 p-md-5">
            <p class="text-secondary mb-4">
                Terjadi kendala teknis internal pada sistem. Tim administrator telah mencatat log kejadian ini.
            </p>
            <div class="d-flex justify-content-center gap-2">
                <a href="{{ url('/') }}" class="btn btn-kpu">
                    <i class="bi bi-house-door me-1"></i> Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>
</body>
</html>
