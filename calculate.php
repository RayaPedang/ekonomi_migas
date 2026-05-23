<?php
/* ══════════════════════════════════════════════════════════
   EkoMigas Pro – calculate.php
   Perhitungan Financial Model (FM) Keekonomian Lapangan Migas
   ══════════════════════════════════════════════════════════ */

// ── Validasi input ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// ── Ambil & sanitasi data POST ──────────────────────────────
$jangkaWaktu        = max(1, (int)   $_POST['jangka_waktu']);
$capital            = (float) $_POST['capital'];
$nonCapital         = (float) $_POST['non_capital'];
$totalInvestasi     = $capital + $nonCapital;
$prodAwal           = (float) $_POST['prod_thn_1'];
$lajuKenaikan       = (float) $_POST['laju_kenaikan']       / 100;
$tahunPuncak        = max(1, (int)   $_POST['tahun_puncak']);
$declineRate        = (float) $_POST['decline']             / 100;
$hargaMinyak        = (float) $_POST['harga_minyak'];
$opexBase           = (float) $_POST['opex_base'];
$opexEskalasi       = (float) $_POST['opex_eskalasi']       / 100;
$tahunMulaiEskalasi = max(1, (int)   $_POST['tahun_mulai_eskalasi']);
$pajakRate          = (float) $_POST['pajak']               / 100;
$discountRate       = (float) $_POST['discount_rate']       / 100;
$metode             = $_POST['metode_depresiasi'];

// Label metode depresiasi
$metodeLabel = [
    'straight_line'    => 'Garis Lurus (Straight-Line)',
    'declining_balance'=> 'Saldo Menurun (Declining Balance)',
    'sum_years_digits' => 'Jumlah Angka Tahun (SYD)',
];

// ── Fungsi IRR (Newton-Raphson) ────────────────────────────
function hitungIRR(array $cashflows): ?float {
    $rate = 0.1;
    for ($iter = 0; $iter < 2000; $iter++) {
        $npv  = 0;
        $dnpv = 0;
        foreach ($cashflows as $t => $cf) {
            $d = pow(1 + $rate, $t);
            $npv  += $cf / $d;
            if ($t > 0) $dnpv -= $t * $cf / ($d * (1 + $rate));
        }
        if (abs($dnpv) < 1e-14) break;
        $new = $rate - $npv / $dnpv;
        if ($new < -0.999) $new = -0.5;
        if (abs($new - $rate) < 1e-9) { $rate = $new; break; }
        $rate = $new;
    }
    return (is_nan($rate) || is_infinite($rate)) ? null : $rate;
}

// ── Fungsi Payback Period ──────────────────────────────────
function hitungPayback(array $cashflows): ?float {
    $cum = 0;
    foreach ($cashflows as $t => $cf) {
        $prev = $cum;
        $cum += $cf;
        if ($t > 0 && $cum >= 0 && $prev < 0) {
            return $t - 1 + abs($prev) / abs($cum - $prev);
        }
    }
    return null;  // tidak tercapai
}

// ── Hitung produksi puncak ─────────────────────────────────
$prodPeak = $prodAwal * pow(1 + $lajuKenaikan, max(0, $tahunPuncak - 1));

// ── Loop utama ─────────────────────────────────────────────
$rows        = [];
$cashflows   = [-$totalInvestasi];   // t=0
$npv         = -$totalInvestasi;
$totalNCF_ND = 0;
$totalNCF_D  = 0;
$cumNCF      = -$totalInvestasi;

$nilaiBuku   = $totalInvestasi;      // untuk declining balance
$sumYears    = ($jangkaWaktu * ($jangkaWaktu + 1)) / 2;

for ($t = 1; $t <= $jangkaWaktu; $t++) {

    // — Produksi —
    if ($t <= $tahunPuncak) {
        $prod = $prodAwal * pow(1 + $lajuKenaikan, $t - 1);
    } else {
        $prod = $prodPeak * pow(1 - $declineRate, $t - $tahunPuncak);
    }

    // — Income —
    $income = $prod * $hargaMinyak;

    // — Opex dengan eskalasi —
    if ($t < $tahunMulaiEskalasi) {
        $opex = $opexBase;
    } else {
        $opex = $opexBase * pow(1 + $opexEskalasi, $t - $tahunMulaiEskalasi + 1);
    }

    // — Depresiasi (Di) —
    switch ($metode) {
        case 'declining_balance':
            $rate2 = 2 / $jangkaWaktu;   // double-declining
            $di    = $nilaiBuku * $rate2;
            $nilaiBuku -= $di;
            break;
        case 'sum_years_digits':
            $di = (($jangkaWaktu - $t + 1) / $sumYears) * $totalInvestasi;
            break;
        default: // straight_line
            $di = $totalInvestasi / $jangkaWaktu;
    }

    // — Taxable Income, Tax, NCF —
    $taxable = $income - $opex - $di;
    $tax     = ($taxable > 0) ? $taxable * $pajakRate : 0;
    $ncf_nd  = $taxable - $tax;               // NCF Undiscounted = Taxable − Tax
    $ncf_d   = $ncf_nd / pow(1 + $discountRate, $t);  // NCF Discounted
    $cumNCF += $ncf_nd;

    $totalNCF_ND += $ncf_nd;
    $totalNCF_D  += $ncf_d;
    $npv         += $ncf_d;
    $cashflows[]  = $ncf_nd;

    $rows[] = [
        't'         => $t,
        'prod'      => $prod,
        'income'    => $income,
        'opex'      => $opex,
        'di'        => $di,
        'taxable'   => $taxable,
        'tax'       => $tax,
        'ncf_nd'    => $ncf_nd,
        'ncf_d'     => $ncf_d,
        'cum_ncf'   => $cumNCF,
    ];
}

// ── Metrik keuangan ────────────────────────────────────────
$irr     = hitungIRR($cashflows);
$payback = hitungPayback($cashflows);
$pi      = ($totalInvestasi > 0) ? (($npv + $totalInvestasi) / $totalInvestasi) : 0;

// ── Format angka ───────────────────────────────────────────
function fmt(float $v, int $dec = 1): string {
    return number_format($v, $dec, '.', ',');
}
function fmtPct(float $v): string {
    return number_format($v * 100, 2, '.', ',') . '%';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil FM – EkoMigas Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&family=Plus+Jakarta+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --bg:      #07101e;
            --bg2:     #0b1929;
            --card:    #0f1f33;
            --sidebar: #091524;
            --border:  #162840;
            --amber:   #f59e0b;
            --amber2:  #fbbf24;
            --amber-bg:rgba(245,158,11,.08);
            --cyan:    #22d3ee;
            --text:    #dde5f0;
            --muted:   #5a7290;
            --dim:     #8aa4bf;
            --success: #10b981;
            --danger:  #ef4444;
            --in-bg:   #061120;
            --in-bdr:  #1c3352;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
        }
        body::before {
            content: ''; position: fixed; inset: 0;
            background-image:
                linear-gradient(rgba(245,158,11,.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(245,158,11,.025) 1px, transparent 1px);
            background-size: 44px 44px;
            pointer-events: none; z-index: 0;
        }

        /* ── Navbar ─────────────────────────── */
        .top-nav {
            position: fixed; top: 0; left: 0; right: 0; height: 62px;
            background: var(--bg2); border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 1.75rem; z-index: 200; backdrop-filter: blur(12px);
        }
        .nav-brand { display: flex; align-items: center; gap: 12px; }
        .nav-logo {
            width: 38px; height: 38px; background: var(--amber);
            border-radius: 9px; display: flex; align-items: center;
            justify-content: center; font-family: 'Rajdhani', sans-serif;
            font-weight: 700; font-size: 15px; color: #000;
            box-shadow: 0 0 20px rgba(245,158,11,.35);
        }
        .nav-title { font-family: 'Rajdhani', sans-serif; font-weight: 700; font-size: 19px; color: var(--text); }
        .nav-sub   { font-size: 11px; color: var(--muted); letter-spacing: .8px; text-transform: uppercase; }
        .nav-actions { display: flex; gap: .75rem; align-items: center; }
        .btn-back {
            background: var(--amber-bg);
            border: 1px solid rgba(245,158,11,.3);
            color: var(--amber);
            font-family: 'Rajdhani', sans-serif;
            font-weight: 600; font-size: 13px;
            padding: 7px 16px; border-radius: 8px;
            cursor: pointer; text-decoration: none;
            display: flex; align-items: center; gap: 6px;
            transition: .18s all;
        }
        .btn-back:hover { background: var(--amber); color: #000; }
        .btn-print {
            background: rgba(34,211,238,.1);
            border: 1px solid rgba(34,211,238,.3);
            color: var(--cyan);
            font-family: 'Rajdhani', sans-serif;
            font-weight: 600; font-size: 13px;
            padding: 7px 16px; border-radius: 8px;
            cursor: pointer; text-decoration: none;
            display: flex; align-items: center; gap: 6px;
            transition: .18s all;
        }
        .btn-print:hover { background: var(--cyan); color: #000; }

        /* ── Sidebar ────────────────────────── */
        .sidebar {
            position: fixed; left: 0; top: 62px; bottom: 0;
            width: 255px; background: var(--sidebar);
            border-right: 1px solid var(--border);
            padding: 1.5rem 1rem; overflow-y: auto; z-index: 100;
        }
        .sb-label {
            font-size: 10px; font-weight: 700; text-transform: uppercase;
            letter-spacing: 1.5px; color: var(--muted);
            padding: .35rem .6rem; margin-top: .5rem; display: block;
        }
        .sb-item {
            display: flex; align-items: center; gap: 10px;
            padding: 8px 12px; border-radius: 9px;
            color: var(--dim); font-size: 13.5px; cursor: default;
            margin-bottom: 2px;
        }
        .sb-item.active { background: var(--amber-bg); color: var(--amber); border: 1px solid rgba(245,158,11,.22); }
        .sb-item i { font-size: 15px; }
        .sb-hr { border: none; border-top: 1px solid var(--border); margin: .75rem 0; }

        .param-box {
            background: var(--card); border: 1px solid var(--border);
            border-radius: 10px; padding: 1rem 1.1rem; margin-top: .25rem;
        }
        .param-row {
            display: flex; justify-content: space-between; align-items: baseline;
            padding: 4px 0; border-bottom: 1px solid var(--border);
            font-size: 12px;
        }
        .param-row:last-child { border-bottom: none; }
        .param-row .k { color: var(--dim); }
        .param-row .v {
            font-family: 'IBM Plex Mono', monospace;
            color: var(--amber); font-size: 11.5px;
        }

        /* ── Main ───────────────────────────── */
        .main { margin-left: 255px; padding-top: 62px; position: relative; z-index: 1; }
        .page-body { padding: 2.2rem 2.8rem 3rem; }

        .page-hdr { margin-bottom: 1.75rem; }
        .page-hdr h1 { font-family: 'Rajdhani', sans-serif; font-weight: 700; font-size: 26px; color: var(--text); margin-bottom: .3rem; }
        .page-hdr p { color: var(--muted); font-size: 13.5px; }

        /* ── Metric cards ───────────────────── */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.1rem;
            margin-bottom: 2rem;
        }
        .mcard {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.25rem 1.4rem;
            position: relative; overflow: hidden;
            transition: .2s transform;
        }
        .mcard:hover { transform: translateY(-2px); }
        .mcard::before {
            content: ''; position: absolute;
            top: 0; left: 0; right: 0; height: 2px;
        }
        .mcard.c-amber::before { background: var(--amber); }
        .mcard.c-cyan::before  { background: var(--cyan);  }
        .mcard.c-green::before { background: var(--success); }
        .mcard.c-red::before   { background: var(--danger);  }

        .mcard .mc-icon {
            width: 36px; height: 36px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 17px; margin-bottom: .85rem;
        }
        .c-amber .mc-icon { background: rgba(245,158,11,.12); color: var(--amber); }
        .c-cyan  .mc-icon { background: rgba(34,211,238,.1);  color: var(--cyan);  }
        .c-green .mc-icon { background: rgba(16,185,129,.1);  color: var(--success); }
        .c-red   .mc-icon { background: rgba(239,68,68,.1);   color: var(--danger);  }

        .mcard .mc-label {
            font-size: 11px; font-weight: 700; text-transform: uppercase;
            letter-spacing: .8px; color: var(--muted); margin-bottom: .3rem;
        }
        .mcard .mc-value {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 22px; font-weight: 600; color: var(--text);
            line-height: 1;
        }
        .mcard .mc-unit { font-size: 12px; color: var(--dim); margin-top: .4rem; }

        .positive { color: var(--success) !important; }
        .negative { color: var(--danger)  !important; }

        /* ── Table card ─────────────────────── */
        .tcard {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 13px;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        .tcard-hdr {
            padding: 1.2rem 1.6rem;
            border-bottom: 1px solid var(--border);
            font-family: 'Rajdhani', sans-serif;
            font-weight: 700; font-size: 14px;
            color: var(--amber); text-transform: uppercase;
            letter-spacing: 1px;
            display: flex; align-items: center; gap: 8px;
        }

        .table-wrap { overflow-x: auto; }

        table {
            width: 100%; border-collapse: collapse;
            font-size: 12.5px;
        }

        thead tr th {
            background: #0a1e35;
            padding: 10px 10px;
            text-align: center;
            font-family: 'Rajdhani', sans-serif;
            font-weight: 700; font-size: 12px;
            text-transform: uppercase; letter-spacing: .6px;
            color: var(--dim);
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
            position: sticky; top: 0;
        }
        thead tr:first-child th {
            border-top: none;
        }
        thead tr:nth-child(2) th {
            background: #071626;
            font-size: 11px;
            color: var(--muted);
        }

        /* Investasi colspan header */
        .th-investasi {
            border-left: 1px solid rgba(245,158,11,.2);
            border-right: 1px solid rgba(245,158,11,.2);
            color: var(--amber) !important;
            background: rgba(245,158,11,.04) !important;
        }

        tbody tr {
            border-bottom: 1px solid rgba(22,40,64,.8);
            transition: .12s background;
        }
        tbody tr:hover { background: rgba(245,158,11,.03); }
        tbody tr:nth-child(even) { background: rgba(7,16,30,.6); }
        tbody tr:nth-child(even):hover { background: rgba(245,158,11,.04); }

        tbody td {
            padding: 8px 10px;
            text-align: right;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 12px;
            white-space: nowrap;
            color: var(--text);
        }
        tbody td:first-child {
            text-align: center;
            font-weight: 600; color: var(--amber);
            background: rgba(7,16,30,.5);
        }

        /* Year 0 row */
        .row-zero { background: rgba(245,158,11,.04) !important; }
        .row-zero td { color: var(--amber) !important; }

        /* Total row */
        .row-total td {
            font-weight: 700; color: var(--amber) !important;
            background: rgba(245,158,11,.07) !important;
            border-top: 2px solid rgba(245,158,11,.3) !important;
            font-size: 12.5px;
        }

        .td-investasi {
            border-left: 1px solid rgba(245,158,11,.12);
        }
        .td-investasi-r {
            border-right: 1px solid rgba(245,158,11,.12);
        }

        .neg { color: var(--danger)  !important; }
        .pos { color: var(--success) !important; }
        .dim { color: var(--muted)   !important; }

        /* ── Scrollbar ─────────────────────── */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 6px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--muted); }

        @media print {
            .sidebar, .top-nav, .btn-back, .btn-print { display: none !important; }
            .main { margin-left: 0 !important; padding-top: 0 !important; }
        }
    </style>
</head>
<body>

<!-- ═══ NAVBAR ═══════════════════════════════════════════ -->
<nav class="top-nav">
    <div class="nav-brand">
        <div class="nav-logo">FM</div>
        <div>
            <div class="nav-title">EkoMigas Pro</div>
            <div class="nav-sub">Hasil Perhitungan Keekonomian</div>
        </div>
    </div>
    <div class="nav-actions">
        <a href="index.php" class="btn-back"><i class="bi bi-arrow-left"></i> Input Ulang</a>
        <a href="javascript:window.print()" class="btn-print"><i class="bi bi-printer"></i> Print</a>
    </div>
</nav>

<!-- ═══ SIDEBAR ══════════════════════════════════════════ -->
<aside class="sidebar">
    <span class="sb-label">Navigasi</span>
    <div class="sb-item active"><i class="bi bi-table"></i> Tabel Hasil FM</div>

    <hr class="sb-hr">

    <span class="sb-label">Parameter Input</span>
    <div class="param-box">
        <div class="param-row"><span class="k">Jangka Waktu</span><span class="v"><?= $jangkaWaktu ?> thn</span></div>
        <div class="param-row"><span class="k">Capital</span><span class="v">$<?= fmt($capital,0) ?>M</span></div>
        <div class="param-row"><span class="k">Non-Capital</span><span class="v">$<?= fmt($nonCapital,0) ?>M</span></div>
        <div class="param-row"><span class="k">Total Investasi</span><span class="v">$<?= fmt($totalInvestasi,0) ?>M</span></div>
        <div class="param-row"><span class="k">Prod. Thn-1</span><span class="v"><?= fmt($prodAwal,0) ?> Mbbl</span></div>
        <div class="param-row"><span class="k">Thn Puncak</span><span class="v"><?= $tahunPuncak ?></span></div>
        <div class="param-row"><span class="k">Decline Rate</span><span class="v"><?= fmt($declineRate*100,1) ?>%</span></div>
        <div class="param-row"><span class="k">Harga Minyak</span><span class="v">$<?= fmt($hargaMinyak,2) ?>/bbl</span></div>
        <div class="param-row"><span class="k">Base Opex</span><span class="v">$<?= fmt($opexBase,1) ?>M</span></div>
        <div class="param-row"><span class="k">Eskalasi Opex</span><span class="v"><?= fmt($opexEskalasi*100,1) ?>%</span></div>
        <div class="param-row"><span class="k">Tax Rate</span><span class="v"><?= fmt($pajakRate*100,1) ?>%</span></div>
        <div class="param-row"><span class="k">Discount Rate</span><span class="v"><?= fmt($discountRate*100,1) ?>%</span></div>
        <div class="param-row"><span class="k">Depresiasi</span><span class="v"><?= $metode === 'straight_line' ? 'SL' : ($metode === 'declining_balance' ? 'DB' : 'SYD') ?></span></div>
    </div>
</aside>

<!-- ═══ MAIN ══════════════════════════════════════════════ -->
<main class="main">
<div class="page-body">

    <div class="page-hdr">
        <h1>Hasil Analisis Keekonomian Lapangan Migas</h1>
        <p>Metode Depresiasi: <strong style="color:var(--amber)"><?= $metodeLabel[$metode] ?? $metode ?></strong>
           &nbsp;·&nbsp; Discount Rate: <strong style="color:var(--cyan)"><?= fmt($discountRate*100,1) ?>%</strong>
           &nbsp;·&nbsp; Tax Rate: <strong style="color:var(--cyan)"><?= fmt($pajakRate*100,1) ?>%</strong>
        </p>
    </div>

    <!-- ─── METRIC CARDS ─────────────────────────────── -->
    <div class="metrics-grid">

        <!-- NPV -->
        <div class="mcard c-amber">
            <div class="mc-icon"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="mc-label">NPV</div>
            <div class="mc-value <?= $npv >= 0 ? 'positive' : 'negative' ?>">
                $<?= fmt(abs($npv), 1) ?>M
            </div>
            <div class="mc-unit"><?= $npv >= 0 ? '▲ Proyek layak' : '▼ NPV negatif' ?></div>
        </div>

        <!-- IRR -->
        <div class="mcard c-cyan">
            <div class="mc-icon"><i class="bi bi-percent"></i></div>
            <div class="mc-label">IRR</div>
            <div class="mc-value <?= ($irr !== null && $irr >= $discountRate) ? 'positive' : 'negative' ?>">
                <?= $irr !== null ? fmt($irr*100, 2).'%' : 'N/A' ?>
            </div>
            <div class="mc-unit">
                <?php if ($irr !== null): ?>
                    <?= $irr >= $discountRate ? '▲ IRR > WACC' : '▼ IRR < WACC' ?>
                <?php else: echo 'Tidak dapat dihitung'; endif; ?>
            </div>
        </div>

        <!-- Payback Period -->
        <div class="mcard c-green">
            <div class="mc-icon"><i class="bi bi-clock-history"></i></div>
            <div class="mc-label">Payback Period</div>
            <div class="mc-value">
                <?= $payback !== null ? fmt($payback, 2).' thn' : 'N/A' ?>
            </div>
            <div class="mc-unit">
                <?= $payback !== null ? 'Pengembalian investasi' : 'Tidak tercapai dalam proyek' ?>
            </div>
        </div>

        <!-- Total NCF Undiscounted -->
        <div class="mcard c-red">
            <div class="mc-icon"><i class="bi bi-cash-coin"></i></div>
            <div class="mc-label">Total NCF (Undiscounted)</div>
            <div class="mc-value <?= $totalNCF_ND >= 0 ? 'positive' : 'negative' ?>">
                $<?= fmt(abs($totalNCF_ND), 1) ?>M
            </div>
            <div class="mc-unit">PI = <?= fmt($pi, 3) ?></div>
        </div>

    </div>

    <!-- ─── TABEL FM ──────────────────────────────────── -->
    <div class="tcard">
        <div class="tcard-hdr">
            <i class="bi bi-table"></i>
            Tabel Perhitungan Financial Model (FM)
        </div>

        <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th rowspan="2">Tahun</th>
                    <th rowspan="2">Produksi<br><small style="font-weight:400;color:var(--muted)">(Mbbl)</small></th>
                    <th rowspan="2">Income<br><small style="font-weight:400;color:var(--muted)">($M)</small></th>
                    <th colspan="2" class="th-investasi">Investasi</th>
                    <th rowspan="2">Opex<br><small style="font-weight:400;color:var(--muted)">($M)</small></th>
                    <th rowspan="2">Di<br><small style="font-weight:400;color:var(--muted)">($M)</small></th>
                    <th rowspan="2">Taxable Income<br><small style="font-weight:400;color:var(--muted)">($M)</small></th>
                    <th rowspan="2">Tax<br><small style="font-weight:400;color:var(--muted)">($M)</small></th>
                    <th rowspan="2">NCF Undiscounted<br><small style="font-weight:400;color:var(--muted)">($M)</small></th>
                    <th rowspan="2">NCF Discounted<br><small style="font-weight:400;color:var(--muted)">($M)</small></th>
                    <th rowspan="2">Kumulatif NCF<br><small style="font-weight:400;color:var(--muted)">($M)</small></th>
                </tr>
                <tr>
                    <th class="th-investasi">Capital ($M)</th>
                    <th class="th-investasi">Non-Capital ($M)</th>
                </tr>
            </thead>
            <tbody>
                <!-- Tahun 0 -->
                <tr class="row-zero">
                    <td>0</td>
                    <td class="dim">—</td>
                    <td class="dim">—</td>
                    <td class="td-investasi"><?= fmt($capital, 1) ?></td>
                    <td class="td-investasi-r"><?= fmt($nonCapital, 1) ?></td>
                    <td class="dim">—</td>
                    <td class="dim">—</td>
                    <td class="dim">—</td>
                    <td class="dim">—</td>
                    <td class="neg">(<?= fmt($totalInvestasi, 1) ?>)</td>
                    <td class="neg">(<?= fmt($totalInvestasi, 1) ?>)</td>
                    <td class="neg">(<?= fmt($totalInvestasi, 1) ?>)</td>
                </tr>

                <?php
                $cumDisc = -$totalInvestasi;
                foreach ($rows as $r):
                    $isNeg = $r['ncf_nd'] < 0;
                    $cumDisc += $r['ncf_d'];
                ?>
                <tr>
                    <td><?= $r['t'] ?></td>
                    <td><?= fmt($r['prod'], 0) ?></td>
                    <td><?= fmt($r['income'], 1) ?></td>
                    <td class="td-investasi dim">0</td>
                    <td class="td-investasi-r dim">0</td>
                    <td><?= fmt($r['opex'], 1) ?></td>
                    <td><?= fmt($r['di'], 1) ?></td>
                    <td class="<?= $r['taxable'] < 0 ? 'neg' : '' ?>"><?= fmt($r['taxable'], 1) ?></td>
                    <td><?= fmt($r['tax'], 1) ?></td>
                    <td class="<?= $r['ncf_nd'] < 0 ? 'neg' : 'pos' ?>"><?= fmt($r['ncf_nd'], 1) ?></td>
                    <td class="<?= $r['ncf_d']  < 0 ? 'neg' : '' ?>"><?= fmt($r['ncf_d'],  1) ?></td>
                    <td class="<?= $r['cum_ncf'] < 0 ? 'neg' : 'pos' ?>"><?= fmt($r['cum_ncf'], 1) ?></td>
                </tr>
                <?php endforeach; ?>

                <!-- Total row -->
                <tr class="row-total">
                    <td colspan="9" style="text-align:right;letter-spacing:.5px">TOTAL</td>
                    <td><?= fmt($totalNCF_ND, 1) ?></td>
                    <td><?= fmt($totalNCF_D,  1) ?></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
        </div><!-- /table-wrap -->
    </div><!-- /tcard -->

    <!-- ─── CATATAN RUMUS ─────────────────────────────── -->
    <div style="background:var(--card);border:1px solid var(--border);border-radius:12px;padding:1.25rem 1.6rem;">
        <div style="font-family:'Rajdhani',sans-serif;font-weight:700;font-size:13px;color:var(--amber);text-transform:uppercase;letter-spacing:1px;margin-bottom:.85rem;">
            <i class="bi bi-info-circle"></i>&nbsp; Catatan Formula &amp; Definisi
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;font-size:12px;color:var(--dim);font-family:'IBM Plex Mono',monospace;line-height:1.9;">
            <div>
                <span style="color:var(--amber)">Income</span> = Produksi × Harga Minyak<br>
                <span style="color:var(--amber)">Taxable Income</span> = Income − Opex − Di<br>
                <span style="color:var(--amber)">Tax</span> = Taxable × <?= fmt($pajakRate*100,1) ?>% (jika &gt; 0)<br>
                <span style="color:var(--amber)">NCF Undiscounted</span> = Taxable − Tax
            </div>
            <div>
                <span style="color:var(--cyan)">NCF Discounted</span> = NCF / (1+r)ᵗ<br>
                <span style="color:var(--cyan)">r</span> = Discount Rate <?= fmt($discountRate*100,1) ?>%<br>
                <span style="color:var(--cyan)">NPV</span> = Σ NCF_Disc (t=0 s.d. <?= $jangkaWaktu ?>)<br>
                <span style="color:var(--cyan)">PI</span> = (NPV + Inv) / Inv
            </div>
            <div>
                <span style="color:var(--success)">Produksi (build-up)</span>: P₁×(1+g)^(t−1)<br>
                <span style="color:var(--success)">Produksi (decline)</span>: P_max×(1−d)^(t−t_peak)<br>
                <span style="color:var(--success)">Opex Eskalasi</span>: mulai thn <?= $tahunMulaiEskalasi ?> @<?= fmt($opexEskalasi*100,1) ?>%<br>
                <span style="color:var(--success)">IRR</span>: Newton-Raphson iteration
            </div>
        </div>
    </div>

</div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>