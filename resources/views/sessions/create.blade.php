<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1.0">
        <title>Sign in · 3D Viewer</title>

        <style>
            * {
                box-sizing: border-box;
            }

            body {
                min-height: 100vh;
                margin: 0;
                display: grid;
                place-items: center;
                overflow: hidden;
                background: linear-gradient(135deg, #e9f6ff, #f8fbff 48%, #dff1ff);
                color: #17376e;
                font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
            }

            .orb {
                position: fixed;
                border-radius: 50%;
                filter: blur(2px);
                opacity: .55;
            }

            .orb.one {
                width: 470px;
                height: 470px;
                top: -190px;
                left: -130px;
                background: #93c5fd;
            }

            .orb.two {
                width: 380px;
                height: 380px;
                right: -120px;
                bottom: -170px;
                background: #60a5fa;
            }

            .login-card {
                position: relative;
                width: min(430px, calc(100% - 36px));
                padding: 38px;
                border: 1px solid rgba(255, 255, 255, .9);
                border-radius: 24px;
                background: rgba(255, 255, 255, .86);
                box-shadow: 0 24px 60px rgba(30, 88, 171, .18);
                backdrop-filter: blur(18px);
            }

            .brand {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 30px;
                font-size: 20px;
                font-weight: 800;
            }

            .mark {
                display: grid;
                width: 35px;
                height: 35px;
                place-items: center;
                border-radius: 11px;
                background: linear-gradient(135deg, #2563eb, #60a5fa);
                color: white;
            }

            .eyebrow {
                margin: 0 0 8px;
                color: #2563eb;
                font-size: 11px;
                font-weight: 800;
                letter-spacing: .12em;
                text-transform: uppercase;
            }

            h1 {
                margin: 0 0 8px;
                font-size: 31px;
                letter-spacing: -.04em;
            }

            p {
                margin: 0 0 25px;
                color: #64748b;
                line-height: 1.6;
            }

            .form-group {
                margin-bottom: 18px;
            }

            label {
                display: block;
                margin-bottom: 7px;
                font-size: 13px;
                font-weight: 750;
            }

            input {
                width: 100%;
                padding: 13px;
                border: 1px solid #cfe1fb;
                border-radius: 11px;
                background: #fbfdff;
                font: inherit;
            }

            input:focus {
                outline: 0;
                border-color: #3b82f6;
                box-shadow: 0 0 0 4px rgba(59, 130, 246, .13);
            }

            button {
                width: 100%;
                padding: 13px;
                border: 0;
                border-radius: 11px;
                background: linear-gradient(135deg, #2563eb, #3b82f6);
                box-shadow: 0 9px 18px rgba(37, 99, 235, .23);
                color: white;
                cursor: pointer;
                font: inherit;
                font-weight: 750;
            }

            .error-box {
                margin-bottom: 18px;
                padding: 12px 14px;
                border: 1px solid #fecaca;
                border-radius: 11px;
                background: #fff5f5;
                color: #b91c1c;
                font-size: 13px;
            }
        </style>
    </head>
    <body>
        <span class="orb one"></span>
        <span class="orb two"></span>

        <div class="login-card">
            <div class="brand">
                <span class="mark">✦</span>
                3D Viewer
            </div>

            <div class="eyebrow">Welcome back</div>
            <h1>Sign in to continue</h1>
            <p>Use your account credentials to access your workspace.</p>

            @if($errors->any())
                <div class="error-box">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ url('/sign-in') }}">
                @csrf

                <div class="form-group">
                    <label for="email">Email address</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        required
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <button type="submit">Sign in</button>
            </form>
        </div>
    </body>
</html>
