{{--
    Shown when the whole form is bigger than PHP allows (post_max_size in php.ini).
    The login session is not loaded yet at this point, so this page does not use the main layout.
--}}
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fail Terlalu Besar | {{ config('speed.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/speed.css') }}" rel="stylesheet">
</head>
<body>
    <main class="container py-5" style="max-width: 560px">
        <div class="card text-center">
            <div class="card-body p-4 p-md-5">
                <i class="bi bi-file-earmark-x display-5 text-danger"></i>
                <h1 class="h4 mt-3">Fail terlalu besar</h1>
                <p class="text-muted">
                    Jumlah saiz fail yang dimuat naik melebihi had sistem.
                    Setiap dokumen tidak boleh melebihi {{ config('speed.upload.max_kb') / 1024 }} MB.
                    Sila kecilkan fail (contohnya imbas semula pada resolusi lebih rendah) dan cuba lagi,
                    atau muat naik beberapa dokumen dahulu dan klik <strong>Simpan Draf</strong>.
                </p>
                <button type="button" class="btn btn-primary" onclick="history.back()">
                    <i class="bi bi-arrow-left"></i> Kembali ke Borang
                </button>
            </div>
        </div>
    </main>
</body>
</html>