<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kop Surat Presisi Tata Naskah</title>
    <style>
        @page {
            margin: 1.5cm 2cm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.5;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .page {
            width: 100%;
            padding: 0;
        }

        /* Container Kop */
        .kop-surat {
            width: 100%;
            border-bottom: 4px double #000;
            padding-bottom: 8px;
            margin-bottom: 20px;
        }

        .kop-table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            table-layout: fixed;
            padding-top: 0px;

        }

        /* Logo Kiri */
        .logo-box {
            width: 20%;
            min-width: 85px;
            text-align: left;
            vertical-align: top;
            padding-left: 70px;
            padding-top: 20px;
        }

        .logo-box img {
            width: 120px;
            height: auto;
        }

        /* Spacer untuk menyeimbangkan */
        .spacer-box {
            width: 10%;
            min-width: 85px;
        }

        /* Teks Identitas */
        .info-box {
            text-align: center;
            vertical-align: top;
            padding: 0 10px;
        }

        .info-box h2 {
            margin: 0;
            padding-top: 35px;
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            line-height: 1.2;
        }

        .info-box h1 {
            margin: 0;
            font-size: 18pt;
            font-weight: normal;
            text-transform: uppercase;
            line-height: 1.2;
            display: inline-block;
        }

        .info-box .alamat {
            margin: 0px 0 0 0;
            font-size: 8pt;
            font-weight: normal;
            line-height: 1.3;
            display: inline-block;
            max-width: 100%;
        }

        .info-box .kontak {
            margin: 5px 0 0 0;
            font-size: 8pt;
            line-height: 1.3;
        }

        .info-box a {
            color: black;
            text-decoration: none;
        }

        /* Isi Surat */
        .isi-surat {
            font-size: 12pt;
            line-height: 1.5;
            color: #333;
        }
    </style>
</head>

<body>

    <div class="page">
        <header class="kop-surat">
            <table class="kop-table">
                <tr>
                    <!-- Logo Kota Samarinda -->
                    <td class="logo-box">
                        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('storage/Logo_Kota_Samarinda.png'))) }}"
                            alt="Logo Samarinda">
                    </td>

                    <!-- Teks Sesuai Aturan -->
                    <td class="info-box">
                        <h2>Pemerintah Kota Samarinda</h2>
                        <h1>Dinas Komunikasi Dan Informatika</h1>
                        <p class="alamat">Jalan Kesuma Bangsa Nomor 82, Komplek Balai Kota, Bugis, Samarinda Kota,
                            Samarinda 75121</p>
                        <br>
                        <p class="kontak">
                            Laman: <a
                                href="https://diskominfo.samarindakota.go.id">https://diskominfo.samarindakota.go.id</a>;
                            Pos-El: diskominfo@samarindakota.go.id
                        </p>
                    </td>

                    <!-- Spacer untuk menyeimbangkan dengan logo -->
                    <td class="spacer-box"></td>
                </tr>
            </table>
        </header>
    </div>

</body>

</html>