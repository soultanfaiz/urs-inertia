<!DOCTYPE html>
<html>

<head>
    <title>Laporan Detail Permohonan</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }

        .header h2 {
            margin: 0;
            font-size: 18px;
        }

        .header p {
            margin: 5px 0 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 6px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background-color: #f4f4f4;
            font-weight: bold;
        }

        .section-title {
            background-color: #eee;
            padding: 5px;
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 5px;
            border-left: 4px solid #333;
        }

        .page-break {
            page-break-after: always;
        }

        .no-border td {
            border: none;
            padding: 2px 5px;
        }

        .image-gallery {
            margin-top: 10px;
        }

        .image-item {
            display: inline-block;
            margin-right: 10px;
            margin-bottom: 10px;
            vertical-align: top;
            text-align: center;
            width: 150px;
        }

        .image-item img {
            max-width: 100%;
            max-height: 150px;
            border: 1px solid #ddd;
        }

        .image-caption {
            font-size: 10px;
            color: #666;
            margin-top: 2px;
            display: block;
            word-wrap: break-word;
        }

        .doc-list {
            list-style-type: none;
            padding: 0;
        }

        .doc-list li {
            margin-bottom: 4px;
        }

        .doc-link {
            color: #0066cc;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="header">
        <h2>Laporan Detail Permohonan Aplikasi</h2>
        <p>Sistem Informasi Permohonan Aplikasi Internal Samarinda</p>
        <p style="font-size: 10px; margin-top: 5px;">Dicetak pada: {{ now()->format('d F Y H:i') }} | Oleh:
            {{ auth()->user()->name }}</p>
    </div>

    <!-- Ringkasan (Summary Table) -->
    <h3>Ringkasan Data ({{ count($requests) }} Permohonan)</h3>
    <table>
        <thead>
            <tr>
                <th style="width: 5%">No</th>
                <th style="width: 30%">Judul</th>
                <th style="width: 20%">Instansi</th>
                <th style="width: 15%">Tanggal</th>
                <th style="width: 15%">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($requests as $index => $req)
                <tr>
                    <td style="text-align: center">{{ $index + 1 }}</td>
                    <td>{{ $req->title }}</td>
                    <td>{{ $req->instansi }}</td>
                    <td>{{ $req->created_at->format('d/m/Y') }}</td>
                    <td>{{ $req->status->label() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="page-break"></div>

    <!-- Detail per Permohonan -->
    @foreach($requests as $index => $req)
        <div class="{{ !$loop->last ? 'page-break' : '' }}">
            <h3>{{ $index + 1 }}. {{ $req->title }}</h3>

            <table class="no-border" style="width: 100%; margin-bottom: 15px;">
                <tr>
                    <td style="width: 15%; font-weight: bold;">Instansi</td>
                    <td style="width: 35%">: {{ $req->instansi }}</td>
                    <td style="width: 15%; font-weight: bold;">Pemohon</td>
                    <td style="width: 35%">: {{ $req->user->name ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Tanggal Masuk</td>
                    <td>: {{ $req->created_at->format('d F Y') }}</td>
                    <td style="font-weight: bold;">Status Saat Ini</td>
                    <td>: {{ $req->status->label() }}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Verifikasi</td>
                    <td>: {{ $req->verification_status->label() }}</td>
                    <td style="font-weight: bold;">Estimasi Selesai</td>
                    <td>: {{ $req->end_date ? \Carbon\Carbon::parse($req->end_date)->format('d F Y') : '-' }}</td>
                </tr>
            </table>

            <div class="section-title">Deskripsi</div>
            <div style="padding: 5px; text-align: justify; margin-bottom: 10px;">
                {{ $req->description }}
            </div>

            <div class="section-title">Dokumen Utama</div>
            <div style="padding: 5px;">
                @if($req->file_path)
                    <a href="{{ $req->file_path }}" target="_blank" class="doc-link">
                        [PDF] Download Dokumen Permohonan Utama
                    </a>
                @else
                    <span style="color: #999;">Tidak ada dokumen utama.</span>
                @endif
            </div>

            <div class="section-title">Riwayat & Bukti Dukung</div>
            @forelse($req->histories as $history)
                @if($history->docSupports->isNotEmpty() || $history->imageSupports->isNotEmpty())
                    <div style="margin-bottom: 15px; border-bottom: 1px dashed #ccc; padding-bottom: 10px;">
                        <p style="font-weight: bold; margin-bottom: 5px;">
                            Tahap: {{ $history->status_label }}
                            <span
                                style="font-weight: normal; font-size: 11px; color: #666;">({{ $history->created_at->format('d/m/Y H:i') }})</span>
                        </p>

                        <!-- Gambar -->
                        @if($history->imageSupports->isNotEmpty())
                            <div class="image-gallery">
                                @foreach($history->imageSupports as $img)
                                    <div class="image-item">
                                        <img src="{{ $img->image_path }}" alt="Bukti Gambar">
                                        <span class="image-caption">{{ $img->image_name }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <!-- Dokumen -->
                        @if($history->docSupports->isNotEmpty())
                            <ul class="doc-list">
                                @foreach($history->docSupports as $doc)
                                    <li>
                                        <a href="{{ $doc->file_path }}" target="_blank" class="doc-link">
                                            [PDF] {{ $doc->file_name }}
                                        </a>
                                        <span style="font-size: 10px; color: #666;">({{ $doc->verification_status->label() }})</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif
            @empty
                <p style="padding: 5px; color: #666;">Belum ada riwayat atau bukti dukung.</p>
            @endforelse
        </div>
    @endforeach
</body>

</html>