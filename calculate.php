<?php
require_once __DIR__ . '/storage.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }

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
$projectId          = sanitizeProjectId(trim($_POST['project_id']   ?? ''));
$projectName        = strip_tags(trim($_POST['project_name'] ?? ''));

if ($projectId && !$projectName) {
    $stored = loadProject($projectId);
    if ($stored) $projectName = $stored['name'];
}

$metodeLabel = ['straight_line'=>'Garis Lurus (Straight-Line)','declining_balance'=>'Saldo Menurun (Declining Balance)','sum_years_digits'=>'Jumlah Angka Tahun (SYD)'];

function hitungIRR(array $cashflows): ?float {
    $rate = 0.1;
    for ($iter = 0; $iter < 2000; $iter++) {
        $npv = $dnpv = 0;
        foreach ($cashflows as $t => $cf) { $d=$pow=pow(1+$rate,$t); $npv+=$cf/$d; if($t>0)$dnpv-=$t*$cf/($d*(1+$rate)); }
        if (abs($dnpv) < 1e-14) break;
        $new = $rate - $npv / $dnpv;
        if ($new < -0.999) $new = -0.5;
        if (abs($new - $rate) < 1e-9) { $rate = $new; break; }
        $rate = $new;
    }
    return (is_nan($rate) || is_infinite($rate)) ? null : $rate;
}

function hitungPayback(array $cashflows): ?float {
    $cum = 0;
    foreach ($cashflows as $t => $cf) { $prev=$cum; $cum+=$cf; if($t>0&&$cum>=0&&$prev<0) return $t-1+abs($prev)/abs($cum-$prev); }
    return null;
}

$prodPeak    = $prodAwal * pow(1 + $lajuKenaikan, max(0, $tahunPuncak - 1));
$rows        = [];
$cashflows   = [-$totalInvestasi];
$npv         = -$totalInvestasi;
$totalNCF_ND = 0;
$totalNCF_D  = 0;
$cumNCF      = -$totalInvestasi;
$nilaiBuku   = $totalInvestasi;
$sumYears    = ($jangkaWaktu * ($jangkaWaktu + 1)) / 2;

for ($t = 1; $t <= $jangkaWaktu; $t++) {
    $prod = ($t <= $tahunPuncak) ? $prodAwal * pow(1+$lajuKenaikan,$t-1) : $prodPeak * pow(1-$declineRate,$t-$tahunPuncak);
    $income = $prod * $hargaMinyak;
    $opex = ($t < $tahunMulaiEskalasi) ? $opexBase : $opexBase * pow(1+$opexEskalasi,$t-$tahunMulaiEskalasi+1);
    switch ($metode) {
        case 'declining_balance': $di=$nilaiBuku*(2/$jangkaWaktu); $nilaiBuku-=$di; break;
        case 'sum_years_digits':  $di=(($jangkaWaktu-$t+1)/$sumYears)*$totalInvestasi; break;
        default: $di = $totalInvestasi / $jangkaWaktu;
    }
    $taxable=$income-$opex-$di; $tax=($taxable>0)?$taxable*$pajakRate:0;
    $ncf_nd=$taxable-$tax; $ncf_d=$ncf_nd/pow(1+$discountRate,$t);
    $cumNCF+=$ncf_nd; $totalNCF_ND+=$ncf_nd; $totalNCF_D+=$ncf_d; $npv+=$ncf_d; $cashflows[]=$ncf_nd;
    $rows[]=compact('t','prod','income','opex','di','taxable','tax','ncf_nd','ncf_d')+['cum_ncf'=>$cumNCF];
}

$irr     = hitungIRR($cashflows);
$payback = hitungPayback($cashflows);
$pi      = ($totalInvestasi > 0) ? (($npv + $totalInvestasi) / $totalInvestasi) : 0;

function fmt(float $v, int $dec=1): string { return number_format($v,$dec,'.',','); }
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
        :root{--bg:#07101e;--bg2:#0b1929;--card:#0f1f33;--sidebar:#091524;--border:#162840;--amber:#f59e0b;--amber2:#fbbf24;--amber-bg:rgba(245,158,11,.08);--cyan:#22d3ee;--text:#dde5f0;--muted:#5a7290;--dim:#8aa4bf;--success:#10b981;--danger:#ef4444;--in-bg:#061120;--in-bdr:#1c3352;--sidebar-w:240px}
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        body{background:var(--bg);color:var(--text);font-family:'Plus Jakarta Sans',sans-serif;min-height:100vh}
        body::before{content:'';position:fixed;inset:0;background-image:linear-gradient(rgba(245,158,11,.025) 1px,transparent 1px),linear-gradient(90deg,rgba(245,158,11,.025) 1px,transparent 1px);background-size:44px 44px;pointer-events:none;z-index:0}

        .top-nav{position:fixed;top:0;left:0;right:0;height:62px;background:var(--bg2);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 1.5rem;z-index:300;backdrop-filter:blur(12px)}
        .nav-left{display:flex;align-items:center;gap:14px}
        .btn-toggle{width:38px;height:38px;background:transparent;border:1px solid var(--border);border-radius:9px;color:var(--dim);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:.18s all;flex-shrink:0}
        .btn-toggle:hover{background:var(--amber-bg);border-color:rgba(245,158,11,.3);color:var(--amber)}
        .btn-toggle i{font-size:17px}
        .nav-brand{display:flex;align-items:center;gap:10px}
        .nav-logo{width:36px;height:36px;background:var(--amber);border-radius:8px;display:flex;align-items:center;justify-content:center;font-family:'Rajdhani',sans-serif;font-weight:700;font-size:14px;color:#000;box-shadow:0 0 18px rgba(245,158,11,.35);flex-shrink:0}
        .nav-title{font-family:'Rajdhani',sans-serif;font-weight:700;font-size:18px;color:var(--text)}
        .nav-sub{font-size:10.5px;color:var(--muted);letter-spacing:.8px;text-transform:uppercase}
        .nav-actions{display:flex;gap:.65rem;align-items:center}
        .btn-nav{border-radius:8px;cursor:pointer;text-decoration:none;display:flex;align-items:center;gap:6px;font-family:'Rajdhani',sans-serif;font-weight:600;font-size:13px;padding:7px 14px;transition:.18s all}
        .btn-back{background:var(--amber-bg);border:1px solid rgba(245,158,11,.3);color:var(--amber)}
        .btn-back:hover{background:var(--amber);color:#000}
        .btn-projs{background:rgba(34,211,238,.1);border:1px solid rgba(34,211,238,.3);color:var(--cyan)}
        .btn-projs:hover{background:var(--cyan);color:#000}
        .btn-print{background:rgba(100,120,160,.1);border:1px solid rgba(100,120,160,.25);color:var(--dim)}
        .btn-print:hover{background:var(--dim);color:var(--bg)}

        .sidebar{position:fixed;left:0;top:62px;bottom:0;width:var(--sidebar-w);background:var(--sidebar);border-right:1px solid var(--border);padding:1.4rem .9rem;overflow-y:auto;z-index:200;transition:transform .25s ease}
        .sidebar.collapsed{transform:translateX(-100%)}
        .sb-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:var(--muted);padding:.3rem .6rem;margin-top:.5rem;display:block}
        .sb-item{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:9px;color:var(--dim);font-size:13.5px;text-decoration:none;transition:.18s all;margin-bottom:2px}
        .sb-item:hover{background:rgba(245,158,11,.06);color:var(--text)}
        .sb-item.active{background:var(--amber-bg);color:var(--amber);border:1px solid rgba(245,158,11,.22)}
        .sb-item i{font-size:15px;flex-shrink:0}
        .sb-hr{border:none;border-top:1px solid var(--border);margin:.75rem 0}

        .main{margin-left:var(--sidebar-w);padding-top:62px;position:relative;z-index:1;transition:margin-left .25s ease}
        .main.expanded{margin-left:0}
        .page-body{padding:2rem 2.5rem 3rem}
        .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:150}
        .sidebar-overlay.show{display:block}

        .page-hdr{margin-bottom:1.6rem}
        .page-hdr h1{font-family:'Rajdhani',sans-serif;font-weight:700;font-size:25px;color:var(--text);margin-bottom:.3rem}
        .page-hdr p{color:var(--muted);font-size:13px}

        /* Save panel */
        .save-panel{background:var(--card);border:1px solid var(--border);border-radius:13px;padding:1.2rem 1.5rem;margin-bottom:1.6rem;position:relative;overflow:hidden}
        .save-panel::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--success) 0%,var(--cyan) 50%,transparent 100%)}
        .sp-header{font-family:'Rajdhani',sans-serif;font-weight:700;font-size:13px;color:var(--success);text-transform:uppercase;letter-spacing:1px;display:flex;align-items:center;gap:8px;margin-bottom:.9rem}
        .sp-update-row{display:flex;align-items:center;justify-content:space-between;gap:1rem}
        .sp-proj-info{display:flex;align-items:center;gap:12px}
        .sp-proj-badge{background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.3);color:var(--success);font-size:10.5px;font-weight:700;padding:3px 9px;border-radius:20px;font-family:'IBM Plex Mono',monospace;text-transform:uppercase}
        .sp-proj-name{font-family:'Rajdhani',sans-serif;font-size:16px;font-weight:700;color:var(--text)}
        .sp-actions{display:flex;gap:.55rem;align-items:center}
        .sp-new-row{display:flex;gap:.7rem;align-items:center}
        .sp-input{flex:1;background:var(--in-bg);border:1px solid var(--in-bdr);color:var(--text);border-radius:8px;padding:9px 13px;font-family:'IBM Plex Mono',monospace;font-size:13px;transition:.18s all}
        .sp-input:focus{outline:none;border-color:var(--success);box-shadow:0 0 0 3px rgba(16,185,129,.12)}
        .sp-input::placeholder{color:var(--muted)}
        .btn-save{padding:9px 18px;border:none;border-radius:8px;cursor:pointer;font-family:'Rajdhani',sans-serif;font-weight:700;font-size:13px;display:flex;align-items:center;gap:6px;transition:.18s all;white-space:nowrap}
        .btn-save-new{background:var(--success);color:#fff}
        .btn-save-new:hover{background:#0ea271;transform:translateY(-1px)}
        .btn-save-update{background:var(--amber);color:#000}
        .btn-save-update:hover{background:var(--amber2);transform:translateY(-1px)}
        .btn-sp-sec{padding:9px 14px;background:transparent;border:1px solid var(--border);color:var(--dim);font-family:'Rajdhani',sans-serif;font-weight:600;font-size:13px;border-radius:8px;cursor:pointer;text-decoration:none;display:flex;align-items:center;gap:6px;transition:.18s all}
        .btn-sp-sec:hover{border-color:var(--dim);color:var(--text)}

        /* Metric cards */
        .metrics-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.75rem}
        .mcard{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:1.2rem 1.3rem;position:relative;overflow:hidden;transition:.2s transform}
        .mcard:hover{transform:translateY(-2px)}
        .mcard::before{content:'';position:absolute;top:0;left:0;right:0;height:2px}
        .mcard.c-amber::before{background:var(--amber)}.mcard.c-cyan::before{background:var(--cyan)}.mcard.c-green::before{background:var(--success)}.mcard.c-red::before{background:var(--danger)}
        .mc-icon{width:34px;height:34px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:16px;margin-bottom:.75rem}
        .c-amber .mc-icon{background:rgba(245,158,11,.12);color:var(--amber)}.c-cyan .mc-icon{background:rgba(34,211,238,.1);color:var(--cyan)}.c-green .mc-icon{background:rgba(16,185,129,.1);color:var(--success)}.c-red .mc-icon{background:rgba(239,68,68,.1);color:var(--danger)}
        .mc-label{font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--muted);margin-bottom:.3rem}
        .mc-value{font-family:'IBM Plex Mono',monospace;font-size:21px;font-weight:600;color:var(--text);line-height:1}
        .mc-unit{font-size:11.5px;color:var(--dim);margin-top:.4rem}
        .positive{color:var(--success)!important}.negative{color:var(--danger)!important}

        /* Table */
        .tcard{background:var(--card);border:1px solid var(--border);border-radius:13px;overflow:hidden;margin-bottom:1.5rem}
        .tcard-hdr{padding:1.1rem 1.5rem;border-bottom:1px solid var(--border);font-family:'Rajdhani',sans-serif;font-weight:700;font-size:14px;color:var(--amber);text-transform:uppercase;letter-spacing:1px;display:flex;align-items:center;gap:8px}
        .table-wrap{overflow-x:auto}
        table{width:100%;border-collapse:collapse;font-size:12px}
        thead tr th{background:#0a1e35;padding:9px 10px;text-align:center;font-family:'Rajdhani',sans-serif;font-weight:700;font-size:11.5px;text-transform:uppercase;letter-spacing:.5px;color:var(--dim);border-bottom:1px solid var(--border);white-space:nowrap}
        thead tr:nth-child(2) th{background:#071626;font-size:10.5px;color:var(--muted)}
        .th-inv{border-left:1px solid rgba(245,158,11,.2);border-right:1px solid rgba(245,158,11,.2);color:var(--amber)!important;background:rgba(245,158,11,.04)!important}
        tbody tr{border-bottom:1px solid rgba(22,40,64,.8);transition:.12s background}
        tbody tr:hover{background:rgba(245,158,11,.03)}
        tbody tr:nth-child(even){background:rgba(7,16,30,.6)}
        tbody td{padding:7px 10px;text-align:right;font-family:'IBM Plex Mono',monospace;font-size:11.5px;white-space:nowrap;color:var(--text)}
        tbody td:first-child{text-align:center;font-weight:600;color:var(--amber);background:rgba(7,16,30,.5)}
        .row-zero{background:rgba(245,158,11,.04)!important}.row-zero td{color:var(--amber)!important}
        .row-total td{font-weight:700;color:var(--amber)!important;background:rgba(245,158,11,.07)!important;border-top:2px solid rgba(245,158,11,.3)!important}
        .td-inv{border-left:1px solid rgba(245,158,11,.12)}.td-inv-r{border-right:1px solid rgba(245,158,11,.12)}
        .neg{color:var(--danger)!important}.pos{color:var(--success)!important}.dim{color:var(--muted)!important}

        /* Formula notes */
        .formula-card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:1.2rem 1.5rem}
        .formula-title{font-family:'Rajdhani',sans-serif;font-weight:700;font-size:13px;color:var(--amber);text-transform:uppercase;letter-spacing:1px;margin-bottom:.8rem;display:flex;align-items:center;gap:7px}
        .formula-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;font-size:11.5px;color:var(--dim);font-family:'IBM Plex Mono',monospace;line-height:1.9}
        .formula-grid span{color:var(--amber)}.formula-grid .sc{color:var(--cyan)}.formula-grid .sg{color:var(--success)}

        ::-webkit-scrollbar{width:6px;height:6px}::-webkit-scrollbar-track{background:var(--bg)}::-webkit-scrollbar-thumb{background:var(--border);border-radius:6px}
        @media print{.sidebar,.top-nav,.save-panel,.sidebar-overlay{display:none!important}.main{margin-left:0!important;padding-top:0!important}}
    </style>
</head>
<body>

<nav class="top-nav">
    <div class="nav-left">
        <button class="btn-toggle" id="sidebarToggle" title="Buka/Tutup Sidebar"><i class="bi bi-list"></i></button>
        <div class="nav-brand">
            <div class="nav-logo">FM</div>
            <div>
                <div class="nav-title">EkoMigas Pro</div>
                <div class="nav-sub">Hasil Perhitungan</div>
            </div>
        </div>
    </div>
    <div class="nav-actions">
        <a href="<?= $projectId ? "index.php?load=$projectId" : 'index.php' ?>" class="btn-nav btn-back"><i class="bi bi-arrow-left"></i> Input Ulang</a>
        <a href="project.php" class="btn-nav btn-projs"><i class="bi bi-folder2-open"></i> Semua Proyek</a>
        <a href="javascript:window.print()" class="btn-nav btn-print"><i class="bi bi-printer"></i> Print</a>
    </div>
</nav>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="sidebar">
    <span class="sb-label">Menu</span>
    <a class="sb-item" href="index.php"><i class="bi bi-sliders2"></i> Input Parameter</a>
    <a class="sb-item" href="project.php"><i class="bi bi-folder2-open"></i> Semua Proyek</a>
    <hr class="sb-hr">
    <span class="sb-label">Hasil</span>
    <div class="sb-item active"><i class="bi bi-table"></i> Tabel FM</div>
</aside>

<main class="main" id="mainContent">
<div class="page-body">

    <!-- Save Panel -->
    <div class="save-panel">
        <?php if ($projectId): ?>
        <div class="sp-header"><i class="bi bi-folder2-open"></i> Proyek Tersimpan</div>
        <div class="sp-update-row">
            <div class="sp-proj-info">
                <span class="sp-proj-badge"><i class="bi bi-check2"></i> Tersimpan</span>
                <div>
                    <div class="sp-proj-name"><?= htmlspecialchars($projectName ?: 'Proyek Tanpa Nama') ?></div>
                    <div style="font-size:11px;color:var(--muted)">ID: <?= $projectId ?></div>
                </div>
            </div>
            <div class="sp-actions">
                <form method="POST" action="project.php" style="display:contents">
                    <input type="hidden" name="action"       value="save">
                    <input type="hidden" name="project_id"   value="<?= htmlspecialchars($projectId) ?>">
                    <input type="hidden" name="project_name" value="<?= htmlspecialchars($projectName) ?>">
                    <?php foreach (extractFmParams($_POST) as $k => $v): ?><input type="hidden" name="<?=$k?>" value="<?= htmlspecialchars($v) ?>"><?php endforeach; ?>
                    <button type="submit" class="btn-save btn-save-update"><i class="bi bi-arrow-clockwise"></i> Perbarui Proyek</button>
                </form>
                <a href="project.php" class="btn-sp-sec"><i class="bi bi-folder2-open"></i> Semua Proyek</a>
            </div>
        </div>
        <?php else: ?>
        <div class="sp-header"><i class="bi bi-floppy2"></i> Simpan sebagai Proyek Baru</div>
        <form method="POST" action="project.php">
            <input type="hidden" name="action"     value="save">
            <input type="hidden" name="project_id" value="">
            <?php foreach (extractFmParams($_POST) as $k => $v): ?><input type="hidden" name="<?=$k?>" value="<?= htmlspecialchars($v) ?>"><?php endforeach; ?>
            <div class="sp-new-row">
                <input type="text" name="project_name" class="sp-input" placeholder="Nama proyek, mis: Lapangan A – Skenario Base" value="<?= htmlspecialchars($projectName) ?>">
                <button type="submit" class="btn-save btn-save-new"><i class="bi bi-floppy2-fill"></i> Simpan</button>
                <a href="project.php" class="btn-sp-sec"><i class="bi bi-folder2-open"></i> Semua Proyek</a>
            </div>
        </form>
        <?php endif; ?>
    </div>

    <!-- Page header -->
    <div class="page-hdr">
        <h1>Hasil Analisis Keekonomian<?= $projectName ? ': '.htmlspecialchars($projectName) : '' ?></h1>
        <p>Metode Depresiasi: <strong style="color:var(--amber)"><?= $metodeLabel[$metode]??$metode ?></strong>
           &nbsp;·&nbsp; Discount Rate: <strong style="color:var(--cyan)"><?= fmt($discountRate*100,1) ?>%</strong>
           &nbsp;·&nbsp; Tax Rate: <strong style="color:var(--cyan)"><?= fmt($pajakRate*100,1) ?>%</strong>
        </p>
    </div>

    <!-- Metric cards -->
    <div class="metrics-grid">
        <div class="mcard c-amber">
            <div class="mc-icon"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="mc-label">NPV</div>
            <div class="mc-value <?= $npv>=0?'positive':'negative' ?>">$<?= fmt(abs($npv),1) ?>M</div>
            <div class="mc-unit"><?= $npv>=0?'▲ Proyek layak':'▼ NPV negatif' ?></div>
        </div>
        <div class="mcard c-cyan">
            <div class="mc-icon"><i class="bi bi-percent"></i></div>
            <div class="mc-label">IRR</div>
            <div class="mc-value <?= ($irr!==null&&$irr>=$discountRate)?'positive':'negative' ?>"><?= $irr!==null?fmt($irr*100,2).'%':'N/A' ?></div>
            <div class="mc-unit"><?php if($irr!==null) echo $irr>=$discountRate?'▲ IRR > WACC':'▼ IRR < WACC'; else echo 'Tidak dapat dihitung'; ?></div>
        </div>
        <div class="mcard c-green">
            <div class="mc-icon"><i class="bi bi-clock-history"></i></div>
            <div class="mc-label">Payback Period</div>
            <div class="mc-value"><?= $payback!==null?fmt($payback,2).' thn':'N/A' ?></div>
            <div class="mc-unit"><?= $payback!==null?'Pengembalian investasi':'Tidak tercapai' ?></div>
        </div>
        <div class="mcard c-red">
            <div class="mc-icon"><i class="bi bi-cash-coin"></i></div>
            <div class="mc-label">Total NCF (Undiscounted)</div>
            <div class="mc-value <?= $totalNCF_ND>=0?'positive':'negative' ?>">$<?= fmt(abs($totalNCF_ND),1) ?>M</div>
            <div class="mc-unit">PI = <?= fmt($pi,3) ?></div>
        </div>
    </div>

    <!-- Tabel FM -->
    <div class="tcard">
        <div class="tcard-hdr"><i class="bi bi-table"></i> Tabel Perhitungan Financial Model (FM)</div>
        <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th rowspan="2">Tahun</th>
                    <th rowspan="2">Produksi<br><small style="font-weight:400;color:var(--muted)">(Mbbl)</small></th>
                    <th rowspan="2">Income<br><small style="font-weight:400;color:var(--muted)">($M)</small></th>
                    <th colspan="2" class="th-inv">Investasi</th>
                    <th rowspan="2">Opex<br><small style="font-weight:400;color:var(--muted)">($M)</small></th>
                    <th rowspan="2">Di<br><small style="font-weight:400;color:var(--muted)">($M)</small></th>
                    <th rowspan="2">Taxable Income<br><small style="font-weight:400;color:var(--muted)">($M)</small></th>
                    <th rowspan="2">Tax<br><small style="font-weight:400;color:var(--muted)">($M)</small></th>
                    <th rowspan="2">NCF Undiscounted<br><small style="font-weight:400;color:var(--muted)">($M)</small></th>
                    <th rowspan="2">NCF Discounted<br><small style="font-weight:400;color:var(--muted)">($M)</small></th>
                    <th rowspan="2">Kumulatif NCF<br><small style="font-weight:400;color:var(--muted)">($M)</small></th>
                </tr>
                <tr>
                    <th class="th-inv">Capital ($M)</th>
                    <th class="th-inv">Non-Capital ($M)</th>
                </tr>
            </thead>
            <tbody>
                <tr class="row-zero">
                    <td>0</td><td class="dim">—</td><td class="dim">—</td>
                    <td class="td-inv"><?= fmt($capital,1) ?></td>
                    <td class="td-inv-r"><?= fmt($nonCapital,1) ?></td>
                    <td class="dim">—</td><td class="dim">—</td><td class="dim">—</td><td class="dim">—</td>
                    <td class="neg">(<?= fmt($totalInvestasi,1) ?>)</td>
                    <td class="neg">(<?= fmt($totalInvestasi,1) ?>)</td>
                    <td class="neg">(<?= fmt($totalInvestasi,1) ?>)</td>
                </tr>
                <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= $r['t'] ?></td>
                    <td><?= fmt($r['prod'],0) ?></td>
                    <td><?= fmt($r['income'],1) ?></td>
                    <td class="td-inv dim">0</td><td class="td-inv-r dim">0</td>
                    <td><?= fmt($r['opex'],1) ?></td>
                    <td><?= fmt($r['di'],1) ?></td>
                    <td class="<?= $r['taxable']<0?'neg':'' ?>"><?= fmt($r['taxable'],1) ?></td>
                    <td><?= fmt($r['tax'],1) ?></td>
                    <td class="<?= $r['ncf_nd']<0?'neg':'pos' ?>"><?= fmt($r['ncf_nd'],1) ?></td>
                    <td class="<?= $r['ncf_d']<0?'neg':'' ?>"><?= fmt($r['ncf_d'],1) ?></td>
                    <td class="<?= $r['cum_ncf']<0?'neg':'pos' ?>"><?= fmt($r['cum_ncf'],1) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="row-total">
                    <td colspan="9" style="text-align:right;letter-spacing:.5px">TOTAL</td>
                    <td><?= fmt($totalNCF_ND,1) ?></td>
                    <td><?= fmt($totalNCF_D,1) ?></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
        </div>
    </div>

    <!-- Formula notes -->
    <div class="formula-card">
        <div class="formula-title"><i class="bi bi-info-circle"></i> Catatan Formula &amp; Definisi</div>
        <div class="formula-grid">
            <div>
                <span>Income</span> = Produksi × Harga Minyak<br>
                <span>Taxable Income</span> = Income − Opex − Di<br>
                <span>Tax</span> = Taxable × <?= fmt($pajakRate*100,1) ?>% (jika &gt; 0)<br>
                <span>NCF Undiscounted</span> = Taxable − Tax
            </div>
            <div>
                <span class="sc">NCF Discounted</span> = NCF / (1+r)ᵗ<br>
                <span class="sc">r</span> = Discount Rate <?= fmt($discountRate*100,1) ?>%<br>
                <span class="sc">NPV</span> = Σ NCF_Disc (t=0 s.d. <?= $jangkaWaktu ?>)<br>
                <span class="sc">PI</span> = (NPV + Inv) / Inv
            </div>
            <div>
                <span class="sg">Produksi (build-up)</span>: P₁×(1+g)^(t−1)<br>
                <span class="sg">Produksi (decline)</span>: P_max×(1−d)^(t−t_peak)<br>
                <span class="sg">Opex Eskalasi</span>: mulai thn <?= $tahunMulaiEskalasi ?> @<?= fmt($opexEskalasi*100,1) ?>%<br>
                <span class="sg">IRR</span>: Newton-Raphson iteration
            </div>
        </div>
    </div>

</div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const sidebar = document.getElementById('sidebar');
    const main    = document.getElementById('mainContent');
    const overlay = document.getElementById('sidebarOverlay');
    const toggle  = document.getElementById('sidebarToggle');

    function closeSidebar(){ sidebar.classList.add('collapsed'); main.classList.add('expanded'); overlay.classList.remove('show'); }
    function openSidebar() { sidebar.classList.remove('collapsed'); main.classList.remove('expanded'); overlay.classList.add('show'); }

    toggle.addEventListener('click', () => sidebar.classList.contains('collapsed') ? openSidebar() : closeSidebar());
    overlay.addEventListener('click', closeSidebar);
</script>
</body>
</html>