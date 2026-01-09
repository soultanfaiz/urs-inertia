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
    case DISPORAPAR = 'Dinas Pemuda, Olahraga, dan Pariwisata';
    case DINKES = 'Dinas Kesehatan';
    case DP2PA = 'Dinas Pemberdayaan Perempuan dan Perlindungan Anak';
    case DINSOS_PM = 'Dinas Sosial dan Pemberdayaan Masyarakat';
    case DISDUKCAPIL = 'Dinas Kependudukan dan Pencatatan Sipil';
    case SATPOL_PP = 'Satuan Polisi Pamong Praja';
    case DAMKAR_PENYELAMATAN = 'Dinas Pemadam Kebakaran dan Penyelamatan';
    case DPMPTSP = 'Dinas Penanaman Modal dan Pelayanan Terpadu Satu Pintu';
    case DISKUKMP = 'Dinas Koperasi, Usaha Kecil Menengah dan Perindustrian';
    case DISNAKER = 'Dinas Tenaga Kerja';
    case DISKOMINFO = 'Dinas Komunikasi dan Informatika';
    case PUPR = 'Dinas Pekerjaan Umum dan Penataan Ruang';
    case PERKIM = 'Dinas Perumahan dan Kawasan Permukiman';
    case DISHUB = 'Dinas Perhubungan';
    case DLH = 'Dinas Lingkungan Hidup';
    case DKPP = 'Dinas Ketahanan Pangan dan Pertanian';
    case DISKAN = 'Dinas Perikanan';
    case DISPURSIP = 'Dinas Perpustakaan dan Kearsipan';
    case DISDAG = 'Dinas Perdagangan';

    // Badan
    case BKPSDM = 'Badan Kepegawaian dan Pengembangan Sumber Daya Manusia';
    case BAPPERIDA = 'Badan Perencanaan Pembangunan Riset dan Inovasi Daerah';
    case BPKAD = 'Badan Pengelolaan Keuangan dan Aset Daerah';
    case BAPENDA = 'Badan Pendapatan Daerah';
    case KESBANGPOL = 'Badan Kesatuan Bangsa dan Politik';
    case BPBD = 'Badan Penanggulangan Bencana Daerah';

    // Bagian Sekretariat Daerah
    case BAGPEMERINTAHAN = 'Bagian Tata Pemerintahan';
    case BAGUMUM = 'Bagian Umum';
    case BAGPROTOKOL = 'Bagian Protokol dan Komunikasi Pimpinan';
    case BAGHUKUM = 'Bagian Hukum';

    case BAGKERJASAMA = 'Bagian Kerjasama';

    case BAGADPEM = 'Bagian Administrasi Pembangunan';
    case BAGKESRA = 'Bagian Kesejahteraan Rakyat';
    case BAGORGANISASI = 'Bagian Organisasi';
    case BAGBARJAS = 'Bagian Pengadaan Barang dan Jasa';
    case BAGPEREKONOMIAN = 'Bagian Perekonomian';
    case BAGRENKEU = 'Bagian Perencanaan dan Keuangan';
    case BAGSDA= 'Bagian Sumber Daya Alam';

}
