<?php

namespace App\Enums;

enum Instansi: string
{
    case KEMENKEU = 'Kementerian Keuangan';
    case KEMENDAGRI = 'Kementerian Dalam Negeri';
    case BAPPENAS = 'Bappenas';
    case KEMENLU = 'Kementerian Luar Negeri';
}
