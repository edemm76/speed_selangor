<?php

namespace Database\Seeders;

use App\Enums\ApplicantType;
use App\Enums\ApplicationStatus as S;
use App\Enums\MeetingResult;
use App\Enums\PermitType;
use App\Enums\VendorStatus;
use App\Models\Administrator;
use App\Models\Application;
use App\Models\MeetingDecision;
use App\Models\Notification;
use App\Models\UserNotification;
use App\Models\Vendor;
use App\Services\Notifier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Example data: 20 applicants (10 individuals + 10 companies) and 20 complete
 * submitted applications (+ 1 draft), covering every status. Each application has
 * real sample PDF documents, a full status history, verification records, meeting
 * decisions (for decided applications) and the notifications each step would send.
 *
 * Run with: php artisan migrate:fresh --seed
 */
class SpeedSelangorSeeder extends Seeder
{
    private Administrator $officer;

    public function run(): void
    {
        // Start with clean example files (database is wiped by migrate:fresh)
        $disk = Storage::disk(config('speed.upload.disk'));
        foreach (['applications', 'meetings', 'reports'] as $folder) {
            $disk->deleteDirectory($folder);
        }

        /* ---------------- Administrators (PBT) ---------------- */

        Administrator::create([
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

        /* ---------------- Applicants: 10 companies + 10 individuals ----------------
           All passwords: Vendor@123                                                  */

        $v = [];
        // Companies
        $v['binaan'] = $this->company('Binaan Maju Sdn Bhd', '201901012345', 'Ahmad Faizal bin Rahman', 'vendor@speedselangor.test', '012-3456789', 'No. 12, Jalan Klinik, 45000 Kuala Selangor, Selangor');
        $v['teguh'] = $this->company('Teguh Jaya Enterprise', 'SA0123456-X', 'Lim Wei Ming', 'vendor2@speedselangor.test', '013-9876543', 'Lot 88, Jalan Bukit Belimbing, 45000 Kuala Selangor, Selangor');
        $v['cahaya'] = $this->company('Cahaya Bina Resources', '202301045678', 'Siti Hajar binti Osman', 'pending@speedselangor.test', '017-2223344', 'No. 5, Jalan Tanjung Karang, 45500 Tanjong Karang, Selangor', VendorStatus::Pending);
        $v['lama'] = $this->company('Kontraktor Lama Sdn Bhd', '200801099999', 'Rajesh a/l Kumar', 'suspended@speedselangor.test', '019-5556677', 'No. 3, Jalan Ijok, 45600 Bestari Jaya, Selangor', VendorStatus::Suspended);
        $v['alam'] = $this->company('Perunding Alam Bina', '201501023456', 'Ir. Mohd Fadzli bin Hamid', 'alambina@speedselangor.test', '03-32894455', 'No. 21, Jalan Sultan Ibrahim, 45000 Kuala Selangor, Selangor');
        $v['mega'] = $this->company('Mega Kontraktor Sdn Bhd', '201201034567', 'Tan Chee Keong', 'megakontraktor@speedselangor.test', '012-6677889', 'Lot 15, Kawasan Perindustrian Bukit Rotan, 45700 Kuala Selangor, Selangor');
        $v['hijau'] = $this->company('Hijau Lestari Development Sdn Bhd', '201801045678', 'Nor Azlina binti Yusof', 'hijaulestari@speedselangor.test', '019-3344556', 'No. 8, Jalan Kapar, 45000 Kuala Selangor, Selangor');
        $v['teknik'] = $this->company('Teknik Jaya Engineering', 'SA0987654-T', 'Muthu a/l Ramasamy', 'teknikjaya@speedselangor.test', '016-2233445', 'No. 4, Jalan Raja Abdullah, 45000 Kuala Selangor, Selangor');
        $v['sinar'] = $this->company('Sinar Harapan Properties Sdn Bhd', '202001056789', 'Wong Mei Ling', 'sinarharapan@speedselangor.test', '012-9988776', 'Lot 230, Jalan Bukit Melawati, 45000 Kuala Selangor, Selangor');
        $v['bumi'] = $this->company('Bumi Selatan Builders', 'SA0456789-B', 'Kamarul Ariffin bin Daud', 'bumiselatan@speedselangor.test', '013-4455667', 'No. 17, Jalan Tengku Ampuan Jemaah, 45500 Tanjong Karang, Selangor');

        // Individuals
        $v['hafiz'] = $this->individual('Mohd Hafiz bin Ismail', '900101-10-5678', 'individu@speedselangor.test', '011-23456789', 'No. 15, Jalan Melati 3, Taman Melati, 45000 Kuala Selangor, Selangor');
        $v['siti'] = $this->individual('Siti Aminah binti Ahmad', '880512-10-6234', 'sitiaminah@speedselangor.test', '012-3344112', 'No. 7, Jalan Bestari 2, Taman Bestari, 45000 Kuala Selangor, Selangor');
        $v['rahim'] = $this->individual('Rahim bin Abdullah', '750823-10-5121', 'rahim@speedselangor.test', '019-8877665', 'No. 33, Jalan Seri Tanjung 4, Taman Seri Tanjung, 45500 Tanjong Karang, Selangor');
        $v['nurul'] = $this->individual('Nurul Huda binti Hassan', '920304-10-5568', 'nurulhuda@speedselangor.test', '017-6655443', 'No. 2, Jalan Melawati 6, Taman Melawati, 45000 Kuala Selangor, Selangor');
        $v['lim'] = $this->individual('Lim Ah Kow', '680915-10-5013', 'limahkow@speedselangor.test', '012-2211334', 'No. 45, Jalan Pasar Baru, 45000 Kuala Selangor, Selangor');
        $v['kavitha'] = $this->individual('Kavitha a/p Raman', '850706-10-5432', 'kavitha@speedselangor.test', '016-7788990', 'No. 19, Jalan Ijok Permai 1, Taman Ijok Permai, 45600 Bestari Jaya, Selangor');
        $v['zaki'] = $this->individual('Ahmad Zaki bin Mansor', '790228-10-5677', 'ahmadzaki@speedselangor.test', '013-5566778', 'No. 10, Jalan Kampung Kuantan, 45000 Kuala Selangor, Selangor');
        $v['zainab'] = $this->individual('Zainab binti Omar', '820419-10-5890', 'zainab@speedselangor.test', '011-33445566', 'No. 28, Jalan Sungai Sembilang, 45800 Jeram, Selangor');
        $v['farid'] = $this->individual('Farid bin Kassim', '950110-10-5245', 'farid@speedselangor.test', '014-2233446', 'No. 6, Jalan Assam Jawa, 45700 Kuala Selangor, Selangor');
        $v['mei'] = $this->individual('Chong Mei Yee', '970621-10-5786', 'meiyee@speedselangor.test', '012-8899001', 'No. 11, Jalan Bukit Rotan 2, 45700 Kuala Selangor, Selangor');

        /* ---------------- Applications ----------------
           Steps: [status, gap in days since the previous step, remark, by vendor?]
           A step for Approved / Rejected after Verified records a meeting decision.  */

        // A draft (only the applicant sees it)
        $this->application($v['binaan'], PermitType::KebenaranMerancang,
            'Cadangan Pembinaan 2 Unit Rumah Kedai 2 Tingkat', 'Lot 1201, Jalan Raja Abdullah, 45000 Kuala Selangor',
            null, []);

        // SS-2026-0001 Dihantar (new)
        $this->application($v['binaan'], PermitType::UbahsuaiRumah,
            'Ubahsuai Tambahan Dapur Rumah Teres', 'No. 23, Taman Bestari, 45000 Kuala Selangor',
            2, [[S::Submitted]]);

        // SS-2026-0002 Dalam Semakan
        $this->application($v['binaan'], PermitType::PelanBangunan,
            'Pembinaan Bangunan Pejabat 3 Tingkat', 'Lot 455, Jalan Kapar, 45000 Kuala Selangor',
            6, [[S::Submitted], [S::UnderReview, 1]]);

        // SS-2026-0003 Tindakan Diperlukan
        $this->application($v['binaan'], PermitType::KebenaranMerancang,
            'Cadangan Pecah Sempadan Tanah Pertanian', 'Lot 7788, Mukim Ijok, 45600 Kuala Selangor',
            10, [[S::Submitted], [S::UnderReview, 1], [S::ActionRequired, 1, 'Dokumen perlu dikemas kini: Salinan Geran Tanah. Sila kemukakan salinan geran tanah yang terkini dan jelas.']]);

        // SS-2026-0004 Disahkan, waiting for the meeting (overdue)
        $this->application($v['binaan'], PermitType::KebenaranMerancang,
            'Pembinaan Gudang Simpanan Satu Tingkat', 'Lot 3030, Kawasan Perindustrian Bukit Rotan, 45700 Kuala Selangor',
            20, [[S::Submitted], [S::UnderReview, 1], [S::Verified, 1]]);

        // SS-2026-0005 Diluluskan (Lulus) in 3 days
        $this->application($v['binaan'], PermitType::UbahsuaiRumah,
            'Ubahsuai Anjung Rumah Berkembar', 'No. 8, Taman Seri Tanjung, 45500 Tanjong Karang',
            12, [[S::Submitted], [S::UnderReview, 1], [S::Verified, 1], [S::Approved, 1]],
            ['result' => MeetingResult::Lulus, 'no' => '21/2026']);

        // SS-2026-0006 Dalam Semakan (overdue)
        $this->application($v['teguh'], PermitType::PelanBangunan,
            'Pembinaan Surau Kampung 1 Tingkat', 'Lot 99, Kampung Kuantan, 45000 Kuala Selangor',
            18, [[S::Submitted], [S::UnderReview, 1]]);

        // SS-2026-0007 Ditolak at the meeting
        $this->application($v['teguh'], PermitType::KebenaranMerancang,
            'Cadangan Stesen Minyak di Tanah Pertanian', 'Lot 1500, Jalan Kuala Selangor - Sabak Bernam',
            9, [[S::Submitted], [S::UnderReview, 1], [S::Verified, 1], [S::Rejected, 1]],
            ['result' => MeetingResult::Ditolak, 'no' => '22/2026',
             'reason' => 'Cadangan bercanggah dengan zon guna tanah pertanian dalam Rancangan Tempatan Daerah Kuala Selangor.']);

        // SS-2026-0008 Dalam Semakan
        $this->application($v['hafiz'], PermitType::UbahsuaiRumah,
            'Ubahsuai Bumbung dan Tambahan Bilik', 'No. 15, Jalan Melati 3, Taman Melati, 45000 Kuala Selangor',
            3, [[S::Submitted], [S::UnderReview, 1]]);

        // SS-2026-0009 Dihantar
        $this->application($v['siti'], PermitType::UbahsuaiRumah,
            'Tambahan Bilik Tidur di Tingkat Atas', 'No. 7, Jalan Bestari 2, Taman Bestari, 45000 Kuala Selangor',
            1, [[S::Submitted]]);

        // SS-2026-0010 Dihantar
        $this->application($v['alam'], PermitType::KebenaranMerancang,
            'Cadangan Pembangunan 24 Unit Rumah Teres Satu Tingkat', 'Lot 2045 & 2046, Mukim Jeram, 45800 Kuala Selangor',
            1, [[S::Submitted]]);

        // SS-2026-0011 Dihantar semula after Tindakan Diperlukan (back in review)
        $this->application($v['rahim'], PermitType::UbahsuaiRumah,
            'Ubahsuai Tambahan Garaj Kereta', 'No. 33, Jalan Seri Tanjung 4, Taman Seri Tanjung, 45500 Tanjong Karang',
            8, [[S::Submitted], [S::UnderReview, 1],
                [S::ActionRequired, 1, 'Dokumen perlu dikemas kini: Pelan Ubahsuai. Pelan tidak menunjukkan ukuran anjakan dari sempadan lot.'],
                [S::UnderReview, 3, 'Permohonan dihantar semula oleh pemohon selepas pembetulan. Catatan pemohon: Pelan ubahsuai telah dikemas kini dengan ukuran anjakan.', true, ['pelan_ubahsuai']]]);

        // SS-2026-0012 Tindakan Diperlukan
        $this->application($v['mega'], PermitType::PelanBangunan,
            'Pembinaan Kilang Satu Tingkat dan Pejabat Dua Tingkat', 'Lot 77, Kawasan Perindustrian Bukit Rotan, 45700 Kuala Selangor',
            5, [[S::Submitted], [S::UnderReview, 1],
                [S::ActionRequired, 1, 'Dokumen perlu dikemas kini: Surat Kelulusan KM. Surat yang dikemukakan telah tamat tempoh, sila kemukakan salinan terkini.']]);

        // SS-2026-0013 Disahkan (waiting for the meeting)
        $this->application($v['nurul'], PermitType::UbahsuaiRumah,
            'Ubahsuai Ruang Tamu dan Tambahan Porch', 'No. 2, Jalan Melawati 6, Taman Melawati, 45000 Kuala Selangor',
            7, [[S::Submitted], [S::UnderReview, 1], [S::Verified, 2]]);

        // SS-2026-0014 Disahkan (overdue, waiting for the meeting)
        $this->application($v['hijau'], PermitType::KebenaranMerancang,
            'Cadangan Pembangunan Taman Perumahan Mampu Milik', 'Lot 5120, Mukim Ijok, 45600 Kuala Selangor',
            16, [[S::Submitted], [S::UnderReview, 2], [S::Verified, 3]]);

        // SS-2026-0015 Diluluskan (Lulus) in 9 days
        $this->application($v['lim'], PermitType::UbahsuaiRumah,
            'Ubahsuai Kedai Runcit di Tingkat Bawah Rumah', 'No. 45, Jalan Pasar Baru, 45000 Kuala Selangor',
            25, [[S::Submitted], [S::UnderReview, 2], [S::Verified, 3], [S::Approved, 4]],
            ['result' => MeetingResult::Lulus, 'no' => '18/2026']);

        // SS-2026-0016 Diluluskan after a resubmission, in 18 days (late)
        $this->application($v['teknik'], PermitType::PelanBangunan,
            'Pembinaan Bengkel Kenderaan Berat', 'Lot 310, Jalan Kuala Selangor - Klang, 45000 Kuala Selangor',
            30, [[S::Submitted], [S::UnderReview, 1],
                [S::ActionRequired, 2, 'Dokumen perlu dikemas kini: Pelan Bangunan. Pelan tidak menunjukkan laluan kecemasan dan kemudahan bomba.'],
                [S::UnderReview, 4, 'Permohonan dihantar semula oleh pemohon selepas pembetulan. Catatan pemohon: Pelan bangunan telah dipinda.', true, ['pelan_bangunan']],
                [S::Verified, 2], [S::Approved, 9]],
            ['result' => MeetingResult::Lulus, 'no' => '17/2026']);

        // SS-2026-0017 Lulus dengan Pindaan
        $this->application($v['kavitha'], PermitType::UbahsuaiRumah,
            'Tambahan Dapur dan Bilik Air di Belakang Rumah', 'No. 19, Jalan Ijok Permai 1, Taman Ijok Permai, 45600 Bestari Jaya',
            14, [[S::Submitted], [S::UnderReview, 1], [S::Verified, 3], [S::Approved, 5]],
            ['result' => MeetingResult::LulusDenganPindaan, 'no' => '20/2026',
             'amendments' => 'Kurangkan unjuran bumbung belakang supaya tidak melebihi sempadan lot dan sediakan longkang di sepanjang sempadan.']);

        // SS-2026-0018 Lulus dengan Pindaan
        $this->application($v['sinar'], PermitType::KebenaranMerancang,
            'Cadangan Pembangunan Bercampur Rumah Kedai dan Pangsapuri', 'Lot 880, Jalan Bukit Melawati, 45000 Kuala Selangor',
            28, [[S::Submitted], [S::UnderReview, 2], [S::Verified, 3], [S::Approved, 6]],
            ['result' => MeetingResult::LulusDenganPindaan, 'no' => '18/2026',
             'amendments' => 'Lebarkan jalan masuk utama kepada 20 meter dan tambah rezab kawasan lapang kepada 10% daripada keluasan tapak.']);

        // SS-2026-0019 Ditolak at the meeting
        $this->application($v['zaki'], PermitType::UbahsuaiRumah,
            'Ubahsuai Tambahan Dua Tingkat di Sisi Rumah', 'No. 10, Jalan Kampung Kuantan, 45000 Kuala Selangor',
            21, [[S::Submitted], [S::UnderReview, 2], [S::Verified, 3], [S::Rejected, 5]],
            ['result' => MeetingResult::Ditolak, 'no' => '19/2026',
             'reason' => 'Tambahan binaan melepasi garisan sempadan lot dan tidak mematuhi keperluan anjakan bangunan.']);

        // SS-2026-0020 Diluluskan (Lulus) in 6 days
        $this->application($v['zainab'], PermitType::UbahsuaiRumah,
            'Ubahsuai Pagar dan Pintu Pagar Hadapan', 'No. 28, Jalan Sungai Sembilang, 45800 Jeram',
            18, [[S::Submitted], [S::UnderReview, 1], [S::Verified, 2], [S::Approved, 3]],
            ['result' => MeetingResult::Lulus, 'no' => '19/2026']);

        // Deadline alerts to officers for applications past the 14-day target
        Artisan::call('speed:notify-overdue');
    }

    /* ======================================================================
       Helpers
       ====================================================================== */

    private function company(string $name, string $ssm, string $pic, string $email, string $phone, string $address, VendorStatus $status = VendorStatus::Active): Vendor
    {
        return Vendor::create([
            'applicant_type' => ApplicantType::Company,
            'company_name' => $name,
            'registration_no' => $ssm,
            'pic_name' => $pic,
            'email' => $email,
            'no_phone' => $phone,
            'address' => $address,
            'password_hash' => 'Vendor@123',
            'status' => $status,
        ]);
    }

    private function individual(string $name, string $ic, string $email, string $phone, string $address): Vendor
    {
        return Vendor::create([
            'applicant_type' => ApplicantType::Individual,
            'pic_name' => $name,
            'ic_no' => $ic,
            'email' => $email,
            'no_phone' => $phone,
            'address' => $address,
            'password_hash' => 'Vendor@123',
            'status' => VendorStatus::Active,
        ]);
    }

    /**
     * Create one application with documents, then replay its steps:
     * status history, verification records, meeting decision and notifications.
     *
     * @param  int|null  $daysAgo  days since submission (null = draft)
     * @param  array  $steps  [status, gap days, remark, byVendor, replaced document types]
     * @param  array|null  $meeting  ['result', 'no', 'reason', 'amendments'] for the final decision
     */
    private function application(Vendor $vendor, PermitType $permit, string $title, string $address,
        ?int $daysAgo, array $steps, ?array $meeting = null): Application
    {
        $submittedAt = $daysAgo !== null ? now()->subDays($daysAgo)->setTime(9, 30) : null;

        $application = Application::create([
            'vendor_id' => $vendor->vendor_id,
            'reference_no' => $submittedAt ? Application::generateReferenceNo() : null,
            'permit_type' => $permit,
            'project_title' => $title,
            'project_description' => $this->description($permit, $title),
            'site_address' => $address,
            // a point around Kuala Selangor town
            'latitude' => 3.3400000 + mt_rand(-300, 300) / 10000,
            'longitude' => 101.2500000 + mt_rand(-300, 300) / 10000,
            'current_status' => S::Draft,
            'submitted_at' => $submittedAt,
        ]);

        if (! $submittedAt) {
            return $application; // a draft: no files or history yet
        }

        // Every required document, uploaded on the day of submission
        foreach ($permit->requiredDocuments() as $type => $label) {
            $this->storeDocument($application, $vendor, $type, $label, $submittedAt);
        }

        $at = $submittedAt->copy();
        $last = count($steps) - 1;

        foreach ($steps as $i => $step) {
            [$status, $gap, $remark, $byVendor, $replaced] = $step + [1 => 0, 2 => null, 3 => null, 4 => []];
            $byVendor ??= $status === S::Submitted;
            $at = $i === 0 ? $at : $at->copy()->addDays($gap)->addHours(1);
            $adminId = $byVendor ? null : $this->officer->admin_id;

            // Corrected documents uploaded again at resubmission
            foreach ($replaced as $type) {
                $old = $application->documents()->where('document_type', $type)->first();
                $disk = Storage::disk(config('speed.upload.disk'));
                $disk->delete($old->file_path);
                $old->delete();
                $this->storeDocument($application, $vendor, $type, $permit->requiredDocuments()[$type] . ' (Pindaan)', $at);
            }

            // Final decision after verification -> meeting record first
            if ($i === $last && $meeting && in_array($status, [S::Approved, S::Rejected])) {
                $decision = $this->meetingDecision($application, $meeting, $at);
                $remark = $decision->summaryLine();
            }

            $remark ??= $this->defaultRemark($status);

            $application->forceFill(['current_status' => $status])->save();
            $application->statusHistories()->forceCreate([
                'application_id' => $application->application_id,
                'status' => $status->value,
                'updated_by_admin_id' => $adminId,
                'remarks' => $remark,
                'created_at' => $at,
            ]);

            if (in_array($status, [S::ActionRequired, S::Verified])) {
                $application->verifications()->forceCreate([
                    'application_id' => $application->application_id,
                    'verified_by_admin_id' => $this->officer->admin_id,
                    'decision' => $status->value,
                    'notes' => $remark,
                    'created_at' => $at,
                ]);
            }

            // The notifications this step would have sent (same texts as the real system)
            $this->notify($application, $status, $remark, $adminId, $at);
        }

        // Keep the updated_at in step with the last change
        $application->forceFill(['updated_at' => $at])->saveQuietly();

        return $application;
    }

    /** Sample PDF for one required document */
    private function storeDocument(Application $application, Vendor $vendor, string $type, string $label, Carbon $at): void
    {
        $path = 'applications/' . $application->application_id . '/' . $type . '-' . Str::random(10) . '.pdf';

        Storage::disk(config('speed.upload.disk'))->put($path, $this->pdf([
            strtoupper($label),
            'DOKUMEN CONTOH UNTUK UJIAN SISTEM SAHAJA',
            '',
            'No. Rujukan : ' . $application->reference_no,
            'Pemohon     : ' . $vendor->displayName(),
            'Projek      : ' . $application->project_title,
            'Alamat Tapak: ' . $application->site_address,
            'Tarikh      : ' . $at->format('d/m/Y'),
            '',
            'Semua nama dan nombor adalah rekaan.',
        ]));

        $application->documents()->forceCreate([
            'application_id' => $application->application_id,
            'document_type' => $type,
            'file_name' => str_replace('_', '-', $type) . '.pdf',
            'file_path' => $path,
            'uploaded_at' => $at,
        ]);
    }

    /** Meeting decision (+ sample minutes PDF), held on the day of the final decision */
    private function meetingDecision(Application $application, array $meeting, Carbon $at): MeetingDecision
    {
        $result = $meeting['result'];
        $title = 'Mesyuarat Jawatankuasa OSC Bil. ' . $meeting['no'];
        $path = 'meetings/' . $application->application_id . '/minit-' . Str::random(10) . '.pdf';

        Storage::disk(config('speed.upload.disk'))->put($path, $this->pdf([
            'MINIT ' . strtoupper($title),
            'DOKUMEN CONTOH UNTUK UJIAN SISTEM SAHAJA',
            '',
            'Tarikh     : ' . $at->format('d/m/Y'),
            'Jabatan    : ' . config('speed.meeting.department'),
            'Pengerusi  : Tuan Haji Kamal bin Hassan',
            'Permohonan : ' . $application->reference_no . ' - ' . $application->project_title,
            'Keputusan  : ' . $result->label(),
        ]));

        $decision = $application->meetingDecision()->forceCreate([
            'application_id' => $application->application_id,
            'meeting_title' => $title,
            'meeting_date' => $at->toDateString(),
            'department' => config('speed.meeting.department'),
            'checked_by' => $this->officer->name . ' (Penolong Pegawai Perancang Bandar)',
            'approver_name' => 'Tuan Haji Kamal bin Hassan',
            'approver_position' => 'Pengerusi Jawatankuasa OSC',
            'result' => $result->value,
            'reason' => $meeting['reason'] ?? null,
            'amendments' => $meeting['amendments'] ?? null,
            'minutes_summary' => match ($result) {
                MeetingResult::Lulus => 'Jawatankuasa meneliti kertas pertimbangan dan bersetuju permohonan mematuhi keperluan perancangan.',
                MeetingResult::LulusDenganPindaan => 'Jawatankuasa bersetuju meluluskan permohonan tertakluk kepada pindaan yang dinyatakan.',
                MeetingResult::Ditolak => 'Jawatankuasa membincangkan kertas pertimbangan dan memutuskan untuk menolak permohonan atas sebab yang dinyatakan.',
            },
            'minutes_file_name' => 'minit-mesyuarat-' . str_replace('/', '-', $meeting['no']) . '.pdf',
            'minutes_file_path' => $path,
            'recorded_by_admin_id' => $this->officer->admin_id,
            'created_at' => $at,
            'updated_at' => $at,
        ]);

        $application->setRelation('meetingDecision', $decision);

        return $decision;
    }

    /**
     * Send the step's notifications through the real Notifier, then date them to the step.
     * Older notifications are marked as read so only recent ones show as new.
     */
    private function notify(Application $application, S $status, string $remark, ?int $adminId, Carbon $at): void
    {
        $lastNotification = Notification::max('notification_id') ?? 0;
        $lastRecipient = UserNotification::max('user_notification_id') ?? 0;

        Notifier::statusChanged($application, $status, $remark, $adminId);

        Notification::where('notification_id', '>', $lastNotification)->update(['created_at' => $at]);
        UserNotification::where('user_notification_id', '>', $lastRecipient)->update([
            'created_at' => $at,
            'read_at' => $at->lt(now()->subDays(3)) ? $at->copy()->addHours(2) : null,
        ]);
    }

    private function description(PermitType $permit, string $title): string
    {
        return match ($permit) {
            PermitType::KebenaranMerancang => "Permohonan kebenaran merancang bagi {$title}. Cadangan susun atur pembangunan disertakan bersama Laporan Cadangan Pemajuan.",
            PermitType::PelanBangunan => "Permohonan kelulusan pelan bangunan bagi {$title}. Pelan bangunan disediakan oleh arkitek berdaftar.",
            PermitType::UbahsuaiRumah => "Permohonan ubahsuai bagi {$title}. Kerja ubahsuai melibatkan tambahan binaan pada rumah sedia ada.",
        };
    }

    private function defaultRemark(S $status): string
    {
        return match ($status) {
            S::Submitted => 'Permohonan dihantar oleh pemohon.',
            S::UnderReview => 'Permohonan sedang disemak oleh pegawai.',
            S::ActionRequired => 'Maklumat tambahan diperlukan.',
            S::Verified => 'Maklumat dan dokumen permohonan telah disemak dan disahkan. Dimajukan ke proses seterusnya.',
            S::Approved => 'Permohonan diluluskan.',
            S::Rejected => 'Permohonan ditolak.',
            default => '',
        };
    }

    /** A small one-page PDF with lines of text (no extra library needed) */
    private function pdf(array $lines): string
    {
        $text = "BT /F1 16 Tf 50 790 Td 20 TL\n";
        foreach ($lines as $i => $line) {
            $safe = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);
            $text .= ($i === 1 ? "/F1 10 Tf\n" : '') . "({$safe}) Tj T*\n";
        }
        $text .= 'ET';

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            '<< /Length ' . strlen($text) . " >>\nstream\n{$text}\nendstream",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $n => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($n + 1) . " 0 obj\n{$object}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf . "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
    }
}
