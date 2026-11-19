<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * SSMS-UC-200 Manage User (own profile)
 * Used by both roles: /vendor/profile (Pemohon) and /admin/profile (Pegawai PBT).
 * - view profile [SSMS-REQ-201]
 * - update phone / address [SSMS-REQ-202, 203]
 * - change password, current password checked [A1: SSMS-REQ-204, E1: SSMS-REQ-206]
 * - cancel = "Batal" returns to the profile without saving [A2: SSMS-REQ-205]
 */
class ProfileController extends Controller
{
    /** 'admin' for /admin/profile..., 'vendor' for /vendor/profile... */
    private function role(Request $request): string
    {
        return $request->routeIs('admin.*') ? 'admin' : 'vendor';
    }

    /** The logged-in Administrator or Vendor */
    private function user(Request $request)
    {
        return Auth::guard($this->role($request))->user();
    }

    /** Show the profile page */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'role' => $this->role($request),
            'user' => $this->user($request),
        ]);
    }

    /** Save the profile details */
    public function update(Request $request): RedirectResponse
    {
        $role = $this->role($request);
        $user = $this->user($request);

        $phone = ['required', 'regex:/^0\d{1,2}-?\d{7,8}$/'];

        if ($role === 'admin') {
            $rules = [
                'name' => ['required', 'string', 'max:255'],
                'no_phone' => $phone,
            ];
        } else {
            $rules = [
                'no_phone' => $phone,
                'address' => ['required', 'string', 'max:500'],
            ];
            // A company may change its contact person; an individual's name follows the IC
            if ($user->isCompany()) {
                $rules['pic_name'] = ['required', 'string', 'max:255'];
            }
        }

        $data = $request->validate($rules, [
            'required' => 'Sila isi :attribute.',
            'no_phone.regex' => 'No. telefon tidak sah, contoh 012-3456789.',
            'max' => ':attribute terlalu panjang.',
        ], [
            'name' => 'Nama',
            'pic_name' => 'Nama Pegawai Untuk Dihubungi',
            'no_phone' => 'No. Telefon',
            'address' => 'Alamat',
        ]);

        $user->fill($data)->save();

        return redirect()->route("$role.profile.edit")
            ->with('success', 'Maklumat profil berjaya dikemas kini.');
    }

    /** Change the password (current password must be correct) */
    public function updatePassword(Request $request): RedirectResponse
    {
        $role = $this->role($request);
        $user = $this->user($request);

        // Errors go to the 'password' bag so they show inside the password card
        $data = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password:' . $role],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(8)->letters()->numbers()],
        ], [
            'current_password.required' => 'Sila masukkan kata laluan semasa.',
            'current_password.current_password' => 'Kata laluan semasa tidak betul.',
            'password.required' => 'Sila masukkan kata laluan baharu.',
            'password.confirmed' => 'Pengesahan kata laluan tidak sepadan.',
            'password.different' => 'Kata laluan baharu mesti berbeza daripada kata laluan semasa.',
            'password.min' => 'Kata laluan mesti sekurang-kurangnya 8 aksara.',
            'password.letters' => 'Kata laluan mesti mengandungi sekurang-kurangnya satu huruf.',
            'password.numbers' => 'Kata laluan mesti mengandungi sekurang-kurangnya satu nombor.',
        ]);

        $user->password_hash = $data['password'];    // hashed automatically by the model
        $user->setRememberToken(Str::random(60));    // "remember me" on other devices stops working
        $user->save();

        $request->session()->regenerate();

        return redirect()->route("$role.profile.edit")
            ->with('success', 'Kata laluan berjaya ditukar.');
    }
}