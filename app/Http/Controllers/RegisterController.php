<?php

namespace App\Http\Controllers;

use App\Enums\ApplicantType;
use App\Enums\VendorStatus;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * SSMS-UC-100 A1: Register [SSMS-REQ-103]
 * Any member of the public can register as an individual or a company.
 * The account is active straight away and the user is logged in.
 */
class RegisterController extends Controller
{
    /** Show the registration form */
    public function create(): View
    {
        return view('auth.register');
    }

    /** Validate, create the account, log in, go to the dashboard */
    public function store(Request $request): RedirectResponse
    {
        // Tidy the IC number to 900101-10-5678 before checking it
        if ($request->filled('ic_no')) {
            $digits = preg_replace('/\D/', '', $request->input('ic_no'));
            if (strlen($digits) === 12) {
                $request->merge(['ic_no' => substr($digits, 0, 6) . '-' . substr($digits, 6, 2) . '-' . substr($digits, 8)]);
            }
        }

        $isCompany = $request->input('applicant_type') === ApplicantType::Company->value;

        $data = $request->validate([
            'applicant_type' => ['required', Rule::enum(ApplicantType::class)],
            'pic_name' => ['required', 'string', 'max:255'],
            'ic_no' => [Rule::requiredIf(! $isCompany), 'nullable', 'regex:/^\d{6}-\d{2}-\d{4}$/', 'unique:vendors,ic_no'],
            'company_name' => [Rule::requiredIf($isCompany), 'nullable', 'string', 'max:255'],
            'registration_no' => [Rule::requiredIf($isCompany), 'nullable', 'string', 'max:50', 'unique:vendors,registration_no'],
            'email' => ['required', 'email', 'max:255', 'unique:vendors,email'],
            'no_phone' => ['required', 'regex:/^0\d{1,2}-?\d{7,8}$/'],
            'address' => ['required', 'string', 'max:500'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'agree' => ['accepted'],
        ], [
            'required' => 'Sila isi :attribute.',
            'ic_no.required' => 'Sila isi No. Kad Pengenalan.',
            'company_name.required' => 'Sila isi Nama Syarikat.',
            'registration_no.required' => 'Sila isi No. Pendaftaran Syarikat (SSM).',
            'ic_no.regex' => 'No. Kad Pengenalan mesti 12 digit, contoh 900101-10-5678.',
            'no_phone.regex' => 'No. telefon tidak sah, contoh 012-3456789.',
            'email.email' => 'Format e-mel tidak sah.',
            'unique' => ':attribute ini telah didaftarkan. Sila log masuk atau gunakan yang lain.',
            'password.confirmed' => 'Pengesahan kata laluan tidak sepadan.',
            'password.min' => 'Kata laluan mesti sekurang-kurangnya 8 aksara.',
            'password.letters' => 'Kata laluan mesti mengandungi sekurang-kurangnya satu huruf.',
            'password.numbers' => 'Kata laluan mesti mengandungi sekurang-kurangnya satu nombor.',
            'agree.accepted' => 'Sila tandakan perakuan bahawa maklumat yang diberikan adalah benar.',
            'max' => ':attribute terlalu panjang.',
        ], [
            'applicant_type' => 'jenis pemohon',
            'pic_name' => $isCompany ? 'Nama Pegawai Untuk Dihubungi' : 'Nama Penuh',
            'ic_no' => 'No. Kad Pengenalan',
            'company_name' => 'Nama Syarikat',
            'registration_no' => 'No. Pendaftaran Syarikat (SSM)',
            'email' => 'E-mel',
            'no_phone' => 'No. Telefon',
            'address' => 'Alamat',
            'password' => 'Kata Laluan',
        ]);

        $vendor = Vendor::create([
            'applicant_type' => $data['applicant_type'],
            'pic_name' => $data['pic_name'],
            // keep only the fields that belong to the chosen type
            'ic_no' => $isCompany ? null : $data['ic_no'],
            'company_name' => $isCompany ? $data['company_name'] : null,
            'registration_no' => $isCompany ? $data['registration_no'] : null,
            'email' => $data['email'],
            'no_phone' => $data['no_phone'],
            'address' => $data['address'],
            'password_hash' => $data['password'], // hashed automatically by the model
            'status' => VendorStatus::Active,     // active straight away (decision 2026-10-14)
        ]);

        Auth::guard('vendor')->login($vendor);
        $request->session()->regenerate();

        return redirect()->route('vendor.dashboard')
            ->with('success', 'Pendaftaran berjaya. Selamat datang, ' . $vendor->pic_name . '!');
    }
}