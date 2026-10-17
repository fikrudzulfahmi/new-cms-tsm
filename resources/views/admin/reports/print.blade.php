@php
    $tgl = fn ($d) => \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('j F Y');
    $tglSingkat = fn ($d) => \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d M Y');
    $jam = fn ($d) => \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d M Y, H.i');
    $angka = fn ($n) => number_format((int) $n, 0, ',', '.');
    $logo = $settings['site_logo'] ?? null;
    $logoUrl = $logo ? (preg_match('#^https?://#', $logo) ? $logo : asset('storage/' . $logo)) : null;
    $detail = $activities->take(80);
    $sisaDetail = $activities->count() - $detail->count();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Performa Website {{ $tglSingkat($from) }} – {{ $tglSingkat($to) }}</title>
    <style>
        :root { --ink: #111827; --muted: #4b5563; --line: #d1d5db; --soft: #f3f4f6; --navy: #0a1a3a; --accent: #1e4fa8; }
        *, *::before, *::after { box-sizing: border-box; }
        body {
            margin: 0;
            background: #e5e7eb;
            color: var(--ink);
            font-family: 'Segoe UI', Arial, Helvetica, sans-serif;
            font-size: 12px;
            line-height: 1.55;
        }
        .toolbar {
            position: sticky; top: 0; z-index: 10;
            display: flex; align-items: center; justify-content: space-between; gap: 12px;
            padding: 12px 18px; background: var(--navy); color: #fff;
        }
        .toolbar p { margin: 0; font-size: 12px; color: #c9ddf5; }
        .toolbar .actions { display: flex; gap: 8px; }
        .btn {
            display: inline-flex; align-items: center; gap: 6px;
            min-height: 38px; padding: 0 18px; border-radius: 999px;
            font-size: 12px; font-weight: 700; text-decoration: none; cursor: pointer;
            border: 1px solid transparent; font-family: inherit;
        }
        .btn-primary { background: #7cc8ff; color: #0a1a3a; }
        .btn-ghost { background: transparent; color: #eaf5ff; border-color: rgba(234,245,255,.45); }
        .sheet {
            width: 210mm; min-height: 297mm; margin: 18px auto; padding: 16mm 14mm;
            background: #fff; box-shadow: 0 10px 40px rgba(15,23,42,.18);
        }
        .letterhead { display: flex; align-items: center; gap: 14px; border-bottom: 3px double var(--ink); padding-bottom: 10px; }
        .letterhead img { height: 58px; width: auto; max-width: 90px; object-fit: contain; }
        .letterhead .school { flex: 1; text-align: center; }
        .letterhead .school strong { display: block; font-size: 17px; letter-spacing: .3px; text-transform: uppercase; }
        .letterhead .school span { display: block; font-size: 13px; font-weight: 600; color: var(--accent); }
        .letterhead .school small { display: block; font-size: 10.5px; color: var(--muted); margin-top: 2px; }
        h1 { font-size: 15px; text-align: center; text-transform: uppercase; letter-spacing: .6px; margin: 16px 0 2px; }
        .period { text-align: center; font-size: 12px; color: var(--muted); margin: 0 0 14px; }
        .meta { display: flex; justify-content: space-between; gap: 12px; font-size: 11px; color: var(--muted); border-bottom: 1px solid var(--line); padding-bottom: 8px; margin-bottom: 14px; }
        h2 { font-size: 12.5px; text-transform: uppercase; letter-spacing: .4px; margin: 16px 0 6px; padding-bottom: 3px; border-bottom: 1px solid var(--line); break-after: avoid; }
        h2 .no { color: var(--accent); margin-right: 4px; }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; }
        .stat { border: 1px solid var(--line); border-radius: 4px; padding: 7px 9px; }
        .stat b { display: block; font-size: 16px; line-height: 1.2; }
        .stat span { font-size: 10.5px; color: var(--muted); }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th, td { border: 1px solid var(--line); padding: 5px 6px; vertical-align: top; text-align: left; }
        th { background: var(--soft); font-size: 10.5px; text-transform: uppercase; letter-spacing: .3px; }
        td.num, th.num { text-align: right; white-space: nowrap; }
        tr { break-inside: avoid; }
        .tbl-title { font-weight: 700; font-size: 6.5pt; }
        .excerpt { color: #374151; font-size: 10.5px; }
        .empty { padding: 8px; background: var(--soft); border: 1px solid var(--line); font-size: 11px; color: var(--muted); }
        .note { font-size: 10px; color: var(--muted); margin: 4px 0 0; }
        .closing { margin-top: 18px; font-size: 11px; }
        .signs { display: flex; justify-content: space-between; gap: 24px; margin-top: 22px; text-align: center; font-size: 11px; }
        .signs div { width: 45%; }
        .signs .space { height: 58px; }
        .signs .name { font-weight: 700; text-decoration: underline; }
        .foot { margin-top: 16px; border-top: 1px solid var(--line); padding-top: 6px; font-size: 9.5px; color: var(--muted); text-align: center; }
        @media print {
            body { background: #fff; font-size: 10.5pt; }
            .toolbar { display: none !important; }
            .sheet { width: auto; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
            @page { size: A4 portrait; margin: 14mm 13mm; }
            h2 { break-after: avoid; }
            .signs { break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <p><b>Laporan siap cetak.</b> Klik Cetak lalu pilih tujuan <b>“Simpan sebagai PDF”</b> pada dialog browser.</p>
        <div class="actions">
            <a class="btn btn-ghost" href="{{ url('/admin/laporan?from=' . $from->toDateString() . '&to=' . $to->toDateString()) }}">Kembali</a>
            <button class="btn btn-primary" type="button" onclick="window.print()">Cetak / Simpan PDF</button>
        </div>
    </div>

    <div class="sheet">
        <div class="letterhead">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="Logo sekolah">
            @endif
            <div class="school">
                <strong>{{ $settings['school_name'] ?: 'SMKS Islam 1 Kota Blitar' }}</strong>
                <span>{{ $settings['department_name'] ?: 'Teknik Sepeda Motor' }}</span>
                <small>
                    {{ $settings['address'] ?: 'Jl. Musi No. 06 Kota Blitar' }}
                    @if ($settings['phone']) &middot; {{ $settings['phone'] }} @endif
                    @if ($settings['email']) &middot; {{ $settings['email'] }} @endif
                </small>
            </div>
        </div>

        <h1>Laporan Performa Website dan Pembaruan Konten</h1>
        <p class="period">Periode {{ $tgl($from) }} – {{ $tgl($to) }} ({{ $days }} hari)</p>

        <div class="meta">
            <span>Disusun oleh: <b>{{ $printedBy }}</b></span>
            <span>Dicetak: {{ $jam($printedAt) }} WIB</span>
        </div>

        <h2><span class="no">1.</span> Ringkasan Performa Website</h2>
        <div class="stats">
            <div class="stat"><b>{{ $angka($summary['visits']) }}</b><span>Total kunjungan</span></div>
            <div class="stat"><b>{{ $angka($summary['unique']) }}</b><span>Pengunjung unik</span></div>
            <div class="stat"><b>{{ number_format($summary['per_day'], 1, ',', '.') }}</b><span>Rata-rata kunjungan/hari</span></div>
            <div class="stat"><b>{{ $angka($summary['posts_published']) }}</b><span>Berita diterbitkan</span></div>
            <div class="stat"><b>{{ $angka($summary['comments']) }}</b><span>Komentar masuk</span></div>
            <div class="stat"><b>{{ $angka($summary['messages']) }}</b><span>Pesan dari formulir kontak</span></div>
        </div>

        @if ($topPages->isNotEmpty())
            <h2><span class="no">2.</span> Halaman Paling Banyak Dikunjungi</h2>
            <table>
                <thead>
                    <tr><th style="width: 8%">No</th><th>Halaman</th><th class="num" style="width: 16%">Kunjungan</th></tr>
                </thead>
                <tbody>
                    @foreach ($topPages as $i => $page)
                        <tr>
                            <td class="num">{{ $i + 1 }}</td>
                            <td>{{ $page->path === '/' ? 'Beranda (/)' : $page->path }}</td>
                            <td class="num">{{ $angka($page->total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if ($topPosts->isNotEmpty())
            <h2><span class="no">3.</span> Berita Paling Banyak Dibaca</h2>
            <table>
                <thead>
                    <tr><th style="width: 8%">No</th><th>Judul</th><th class="num" style="width: 16%">Total dibaca</th><th class="num" style="width: 18%">Terbit</th></tr>
                </thead>
                <tbody>
                    @foreach ($topPosts as $i => $post)
                        <tr>
                            <td class="num">{{ $i + 1 }}</td>
                            <td>{{ $post->title }}</td>
                            <td class="num">{{ $angka($post->views_count) }}</td>
                            <td class="num">{{ $post->published_at ? $tglSingkat($post->published_at) : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <h2><span class="no">4.</span> Berita yang Diterbitkan pada Periode Ini</h2>
        @if ($posts->isEmpty())
            <p class="empty">Belum ada berita yang diterbitkan pada periode ini.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th style="width: 6%">No</th>
                        <th style="width: 14%">Tanggal</th>
                        <th>Judul &amp; ringkasan</th>
                        <th style="width: 13%">Kategori</th>
                        <th style="width: 15%">Penulis</th>
                        <th class="num" style="width: 9%">Dibaca</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($posts as $i => $post)
                        <tr>
                            <td class="num">{{ $i + 1 }}</td>
                            <td>{{ $post->published_at ? $tglSingkat($post->published_at) : '—' }}</td>
                            <td>
                                <span class="tbl-title">{{ $post->title }}</span><br>
                                <span class="excerpt">{{ $post->excerpt ? \Illuminate\Support\Str::limit(strip_tags($post->excerpt), 200) : '—' }}</span>
                            </td>
                            <td>{{ $post->category?->name ?? '—' }}</td>
                            <td>{{ $post->user?->name ?? 'Admin' }}</td>
                            <td class="num">{{ $angka($post->views_count) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <h2><span class="no">5.</span> Pembaruan Website yang Dilakukan</h2>
        @if ($activities->isEmpty())
            <p class="empty">Tidak ada pembaruan data pada periode ini.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Bagian</th>
                        <th class="num" style="width: 11%">Tambah</th>
                        <th class="num" style="width: 11%">Ubah</th>
                        <th class="num" style="width: 11%">Hapus</th>
                        <th class="num" style="width: 11%">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($activityRecap as $recap)
                        <tr>
                            <td>{{ $recap['label'] }}</td>
                            <td class="num">{{ $angka($recap['create']) }}</td>
                            <td class="num">{{ $angka($recap['update']) }}</td>
                            <td class="num">{{ $angka($recap['delete']) }}</td>
                            <td class="num"><b>{{ $angka($recap['total']) }}</b></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <h2 style="border-bottom:none;margin-bottom:4px">Rincian pembaruan</h2>
            <table>
                <thead>
                    <tr>
                        <th style="width: 16%">Tanggal</th>
                        <th style="width: 15%">Pengguna</th>
                        <th style="width: 9%">Aksi</th>
                        <th style="width: 18%">Bagian</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($detail as $log)
                        <tr>
                            <td>{{ $tglSingkat($log->created_at) }}</td>
                            <td>{{ $log->user_name ?: '—' }}</td>
                            <td>{{ \App\Models\ActivityLog::actionLabel($log->action) }}</td>
                            <td>{{ \App\Models\ActivityLog::moduleLabel($log->module) }}</td>
                            <td>{{ $log->description ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($sisaDetail > 0)
                <p class="note">Menampilkan 80 pembaruan terakhir; {{ $sisaDetail }} pembaruan lain tercatat di menu Log Aktivitas.</p>
            @endif
            @if ($logSince)
                <p class="note">Rekap pembaruan bersumber dari log aktivitas admin, tercatat sejak {{ $tglSingkat($logSince) }}.</p>
            @endif
        @endif

        <h2><span class="no">6.</span> Isi Website Saat Ini</h2>
        <table>
            <thead>
                <tr><th>Bagian</th><th class="num" style="width: 18%">Jumlah</th></tr>
            </thead>
            <tbody>
                @foreach ($contentTotals as $row)
                    <tr><td>{{ $row['label'] }}</td><td class="num">{{ $angka($row['total']) }}</td></tr>
                @endforeach
            </tbody>
        </table>

        <p class="closing">
            Laporan ini dibuat otomatis dari panel admin website jurusan berdasarkan data kunjungan, berita yang diterbitkan,
            serta catatan pembaruan konten pada periode {{ $tgl($from) }} – {{ $tgl($to) }}.
        </p>

        <div class="signs">
            <div>
                <div>Disusun oleh,</div>
                <div class="space"></div>
                <div class="name">{{ $printedBy }}</div>
                <div>Pengelola Website</div>
            </div>
            <div>
                <div>Mengetahui,</div>
                <div class="space"></div>
                <div class="name">{{ $settings['profile_head_name'] ?: 'Kepala Program' }}</div>
                <div>Kepala Program {{ $settings['department_name'] ?: 'Teknik Sepeda Motor' }}</div>
            </div>
        </div>

        <div class="foot">
            Dicetak {{ $jam($printedAt) }} WIB &middot; {{ $settings['school_name'] ?: 'SMKS Islam 1 Kota Blitar' }}
            &middot; Data diambil langsung dari sistem CMS sekolah.
        </div>
    </div>
</body>
</html>
