<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Aktivitas WA Bot Nagari Sungai Aua</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #444;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            text-transform: uppercase;
        }
        .header h2 {
            margin: 5px 0 0;
            font-size: 16px;
            font-weight: normal;
        }
        .info {
            margin-bottom: 20px;
            font-size: 12px;
        }
        .info table {
            width: 100%;
        }
        .info td {
            padding: 2px 0;
        }
        .content table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }
        .content th {
            background-color: #f2f2f2;
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-weight: bold;
        }
        .content td {
            border: 1px solid #ddd;
            padding: 8px;
            vertical-align: top;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 12px;
        }
        .footer .signature {
            margin-top: 50px;
        }
        .page-number:after {
            content: counter(page);
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Pemerintah Kabupaten Pasaman Barat</h1>
        <h1>Kecamatan Sungai Aur</h1>
        <h1>Nagari Sungai Aua</h1>
        <h2>Laporan Aktivitas WhatsApp Bot Pelayanan Publik</h2>
    </div>

    <div class="info">
        <table>
            <tr>
                <td width="150">Tanggal Laporan</td>
                <td width="10">:</td>
                <td>{{ date('d F Y H:i') }}</td>
            </tr>
            <tr>
                <td>Total Kontak</td>
                <td>:</td>
                <td>{{ count($contacts) }}</td>
            </tr>
            <tr>
                <td>Periode Data</td>
                <td>:</td>
                <td>Sesuai Aktivitas Terakhir</td>
            </tr>
        </table>
    </div>

    <div class="content">
        <table>
            <thead>
                <tr>
                    <th width="30">No</th>
                    <th width="100">Nomor Telepon</th>
                    <th width="120">Nama / Identitas</th>
                    <th width="80">Grup / Tag</th>
                    <th>Pesan Terakhir</th>
                    <th width="100">Waktu Terakhir</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($contacts as $index => $contact)
                    @php
                        $m = $contact->messages->first();
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $contact->phone }}</td>
                        <td>{{ $contact->name ?? '-' }}</td>
                        <td>{{ $contact->group_tag ?? '-' }}</td>
                        <td>
                            @if($m)
                                <strong>[{{ strtoupper($m->type) }}]</strong>:
                                {{ \Illuminate\Support\Str::limit($m->message, 100) }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            {{ $m?->message_timestamp ? $m->message_timestamp->format('d/m/Y H:i') : ($contact->last_message_at ? $contact->last_message_at->format('d/m/Y H:i') : '-') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>Sungai Aua, {{ date('d F Y') }}</p>
        <p>Petugas Administrator,</p>
        <div class="signature">
            <p>__________________________</p>
            <p>NIP. ..........................</p>
        </div>
    </div>
</body>
</html>
