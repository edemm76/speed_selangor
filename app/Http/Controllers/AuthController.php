<?php

namespace App\Http\Controllers;

use App\Enums\VendorStatus;
use App\Models\Administrator;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * SSMS-UC-100 Manage Login and Registration
 * Login and logout for both Vendor and Administrator.
 */
class AuthController extends Controller
{
    /** Show the login page */
    public function showLogin(): View
    {
        return view('auth.login');
    }

    /** Check credentials, create the session, send the user to their dashboard */
    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', 'in:vendor,admin'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'role.required' => 'Sila pilih jenis pengguna.',
            'email.required' => 'Sila masukkan e-mel.',
            'email.email' => 'Format e-mel tidak sah.',
            'password.required' => 'Sila masukkan kata laluan.',
        ]);

        $remember = $request->boolean('remember');

        // Look up the account in the right table
        $user = $data['role'] === 'admin'
            ? Administrator::where('email', $data['email'])->first()
            : Vendor::where('email', $data['email'])->first();

        // [E1: Invalid Credential] - same message for wrong email or wrong password
        if (! $user || ! Hash::check($data['password'], $user->password_hash)) {
            return back()
                ->withInput($request->only('email', 'role', 'remember'))
                ->withErrors(['email' => 'E-mel atau kata laluan tidak sah.']);
        }

        // Vendors must be activated by an admin before they can log in
        if ($user instanceof Vendor && ! $user->isActive()) {
            $message = $user->status === VendorStatus::Pending
                ? 'Akaun anda masih menunggu pengesahan daripada pihak PBT.'
                : 'Akaun anda telah digantung. Sila hubungi pihak PBT.';

            return back()
                ->withInput($request->only('email', 'role', 'remember'))
                ->withErrors(['email' => $message]);
        }

        // [SSMS-REQ-102] authenticate and create the session
        Auth::guard($data['role'])->login($user, $remember);
        $request->session()->regenerate();

        // Redirect to the correct dashboard, or back to the page they tried to open
        // (only if that page belongs to their own area: /admin/... or /vendor/...)
        $home = $data['role'] === 'admin' ? route('admin.dashboard') : route('vendor.dashboard');
        $intended = $request->session()->pull('url.intended');
        $intendedPath = $intended ? (string) parse_url($intended, PHP_URL_PATH) : '';

        $target = str_starts_with($intendedPath, '/' . $data['role'] . '/') ? $intended : $home;

        return redirect()->to($target)
            ->with('success', 'Selamat datang, ' . ($user->name ?? $user->pic_name) . '!');
    }

    /** End the session */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();
        Auth::guard('vendor')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah log keluar.');
    }
}