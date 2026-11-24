<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kode verifikasi CariKopi</title>
</head>
<body style="margin:0;padding:24px;background:#f4ece2;font-family:Arial,Helvetica,sans-serif;color:#2b1d14">
    <div style="max-width:480px;margin:0 auto;background:#ffffff;border-radius:12px;padding:32px">
        <p style="margin:0 0 4px;font-size:18px;font-weight:bold;color:#7a4a1c">CariKopi</p>
        <h1 style="margin:16px 0 8px;font-size:20px">Verifikasi emailmu</h1>
        <p style="margin:0 0 20px;line-height:1.5">
            Masukkan kode ini di form pendaftaran untuk membuktikan bahwa email ini milikmu:
        </p>
        <p style="margin:0 0 20px;text-align:center;font-size:34px;font-weight:bold;letter-spacing:8px;background:#f4ece2;border-radius:8px;padding:16px">
            {{ $code }}
        </p>
        <p style="margin:0 0 8px;line-height:1.5">Kode berlaku selama {{ $minutes }} menit.</p>
        <p style="margin:0;line-height:1.5;color:#6b5b4e;font-size:13px">
            Jangan bagikan kode ini kepada siapa pun. Kalau kamu tidak sedang mendaftar di CariKopi, abaikan email ini.
        </p>
    </div>
</body>
</html>
