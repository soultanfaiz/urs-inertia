<?php

namespace App\Enums;

enum Instansi: string
{
    // Sekretariat
    case SEKRETARIAT_DAERAH = 'Sekretariat Daerah';
    case SEKRETARIAT_DPRD = 'Sekretariat DPRD';
    case INSPEKTORAT = 'Inspektorat';

    // Dinas
    case DISDIKBUD = 'Dinas Pendidikan dan Kebudayaan';
    case DISPORAPAR = 'Dinas Kepemudaan, Olahraga, dan Pariwisata';
    case DINKES = 'Dinas Kesehatan';
    case DP3A_PPKB = 'Dinas Pemberdayaan Perempuan dan Perlindungan Anak, serta Pengendalian Penduduk dan Keluarga Berencana';
    case DINSOS_PM = 'Dinas Sosial dan Pemberdayaan Masyarakat';
    case DISDUKCAPIL = 'Dinas Kependudukan dan Pencatatan Sipil';
    case SATPOL_PP = 'Satuan Polisi Pamong Praja';
    case DAMKAR_PENYELAMATAN = 'Dinas Pemadam Kebakaran dan Penyelamatan';
    case DPMPTSP = 'Dinas Penanaman Modal dan Pelayanan Terpadu Satu Pintu';
    case DKUKMPP = 'Dinas Koperasi, Usaha Kecil dan Menengah, Perindustrian, dan Perdagangan';
    case DISNAKER = 'Dinas Tenaga Kerja';
    case DISKOMINFO = 'Dinas Komunikasi, Informatika, Statistik, dan Persandian';
    case PUPR = 'Dinas Pekerjaan Umum dan Penataan Ruang';
    case PERKIMTAN = 'Dinas Perumahan Rakyat dan Kawasan Permukiman Serta Pertanahan';
    case DISHUB = 'Dinas Perhubungan';
    case DLH = 'Dinas Lingkungan Hidup';
    case DKPP = 'Dinas Ketahanan Pangan dan Pertanian';
    case DISKAN = 'Dinas Perikanan';
    case DISPURSIP = 'Dinas Perpustakaan dan Kearsipan';
    case DISDAG = 'Dinas Perdagangan';

    // Badan
    case BKPSDM = 'Badan Kepegawaian dan Pengembangan Sumber Daya Manusia';
    case BAPPEDALITBANG = 'Badan Perencanaan Pembangunan Daerah, Penelitian dan Pengembangan';
    case BPKAD = 'Badan Pengelolaan Keuangan dan Aset Daerah';
    case BAPENDA = 'Badan Pendapatan Daerah';
    case KESBANGPOL = 'Badan Kesatuan Bangsa dan Politik';
    case BPBD = 'Badan Penanggulangan Bencana Daerah';
}
