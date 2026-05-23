<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EkoMigas Pro – Analisis Keekonomian Lapangan Migas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&family=Plus+Jakarta+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --bg:       #07101e;
            --bg2:      #0b1929;
            --card:     #0f1f33;
            --sidebar:  #091524;
            --border:   #162840;
            --amber:    #f59e0b;
            --amber2:   #fbbf24;
            --amber-bg: rgba(245,158,11,.08);
            --cyan:     #22d3ee;
            --text:     #dde5f0;
            --muted:    #5a7290;
            --dim:      #8aa4bf;
            --success:  #10b981;
            --danger:   #ef4444;
            --in-bg:    #061120;
            --in-bdr:   #1c3352;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
        }

        /* ── Grid overlay ─────────────────────── */
        body::before {
            content: '';
            position: fixed; inset: 0;
            background-image:
                linear-gradient(rgba(245,158,11,.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(245,158,11,.025) 1px, transparent 1px);
            background-size: 44px 44px;
            pointer-events: none; z-index: 0;
        }

        /* ── Navbar ───────────────────────────── */
        .top-nav {
            position: fixed; top: 0; left: 0; right: 0; height: 62px;
            background: var(--bg2);
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center;
            justify-content: space-between;
            padding: 0 1.75rem;
            z-index: 200;
            backdrop-filter: blur(12px);
        }
        .nav-brand { display: flex; align-items: center; gap: 12px; }
        .nav-logo {
            width: 38px; height: 38px;
            background: var(--amber);
            border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Rajdhani', sans-serif;
            font-weight: 700; font-size: 15px; color: #000;
            box-shadow: 0 0 20px rgba(245,158,11,.35);
        }
        .nav-title {
            font-family: 'Rajdhani', sans-serif;
            font-weight: 700; font-size: 19px; color: var(--text);
            letter-spacing: .4px;
        }
        .nav-sub { font-size: 11px; color: var(--muted); letter-spacing: .8px; text-transform: uppercase; }
        .nav-tag {
            background: var(--amber-bg);
            border: 1px solid rgba(245,158,11,.3);
            color: var(--amber);
            font-family: 'IBM Plex Mono', monospace;
            font-size: 11px; padding: 4px 11px; border-radius: 20px;
        }

        /* ── Sidebar ──────────────────────────── */
        .sidebar {
            position: fixed; left: 0; top: 62px; bottom: 0;
            width: 255px;
            background: var(--sidebar);
            border-right: 1px solid var(--border);
            padding: 1.5rem 1rem;
            overflow-y: auto; z-index: 100;
            display: flex; flex-direction: column; gap: .25rem;
        }
        .sb-label {
            font-size: 10px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 1.5px;
            color: var(--muted); padding: .35rem .6rem; margin-top: .5rem;
        }
        .sb-item {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 12px; border-radius: 9px;
            color: var(--dim); font-size: 13.5px; cursor: pointer;
            transition: .18s all; text-decoration: none;
        }
        .sb-item:hover  { background: rgba(245,158,11,.06); color: var(--text); }
        .sb-item.active {
            background: var(--amber-bg);
            color: var(--amber);
            border: 1px solid rgba(245,158,11,.22);
        }
        .sb-item i { font-size: 15px; }
        .sb-hr { border: none; border-top: 1px solid var(--border); margin: .75rem 0; }

        .info-box {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 1rem 1.1rem;
            margin-top: .25rem;
        }
        .info-box .ib-title {
            font-family: 'Rajdhani', sans-serif;
            font-size: 13px; font-weight: 700;
            color: var(--amber); margin-bottom: .5rem;
            text-transform: uppercase; letter-spacing: .5px;
        }
        .info-box p {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 11.5px; color: var(--dim); line-height: 1.8;
        }

        /* ── Main ─────────────────────────────── */
        .main {
            margin-left: 255px;
            padding-top: 62px;
            position: relative; z-index: 1;
        }
        .page-body { padding: 2.2rem 2.8rem 3rem; max-width: 1050px; }

        .page-hdr { margin-bottom: 2rem; }
        .page-hdr h1 {
            font-family: 'Rajdhani', sans-serif;
            font-weight: 700; font-size: 26px; color: var(--text);
            margin-bottom: .3rem;
        }
        .page-hdr p { color: var(--muted); font-size: 13.5px; }

        /* ── Section cards ────────────────────── */
        .scard {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 13px;
            padding: 1.6rem 1.75rem;
            margin-bottom: 1.4rem;
            position: relative; overflow: hidden;
        }
        .scard::after {
            content: ''; position: absolute;
            top: 0; left: 0; right: 0; height: 2px;
            background: linear-gradient(90deg, var(--amber) 0%, transparent 65%);
        }
        .sc-title {
            font-family: 'Rajdhani', sans-serif;
            font-weight: 700; font-size: 14px;
            color: var(--amber); text-transform: uppercase;
            letter-spacing: 1px; margin-bottom: 1.3rem;
            display: flex; align-items: center; gap: 8px;
        }
        .sc-title i { font-size: 16px; }

        /* ── Form elements ────────────────────── */
        label {
            display: block;
            font-size: 11.5px; font-weight: 600;
            color: var(--dim); text-transform: uppercase;
            letter-spacing: .5px; margin-bottom: .45rem;
        }
        input[type=number], select {
            width: 100%;
            background: var(--in-bg);
            border: 1px solid var(--in-bdr);
            color: var(--text);
            border-radius: 8px;
            padding: 10px 13px;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 13.5px;
            transition: .18s all;
            appearance: none;
            -webkit-appearance: none;
        }
        input[type=number]:focus, select:focus {
            outline: none;
            border-color: var(--amber);
            box-shadow: 0 0 0 3px rgba(245,158,11,.12);
            background: var(--in-bg);
        }
        input[type=number]::placeholder { color: var(--muted); }
        select option { background: var(--card); color: var(--text); }

        .fg { margin-bottom: 1.15rem; }

        .g2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0 1.5rem; }
        .g3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0 1.5rem; }
        .g4 { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 0 1.5rem; }

        /* ── Submit button ────────────────────── */
        .btn-calc {
            width: 100%; padding: 15px;
            background: var(--amber);
            border: none; border-radius: 11px;
            color: #000;
            font-family: 'Rajdhani', sans-serif;
            font-weight: 700; font-size: 16px;
            letter-spacing: 1.5px; text-transform: uppercase;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            transition: .2s all;
        }
        .btn-calc:hover {
            background: var(--amber2);
            transform: translateY(-1px);
            box-shadow: 0 8px 28px rgba(245,158,11,.3);
        }

        .hint {
            font-size: 11px; color: var(--muted); margin-top: .35rem;
            font-style: italic;
        }

        /* ── Scrollbar ────────────────────────── */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 6px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--muted); }
    </style>
</head>
<body>

<!-- ═══ NAVBAR ═══════════════════════════════════════ -->
<nav class="top-nav">
    <div class="nav-brand">
        <div class="nav-logo">FM</div>
        <div>
            <div class="nav-title">EkoMigas Pro</div>
            <div class="nav-sub">Financial Model · Petroleum Economics</div>
        </div>
    </div>
    <div class="nav-tag">v2.0</div>
</nav>

<!-- ═══ SIDEBAR ══════════════════════════════════════ -->
<aside class="sidebar">
    <div class="sb-label">Navigasi</div>
    <a class="sb-item active" href="index.php">
        <i class="bi bi-sliders2"></i> Input Parameter
    </a>
    <hr class="sb-hr">

    <div class="sb-label">Formula Utama</div>
    <div class="info-box">
        <div class="ib-title">Alur Perhitungan</div>
        <p>
            Income = Prod × Harga<br><br>
            Taxable Income<br>
            &nbsp;= Income − Opex − Di<br><br>
            Tax = Taxable × Tarif<br><br>
            <strong style="color:var(--amber)">NCF = Taxable − Tax</strong><br><br>
            NPV = Σ NCF/(1+r)ᵗ
        </p>
    </div>

    <div class="sb-label" style="margin-top:1rem">Depresiasi (Di)</div>
    <div class="info-box">
        <div class="ib-title">Straight-Line</div>
        <p>Di = TotalInv / N</p>
        <div class="ib-title" style="margin-top:.75rem">Declining Balance</div>
        <p>Di = BukuVal × (2/N)</p>
        <div class="ib-title" style="margin-top:.75rem">Sum of Years Digits</div>
        <p>Di = [(N−t+1)/Σt] × TotalInv</p>
    </div>

    <div class="sb-label" style="margin-top:1rem">Profil Produksi</div>
    <div class="info-box">
        <div class="ib-title">Build-up</div>
        <p>Pₜ = P₁×(1+g)^(t−1)<br>untuk t ≤ tPuncak</p>
        <div class="ib-title" style="margin-top:.75rem">Decline</div>
        <p>Pₜ = Pmax×(1−d)^(t−tPeak)<br>untuk t > tPuncak</p>
    </div>
</aside>

<!-- ═══ MAIN ══════════════════════════════════════════ -->
<main class="main">
<div class="page-body">

    <div class="page-hdr">
        <h1>Input Parameter Keekonomian</h1>
        <p>Isi seluruh parameter di bawah, lalu klik <strong style="color:var(--amber)">Hitung Keekonomian</strong> untuk menghasilkan tabel FM.</p>
    </div>

    <form action="calculate.php" method="POST">

        <!-- ─── 1. INVESTASI ──────────────────── -->
        <div class="scard">
            <div class="sc-title"><i class="bi bi-bank2"></i> Data Investasi Awal (Tahun 0)</div>
            <div class="g3">
                <div class="fg">
                    <label>Jangka Waktu Proyek (Tahun)</label>
                    <input type="number" name="jangka_waktu" placeholder="20" value="20" min="1" max="50" required>
                </div>
                <div class="fg">
                    <label>Capital ($M)</label>
                    <input type="number" name="capital" placeholder="13000" value="13000" step="0.01" required>
                </div>
                <div class="fg">
                    <label>Non-Capital ($M)</label>
                    <input type="number" name="non_capital" placeholder="8000" value="8000" step="0.01" required>
                </div>
            </div>
            <p class="hint">Total Investasi = Capital + Non-Capital → NCF Tahun 0 = −Total Investasi</p>
        </div>

        <!-- ─── 2. PRODUKSI ───────────────────── -->
        <div class="scard">
            <div class="sc-title"><i class="bi bi-droplet-fill"></i> Profil Produksi</div>
            <div class="g4">
                <div class="fg">
                    <label>Produksi Thn ke-1 (Mbbl/thn)</label>
                    <input type="number" name="prod_thn_1" placeholder="175" value="175" step="0.01" required>
                </div>
                <div class="fg">
                    <label>Laju Kenaikan (%/thn)</label>
                    <input type="number" name="laju_kenaikan" placeholder="11.3" value="11.3" step="0.01" min="0">
                    <p class="hint">Saat build-up</p>
                </div>
                <div class="fg">
                    <label>Tahun Puncak Produksi</label>
                    <input type="number" name="tahun_puncak" placeholder="3" value="3" min="1" required>
                    <p class="hint">Decline mulai thn berikutnya</p>
                </div>
                <div class="fg">
                    <label>Decline Rate (%/thn)</label>
                    <input type="number" name="decline" placeholder="3" value="3" step="0.01" min="0" required>
                </div>
            </div>
        </div>

        <!-- ─── 3. REVENUE & OPEX ─────────────── -->
        <div class="scard">
            <div class="sc-title"><i class="bi bi-currency-dollar"></i> Revenue &amp; Biaya Operasi</div>
            <div class="g4">
                <div class="fg">
                    <label>Harga Minyak ($/bbl)</label>
                    <input type="number" name="harga_minyak" placeholder="32" value="32" step="0.01" required>
                </div>
                <div class="fg">
                    <label>Base Opex ($M/thn)</label>
                    <input type="number" name="opex_base" placeholder="180" value="180" step="0.01" required>
                </div>
                <div class="fg">
                    <label>Eskalasi Opex (%/thn)</label>
                    <input type="number" name="opex_eskalasi" placeholder="2.5" value="2.5" step="0.01" min="0">
                    <p class="hint">0 = flat sepanjang proyek</p>
                </div>
                <div class="fg">
                    <label>Thn Mulai Eskalasi Opex</label>
                    <input type="number" name="tahun_mulai_eskalasi" placeholder="4" value="4" min="1">
                </div>
            </div>
        </div>

        <!-- ─── 4. PAJAK & DEPRESIASI ─────────── -->
        <div class="scard">
            <div class="sc-title"><i class="bi bi-percent"></i> Pajak, Depresiasi &amp; Diskonto</div>
            <div class="g3">
                <div class="fg">
                    <label>Tax Rate (%)</label>
                    <input type="number" name="pajak" placeholder="51" value="51" step="0.01" min="0" max="100" required>
                </div>
                <div class="fg">
                    <label>Discount Rate / WACC (%)</label>
                    <input type="number" name="discount_rate" placeholder="10" value="10" step="0.01" min="0" required>
                    <p class="hint">Untuk perhitungan NPV</p>
                </div>
                <div class="fg">
                    <label>Metode Depresiasi (Di)</label>
                    <select name="metode_depresiasi">
                        <option value="straight_line">Garis Lurus (Straight-Line)</option>
                        <option value="declining_balance">Saldo Menurun (Declining Balance)</option>
                        <option value="sum_years_digits">Jumlah Angka Tahun (SYD)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- ─── SUBMIT ────────────────────────── -->
        <button type="submit" class="btn-calc">
            <i class="bi bi-lightning-charge-fill"></i>
            Hitung Keekonomian
        </button>

    </form>
</div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>