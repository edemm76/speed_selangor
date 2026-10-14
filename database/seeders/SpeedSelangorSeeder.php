<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus as S;
use App\Enums\PermitType;
use App\Enums\VendorStatus;
use App\Models\Administrator;
use App\Models\Application;
use App\Models\Notification;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class SpeedSelangorSeeder extends Seeder
{
    private Administrator $admin;
    private Administrator $officer;

    public function run(): void
    {
        /* ---------------- Administrators (PBT) ---------------- */

        $this->admin = Administrator::create([
            'name' => 'Pentadbir Sistem',
            'email' => 'admin@speedselangor.test',
            'no_phone' => '03-32891234',
            'password_hash' => 'Admin@123',
            'role' => 'super_admin',
        ]);

        $this->officer = Administrator::create([
            'name' => 'Nur Aisyah binti Kamal',
            'email' => 'pegawai@speedselangor.test',
            'no_phone' => '03-32895678',
            'password_hash' => 'Pegawai@123',
            'role' => 'pegawai',
        ]);

        /* ---------------- Vendors ---------------- */

        $vendor = Vendor::create([
            'company_name' => 'Binaan Maju Sdn Bhd',
            'registration_no' => '201901012345',
            'pic_name' => 'Ahmad Faizal bin Rahman',
            'email' => 'vendor@speedselangor.test',
            'no_phone' => '012-3456789',
            'address' => 'No. 12, Jalan Klinik, 45000 Kuala Selangor, Selangor',
            'password_hash' => 'Vendor@123',
            'status' => VendorStatus::Active,
        ]);

        $vendor2 = Vendor::create([
            'company_name' => 'Teguh Jaya Enterprise',
            'registration_no' => 'SA0123456-X',
            'pic_name' => 'Lim Wei Ming',
            'email' => 'vendor2@speedselangor.test',
            'no_phone' => '013-9876543',
            'address' => 'Lot 88, Jalan Bukit Belimbing, 45000 Kuala Selangor, Selangor',
            'password_hash' => 'Vendor@123',
            'status' => VendorStatus::Active,
        ]);

        Vendor::create([
            'company_name' => 'Cahaya Bina Resources',
            'registration_no' => '202301045678',
            'pic_name' => 'Siti Hajar binti Osman',
            'email' => 'pending@speedselangor.test',
            'no_phone' => '017-2223344',
            'address' => 'No. 5, Jalan Tanjung Karang, 45500 Tanjong Karang, Selangor',
            'password_hash' => 'Vendor@123',
            'status' => VendorStatus::Pending, // waiting for admin to activate (Step 7)
        ]);

        Vendor::create([
            'company_name' => 'Kontraktor Lama Sdn Bhd',
            'registration_no' => '200801099999',
            'pic_name' => 'Rajesh a/l Kumar',
            'email' => 'suspended@speedselangor.test',
            'no_phone' => '019-5556677',
            'address' => 'No. 3, Jalan Ijok, 45600 Bestari Jaya, Selangor',
            'password_hash' => 'Vendor@123',
            'status' => VendorStatus::Suspended,
        ]);

        /* ---------------- Applications ----------------
           Each entry: [vendor, permit, title, address, days ago submitted, status path]
           The status path is replayed into application_status_histories,
           one day apart, so the timelines look realistic.                      */

        $this->makeApplication($vendor, PermitType::KebenaranMerancang,
            'Cadangan Pembinaan 2 Unit Rumah Kedai 2 Tingkat',
            'Lot 1201, Jalan Raja Abdullah, 45000 Kuala Selangor',
            null, [S::Draft]);

        $this->makeApplication($vendor, PermitType::UbahsuaiRumah,
            'Ubahsuai Tambahan Dapur Rumah Teres',
            'No. 23, Taman Bestari, 45000 Kuala Selangor',
            2, [S::Submitted]);

        $this->makeApplication($vendor, PermitType::PelanBangunan,
            'Pembinaan Bangunan Pejabat 3 Tingkat',
            'Lot 455, Jalan Kapar, 45000 Kuala Selangor',
            6, [S::Submitted, S::UnderReview]);

        $actionApp = $this->makeApplication($vendor, PermitType::KebenaranMerancang,
            'Cadangan Pecah Sempadan Tanah Pertanian',
            'Lot 7788, Mukim Ijok, 45600 Kuala Selangor',
            10, [S::Submitted, S::UnderReview, S::ActionRequired],
            'Sila kemukakan salinan geran tanah yang terkini dan jelas.');

        $this->makeApplication($vendor, PermitType::KebenaranMerancang,
            'Pembinaan Gudang Simpanan Satu Tingkat',
            'Lot 3030, Kawasan Perindustrian Bukit Rotan, 45700 Kuala Selangor',
            20, [S::Submitted, S::UnderReview, S::Verified]); // overdue

        $approvedApp = $this->makeApplication($vendor, PermitType::UbahsuaiRumah,
            'Ubahsuai Anjung Rumah Berkembar',
            'No. 8, Taman Seri Tanjung, 45500 Tanjong Karang',
            12, [S::Submitted, S::UnderReview, S::Verified, S::Approved]);

        $this->makeApplication($vendor2, PermitType::PelanBangunan,
            'Pembinaan Surau Kampung 1 Tingkat',
            'Lot 99, Kampung Kuantan, 45000 Kuala Selangor',
            18, [S::Submitted, S::UnderReview]); // overdue

        $this->makeApplication($vendor2, PermitType::KebenaranMerancang,
            'Cadangan Stesen Minyak di Tanah Pertanian',
            'Lot 1500, Jalan Kuala Selangor - Sabak Bernam',
            9, [S::Submitted, S::UnderReview, S::Rejected],
            'Lokasi bercanggah dengan Rancangan Tempatan Daerah Kuala Selangor.');

        /* ---------------- Notifications ---------------- */

        $this->notifyVendor($vendor, $actionApp,
            'Tindakan Diperlukan',
            "Permohonan {$actionApp->reference_no} memerlukan maklumat tambahan. Sila semak catatan pegawai.");

        $this->notifyVendor($vendor, $approvedApp,
            'Permohonan Diluluskan',
            "Tahniah! Permohonan {$approvedApp->reference_no} telah diluluskan.");

        $newest = Application::where('current_status', S::Submitted->value)->latest('submitted_at')->first();
        $this->notifyAdmins($newest,
            'Permohonan Baharu',
            "Permohonan baharu {$newest->reference_no} telah dihantar oleh {$newest->vendor->company_name}.");
    }

    /**
     * Create one application and replay its status history.
     */
    private function makeApplication(
        Vendor $vendor,
        PermitType $permit,
        string $title,
        string $address,
        ?int $daysAgo,
        array $statusPath,
        ?string $lastRemark = null,
    ): Application {
        $submittedAt = $daysAgo !== null ? now()->subDays($daysAgo)->setTime(9, 30) : null;

        $application = Application::create([
            'vendor_id' => $vendor->vendor_id,
            'reference_no' => $submittedAt ? Application::generateReferenceNo() : null,
            'permit_type' => $permit,
            'project_title' => $title,
            'project_description' => 'Permohonan contoh untuk tujuan ujian sistem.',
            'site_address' => $address,
            // random point around Kuala Selangor town
            'latitude' => 3.3400000 + mt_rand(-300, 300) / 10000,
            'longitude' => 101.2500000 + mt_rand(-300, 300) / 10000,
            'current_status' => end($statusPath),
            'submitted_at' => $submittedAt,
        ]);

        if (! $submittedAt) {
            return $application; // drafts have no history yet
        }

        $last = count($statusPath) - 1;

        foreach ($statusPath as $i => $status) {
            $byVendor = $status === S::Submitted;
            $at = $submittedAt->copy()->addDays($i)->addHours($i);

            $application->statusHistories()->forceCreate([
                'application_id' => $application->application_id,
                'status' => $status->value,
                'updated_by_admin_id' => $byVendor ? null : $this->officer->admin_id,
                'remarks' => $i === $last && $lastRemark ? $lastRemark : $this->defaultRemark($status),
                'created_at' => $at,
            ]);

            // Verification decisions also go to the verifications table
            if (in_array($status, [S::ActionRequired, S::Verified])) {
                $application->verifications()->forceCreate([
                    'application_id' => $application->application_id,
                    'verified_by_admin_id' => $this->officer->admin_id,
                    'decision' => $status->value,
                    'notes' => $i === $last && $lastRemark ? $lastRemark : $this->defaultRemark($status),
                    'created_at' => $at,
                ]);
            }
        }

        return $application;
    }

    private function defaultRemark(S $status): string
    {
        return match ($status) {
            S::Submitted => 'Permohonan dihantar oleh pemohon.',
            S::UnderReview => 'Permohonan sedang disemak oleh Jabatan Perancangan.',
            S::ActionRequired => 'Maklumat tambahan diperlukan.',
            S::Verified => 'Maklumat permohonan telah disahkan dan dimajukan ke proses seterusnya.',
            S::Approved => 'Permohonan diluluskan.',
            S::Rejected => 'Permohonan ditolak.',
            default => '',
        };
    }

    private function notifyVendor(Vendor $vendor, Application $application, string $title, string $message): void
    {
        $notification = Notification::create([
            'title' => $title,
            'message' => $message,
            'target_type' => 'vendor',
        ]);

        $notification->recipients()->create([
            'vendor_id' => $vendor->vendor_id,
            'application_id' => $application->application_id,
        ]);
    }

    private function notifyAdmins(Application $application, string $title, string $message): void
    {
        $notification = Notification::create([
            'title' => $title,
            'message' => $message,
            'target_type' => 'admin',
        ]);

        foreach (Administrator::all() as $admin) {
            $notification->recipients()->create([
                'admin_id' => $admin->admin_id,
                'application_id' => $application->application_id,
            ]);
        }
    }
}