<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notulen Kegiatan</title>
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
            font-size: 11pt;
            line-height: 1.4;
            color: #333;
        }

        .page {
            width: 100%;
            padding: 0;
        }

        /* Header Table - Combined Logo + Dinas + Title */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #333;
            margin-bottom: 0;
        }

        .header-table td {
            border: 1px solid #333;
            padding: 8px 10px;
            vertical-align: middle;
        }

        .logo-cell {
            width: 80px;
            text-align: center;
            vertical-align: middle;
        }

        .logo-cell img {
            width: 65px;
            height: auto;
        }

        .dinas-cell {
            text-align: center;
            vertical-align: middle;
        }

        .dinas-cell h2 {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0;
            letter-spacing: 1px;
        }

        .dinas-cell h3 {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0;
        }

        .title-cell {
            text-align: center;
            vertical-align: middle;
            padding: 10px;
        }

        .title-cell h1 {
            font-size: 16pt;
            font-weight: bold;
            text-decoration: underline;
            margin: 0;
        }

        /* Main Info Table */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            border: 1px solid #333;
            border-top: none;
        }

        .info-table td {
            padding: 6px 10px;
            vertical-align: top;
            border: 1px solid #333;
        }

        .info-table .label {
            width: 110px;
            font-weight: normal;
            background-color: #fff;
        }

        .info-table .value {
            background-color: #fff;
        }

        .info-table .right-section {
            width: 120px;
            font-weight: normal;
        }

        .info-table .right-value {
            width: 120px;
        }

        /* Signature Section (Right side of main table) */
        .signature-cell {
            border-left: 1px solid #333;
        }

        /* Section Headers */
        .section-header {
            background-color: #1e4a6e;
            color: #fff;
            padding: 6px 12px;
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 11pt;
        }

        /* Content Section */
        .content-section {
            margin-bottom: 20px;
        }

        .content-section ul,
        .content-section ol {
            margin: 10px 0 10px 20px;
            padding-left: 15px;
        }

        .content-section li {
            margin-bottom: 8px;
            line-height: 1.5;
        }

        .content-section p {
            margin: 8px 0;
            line-height: 1.6;
            text-align: justify;
        }

        /* Note Content - Tiptap Content Sanitizer */
        .note-content {
            font-size: 11pt;
            line-height: 1.6;
            padding-left: 30px;
            /* Tambahkan indentasi kiri */
            padding-right: 30px;
        }

        .note-content h1,
        .note-content h2,
        .note-content h3,
        .note-content h4,
        .note-content h5,
        .note-content h6 {
            margin: 10px 0 5px 0;
            font-weight: bold;
        }

        .note-content h1 {
            font-size: 14pt;
        }

        .note-content h2 {
            font-size: 13pt;
        }

        .note-content h3 {
            font-size: 12pt;
        }

        .note-content h4,
        .note-content h5,
        .note-content h6 {
            font-size: 11pt;
        }

        .note-content p {
            margin: 5px 0;
            text-align: justify;
        }

        .note-content ul,
        .note-content ol {
            margin: 5px 0 5px 20px;
            padding-left: 15px;
        }

        .note-content li {
            margin-bottom: 5px;
        }

        .note-content strong,
        .note-content b {
            font-weight: bold;
        }

        .note-content em,
        .note-content i {
            font-style: italic;
        }

        .note-content u {
            text-decoration: underline;
        }

        .note-content s,
        .note-content strike {
            text-decoration: line-through;
        }

        .note-content blockquote {
            margin: 10px 0;
            padding-left: 15px;
            border-left: 3px solid #ccc;
            font-style: italic;
        }

        .note-content pre,
        .note-content code {
            font-family: 'Courier New', monospace;
            font-size: 10pt;
            background-color: #f5f5f5;
            padding: 2px 5px;
        }

        .note-content pre {
            display: block;
            padding: 10px;
            margin: 10px 0;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .note-content a {
            color: #1a365d;
            text-decoration: underline;
        }

        .note-content table {
            border-collapse: collapse;
            width: 100%;
            margin: 10px 0;
        }

        .note-content table td,
        .note-content table th {
            border: 1px solid #333;
            padding: 5px;
        }

        .note-content img {
            max-width: 100%;
            height: auto;
            margin: 10px 0;
        }

        /* Peserta List */
        .peserta-list {
            margin: 0;
            padding-left: 20px;
        }

        .peserta-list li {
            margin-bottom: 3px;
        }

        /* Note Image */
        .note-image {
            max-width: 100%;
            height: auto;
            margin: 10px 0;
            border: 1px solid #ddd;
        }

        /* Page Break */
        .page-break {
            page-break-before: always;
        }
    </style>
</head>

<body>
    <div class="page">
        <!-- Header Table: Logo (merged 2 rows) + Dinas Text + Notulen Title -->
        <table class="header-table">
            <tr>
                <!-- Logo - merged 2 rows -->
                <td class="logo-cell" rowspan="2">
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('storage/Logo_Kota_Samarinda.png'))) }}"
                        alt="Logo Samarinda">
                </td>
                <!-- Dinas Text -->
                <td class="dinas-cell">
                    <h2>Dinas Komunikasi dan Informatika</h2>
                    <h3>Pemerintah Kota Samarinda</h3>
                </td>
            </tr>
            <tr>
                <!-- Notulen Kegiatan Title -->
                <td class="title-cell">
                    <h1>NOTULEN KEGIATAN</h1>
                </td>
            </tr>
        </table>

        <!-- Main Info Table -->
        <table class="info-table">
            <tr>
                <td class="label">Subyek</td>
                <td class="value" colspan="3">{{ $appRequest->title ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Hari / Tanggal</td>
                <td class="value">
                    {{ $appRequest->start_date ? $appRequest->start_date->translatedFormat('l, d F Y') : '-' }}
                </td>
                <td class="right-section">Notulis / Disiapkan oleh :</td>
                <td class="right-value">{{ $appRequest->user->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Waktu</td>
                <td class="value">{{ $metadata->time_display }}</td>
                <td class="right-section">Diperiksa oleh :</td>
                <td class="right-value"></td>
            </tr>
            <tr>
                <td class="label">Pimpinan / Moderator</td>
                <td class="value">{{ $metadata->leader }}</td>
                <td class="right-section">Disetujui oleh :</td>
                <td class="right-value"></td>
            </tr>
            <tr>
                <td class="label">Narasumber</td>
                <td class="value">{{ $metadata->speakers }}</td>
                <td class="right-section">Tanggal :</td>
                <td class="right-value">{{ now()->translatedFormat('d F Y') }}</td>
            </tr>
            <tr>
                <td class="label">Tempat</td>
                <td class="value">{{ $metadata->place }}</td>
                <td class="right-section">Tanda tangan</td>
                <td class="right-value"></td>
            </tr>
            <tr>
                <td class="label">Peserta</td>
                <td class="value" colspan="3">{{ $metadata->participants }}</td>
            </tr>
        </table>

        <!-- Notes Content -->
        @foreach($notes as $index => $note)
            <div class="content-section">
                <div class="section-header">{{ chr(65 + $index) }}. {{ strtoupper($note->title) }}</div>
                <div class="note-content">
                    {!! $note->note !!}
                </div>

                @if($note->image_path)
                    <div style="margin-top: 10px;">
                        <img src="{{ $note->image_path }}" alt="Lampiran Gambar" class="note-image">
                    </div>
                @endif
            </div>
        @endforeach

        @if($notes->isEmpty())
            <div class="content-section">
                <p style="text-align: center; color: #666;">Belum ada catatan untuk notulen ini.</p>
            </div>
        @endif
    </div>
</body>

</html>