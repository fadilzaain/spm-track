<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - {{ config('app.name') }}</title>
    <style>
        /* Gaya sementara tanpa Vite, desain asli dikerjakan di M2 */
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center;
               font-family: system-ui, sans-serif; background: #f1f5f9; color: #0f172a; }
        .kartu { width: 100%; max-width: 380px; background: #fff; padding: 32px;
                 border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
        h1 { margin: 0 0 4px; font-size: 22px; }
        .sub { margin: 0 0 24px; color: #64748b; font-size: 14px; }
        label { display: block; margin: 16px 0 6px; font-size: 14px; font-weight: 600; }
        input[type=text], input[type=password] { width: 100%; padding: 10px 12px;
                 border: 1px solid #cbd5e1; border-radius: 8px; font-size: 15px; }
        .ingat { display: flex; align-items: center; gap: 8px; margin-top: 16px; font-size: 14px; }
        button { width: 100%; margin-top: 24px; padding: 11px; border: 0; border-radius: 8px;
                 background: #0f766e; color: #fff; font-size: 15px; font-weight: 600; cursor: pointer; }
        .galat { margin-top: 6px; color: #b91c1c; font-size: 13px; }
    </style>
</head>
<body>
    <main class="kartu">
        <h1>{{ config('app.name') }}</h1>
        <p class="sub">RSUD Kabupaten Jombang</p>

        <form method="POST" action="{{ route('login.proses') }}">
            {{-- Token CSRF wajib ada di setiap form POST --}}
            @csrf

            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="{{ old('username') }}"
                   autocomplete="username" autofocus>
            @error('username')
                <div class="galat">{{ $message }}</div>
            @enderror

            <label for="password">Password</label>
            <input type="password" id="password" name="password" autocomplete="current-password">
            @error('password')
                <div class="galat">{{ $message }}</div>
            @enderror

            <label class="ingat">
                <input type="checkbox" name="ingat_saya" value="1"> Ingat saya
            </label>

            <button type="submit">Masuk</button>
        </form>
    </main>
</body>
</html>