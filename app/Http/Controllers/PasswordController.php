<?php

namespace App\Http\Controllers;

use App\Notifications\ResetPasswordNotification;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

/**
 * SSMS-UC-100 A2: Forgot Password [SSMS-REQ-103]
 * 1. The user asks for a reset link (forgot-password page).
 * 2. The system e-mails a link that works for 60 minutes.
 * 3. The user opens the link and sets a new password (reset-password page).
 * Works for both roles: 'vendor' (Pemohon) and 'admin' (Pegawai PBT).
 */
class PasswordController extends Controller
{
    /** Password broker for each role (config/auth.php -> passwords) */
    private function broker(string $role)
    {
        return Password::broker($role === 'admin' ? 'administrators' : 'vendors');
    }

    /** Step 1: form to ask for the reset link */
    public function create(Request $request): View
    {
        return view('auth.forgot-password', [
            'role' => $request->query('role') === 'admin' ? 'admin' : 'vendor',
        ]);
    }

    /** Step 2: send the reset link by e-mail */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', 'in:vendor,admin'],
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Sila masukkan e-mel.',
            'email.email' => 'Format e-mel tidak sah.',
        ]);

        $devLink = null;

        $this->broker($data['role'])->sendResetLink(
            ['email' => $data['email']],
            function (CanResetPassword $user, string $token) use ($data, &$devLink) {
                $user->notify(new ResetPasswordNotification($token, $data['role']));

                // While developing (no real e-mail server), also show the link on screen
                if (app()->isLocal() && config('mail.default') === 'log') {
                    $devLink = ResetPasswordNotification::resetUrl($data['role'], $token, $user->getEmailForPasswordReset());
                }
            }
        );

        // Same message whether or not the e-mail exists, so nobody can use
        // this form to find out which e-mails are registered.
        return back()
            ->withInput($request->only('email', 'role'))
            ->with('success', 'Jika e-mel ini berdaftar, pautan untuk menetapkan semula kata laluan telah dihantar. Sila semak e-mel anda.')
            ->with('dev_reset_link', $devLink);
    }

    /** Step 3: form to choose the new password (opened from the e-mail link) */
    public function edit(Request $request, string $role, string $token): View
    {
        return view('auth.reset-password', [
            'role' => $role,
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    /** Step 4: save the new password */
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', 'in:vendor,admin'],
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ], [
            'email.required' => 'Sila masukkan e-mel.',
            'email.email' => 'Format e-mel tidak sah.',
            'password.required' => 'Sila masukkan kata laluan baharu.',
            'password.confirmed' => 'Pengesahan kata laluan tidak sepadan.',
            'password.min' => 'Kata laluan mesti sekurang-kurangnya 8 aksara.',
            'password.letters' => 'Kata laluan mesti mengandungi sekurang-kurangnya satu huruf.',
            'password.numbers' => 'Kata laluan mesti mengandungi sekurang-kurangnya satu nombor.',
        ]);

        $status = $this->broker($data['role'])->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password) {
                $user->password_hash = $password;          // hashed automatically by the model
                $user->setRememberToken(Str::random(60));  // logs out "remember me" on other devices
                $user->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Pautan set semula tidak sah atau telah tamat tempoh. Sila mohon pautan baharu.']);
        }

        return redirect()->route('login', ['role' => $data['role']])
            ->with('success', 'Kata laluan berjaya ditukar. Sila log masuk dengan kata laluan baharu.');
    }
}