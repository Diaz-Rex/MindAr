@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
    <style>
        .profile-grid {
            display: grid;
            grid-template-columns: minmax(220px, 0.7fr) minmax(0, 2fr);
            gap: 24px;
        }

        .profile-summary {
            text-align: center;
        }

        .profile-avatar {
            display: grid;
            width: 96px;
            height: 96px;
            margin: 0 auto 18px;
            place-items: center;
            border-radius: 50%;
            background: #2563eb;
            color: white;
            font-size: 34px;
            font-weight: 700;
        }

        .profile-summary h1 { margin-bottom: 6px; }
        .profile-summary p { color: #64748b; }

        .status-badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            background: #dcfce7;
            color: #166534;
            font-size: 13px;
            font-weight: 700;
        }

        .profile-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .profile-field label {
            display: block;
            margin-bottom: 7px;
            font-weight: 700;
        }

        .profile-field input,
        .profile-field textarea {
            width: 100%;
            min-height: 50px;
            padding: 13px 15px;
            border-radius: 12px;
        }

        .profile-field textarea {
            min-height: 145px;
            padding: 14px 15px;
            resize: vertical;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font: inherit;
        }

        .full-width { grid-column: 1 / -1; }

        .profile-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .section-heading {
            margin-top: 28px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
        }

        @media (max-width: 800px) {
            .profile-grid,
            .profile-form-grid { grid-template-columns: 1fr; }
            .full-width { grid-column: auto; }
        }
    </style>

    <div class="profile-grid">
        <section class="card profile-summary">
            <div class="profile-avatar">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>

            <h1>{{ $user->name }}</h1>
            <p>{{ $user->email }}</p>

            <span class="status-badge">
                {{ $user->active ? 'Active account' : 'Inactive account' }}
            </span>
        </section>

        <section class="card">
            <h1>Profile information</h1>
            <p>Update your personal information or change your password.</p>

            <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PUT')

                <div class="profile-form-grid">
                    <div class="profile-field">
                        <label for="name">Name</label>
                        <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required>
                    </div>

                    <div class="profile-field">
                        <label for="identification">Identification</label>
                        <input id="identification" type="text" name="identification" value="{{ old('identification', $user->identification) }}">
                    </div>

                    <div class="profile-field">
                        <label for="email">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required>
                    </div>

                    <div class="profile-field">
                        <label for="phone">Phone</label>
                        <input id="phone" type="text" name="phone" value="{{ old('phone', $user->phone) }}">
                    </div>

                    <div class="profile-field full-width">
                        <label for="location">Location</label>
                        <input id="location" type="text" name="location" value="{{ old('location', $user->location) }}">
                    </div>

                    <div class="profile-field full-width">
                        <label for="about">About</label>
                        <textarea id="about" name="about">{{ old('about', $user->about) }}</textarea>
                    </div>
                </div>

                <h2 class="section-heading">Change password</h2>
                <p>Leave these fields empty to keep your current password.</p>

                <div class="profile-form-grid">
                    <div class="profile-field">
                        <label for="password">New password</label>
                        <input id="password" type="password" name="password" autocomplete="new-password">
                    </div>

                    <div class="profile-field">
                        <label for="password_confirmation">Confirm password</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password">
                    </div>
                </div>

                <div class="profile-actions">
                    <button class="button" type="submit">Save changes</button>
                </div>
            </form>
        </section>
    </div>
@endsection
