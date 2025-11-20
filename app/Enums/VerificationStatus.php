<?php

namespace App\Enums;

enum VerificationStatus: string
{
    case MENUNGGU = 'MENUNGGU';
    case DISETUJUI = 'DISETUJUI';
    case DITOLAK = 'DITOLAK';

    public function label(): string
    {
        return match ($this) {
            self::MENUNGGU => 'Menunggu Verifikasi',
            self::DISETUJUI => 'Disetujui',
            self::DITOLAK => 'Ditolak',
        };
    }
}
