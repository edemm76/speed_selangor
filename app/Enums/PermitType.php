<?php

namespace App\Enums;

enum PermitType: string
{
    case KebenaranMerancang = 'kebenaran_merancang';
    case PelanBangunan = 'pelan_bangunan';
    case UbahsuaiRumah = 'ubahsuai_rumah';

    /** Malay label shown in the UI */
    public function label(): string
    {
        return match ($this) {
            self::KebenaranMerancang => 'Kebenaran Merancang (KM)',
            self::PelanBangunan => 'Pelan Bangunan',
            self::UbahsuaiRumah => 'Ubahsuai Rumah',
        };
    }

    /**
     * Documents the vendor must upload before submitting.
     * Key = document_type saved in application_documents, value = Malay label.
     */
    public function requiredDocuments(): array
    {
        return match ($this) {
            self::KebenaranMerancang => [
                'borang_permohonan' => 'Borang Permohonan',
                'geran_tanah' => 'Salinan Geran Tanah',
                'pelan_lokasi' => 'Pelan Lokasi / Tapak',
                'laporan_cadangan' => 'Laporan Cadangan Pemajuan',
            ],
            self::PelanBangunan => [
                'borang_permohonan' => 'Borang Permohonan',
                'geran_tanah' => 'Salinan Geran Tanah',
                'pelan_bangunan' => 'Pelan Bangunan',
                'surat_kelulusan_km' => 'Surat Kelulusan KM',
            ],
            self::UbahsuaiRumah => [
                'borang_permohonan' => 'Borang Permohonan',
                'geran_tanah' => 'Salinan Geran Tanah',
                'pelan_ubahsuai' => 'Pelan Ubahsuai',
            ],
        };
    }

    /** ['kebenaran_merancang' => 'Kebenaran Merancang (KM)', ...] for dropdowns */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $p) => [$p->value => $p->label()])
            ->all();
    }
}