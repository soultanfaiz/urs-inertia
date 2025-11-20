<?php

namespace App\Enums;

enum RequestStatus: string
{
    case PERMOHONAN = 'PERMOHONAN';
    case URS = 'URS';
    case PENGEMBANGAN = 'PENGEMBANGAN';
    case UAT = 'UAT';
    case SELESAI = 'SELESAI';

    public function label(): string
    {
        return match ($this) {
            self::PERMOHONAN => 'Permohonan Diajukan',
            self::URS => 'Penyusunan URS',
            self::PENGEMBANGAN => 'Pengembangan',
            self::UAT => 'UAT',
            self::SELESAI => 'Selesai',
        };
    }
}
