@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <style>
        .welcome {
            position: relative;
            overflow: hidden;
            padding: 34px;
            border: 0;
            border-radius: 22px;
            background: linear-gradient(120deg, #123a88, #2563eb 62%, #60a5fa);
            color: white;
            box-shadow: 0 18px 38px rgba(37, 99, 235, .24);
        }

        .welcome:after {
            position: absolute;
            right: -80px;
            bottom: -150px;
            width: 360px;
            height: 360px;
            border: 40px solid rgba(255, 255, 255, .11);
            border-radius: 50%;
            content: "";
        }

        .welcome h1 {
            position: relative;
            color: white;
            font-size: clamp(29px, 4vw, 42px);
        }

        .welcome p {
            position: relative;
            max-width: 540px;
            color: #dbeafe;
        }

        .badge {
            position: relative;
            display: inline-block;
            margin: 0 0 10px;
            padding: 8px 11px;
            border: 1px solid rgba(255, 255, 255, .22);
            border-radius: 999px;
            background: rgba(255, 255, 255, .12);
            font-size: 13px;
            font-weight: 700;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-top: 22px;
        }

        .metric {
            padding: 22px;
            border: 1px solid #dbeafe;
            border-radius: 17px;
            background: rgba(255, 255, 255, .9);
            box-shadow: 0 10px 24px rgba(30, 88, 171, .07);
        }

        .metric-icon {
            display: grid;
            width: 42px;
            height: 42px;
            margin-bottom: 14px;
            place-items: center;
            border-radius: 12px;
            background: #e5f3ff;
            color: #2563eb;
            font-size: 19px;
        }

        .metric small {
            color: #64748b;
            font-weight: 700;
        }

        .metric strong {
            display: block;
            margin-top: 5px;
            color: #17376e;
            font-size: 22px;
        }

        @media (max-width: 780px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <section class="welcome">
        <p class="badge">3D workspace</p>
        <h1>Good to see you, {{ strtok(Auth::user()->name, ' ') }}.</h1>
        <p>Your workspace is ready. Use the navigation to manage your profile and access permissions.</p>
    </section>

    <section class="dashboard-grid">
        <article class="metric">
            <div class="metric-icon">◉</div>
            <small>Account</small>
            <strong>{{ Auth::user()->active ? 'Active' : 'Inactive' }}</strong>
        </article>

        <article class="metric">
            <div class="metric-icon">⌁</div>
            <small>Workspace</small>
            <strong>Ready</strong>
        </article>

        <article class="metric">
            <div class="metric-icon">⚙</div>
            <small>Access</small>
            <strong>Configured</strong>
        </article>
    </section>
@endsection
