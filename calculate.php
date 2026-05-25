<?php
require_once __DIR__ . '/storage.php';
$page = 'calculate';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }

/* ── Extract inputs ──────────────────────────────── */
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
$projectName        = strip_tags(trim($_POST['project_name'] ?? 'Proyek Tanpa Nama'));

$metodeLabel = ['straight_line'=>'Garis Lurus (SL)','declining_balance'=>'Saldo Menurun (DB)','sum_years_digits'=>'Jumlah Angka Tahun (SYD)'];

/* ── Helpers ─────────────────────────────────────── */
function fmt(float $v, int $d = 1): string { return number_format($v, $d, '.', ','); }

function hitungIRR(array $cf): ?float {
    $r = 0.1;
    for ($i = 0; $i < 2000; $i++) {
        $n = $dn = 0;
        foreach ($cf as $t => $c) { $d = pow(1+$r,$t); $n += $c/$d; if($t>0)$dn -= $t*$c/($d*(1+$r)); }
        if (abs($dn) < 1e-14) break;
        $nr = $r - $n/$dn;
        if ($nr < -0.999) $nr = -0.5;
        if (abs($nr-$r) < 1e-9) { $r = $nr; break; }
        $r = $nr;
    }
    return (is_nan($r)||is_infinite($r)) ? null : $r;
}
function hitungPayback(array $cf): ?float {
    $cum = 0;
    foreach ($cf as $t => $c) { $prev=$cum; $cum+=$c; if($t>0&&$cum>=0&&$prev<0) return $t-1+abs($prev)/abs($cum-$prev); }
    return null;
}

/* ── Calculate ───────────────────────────────────── */
$prodPeak  = $prodAwal * pow(1 + $lajuKenaikan, max(0, $tahunPuncak - 1));
$rows = []; $cashflows = [-$totalInvestasi];
$npv=$totalNCF_ND=$totalNCF_D=0;
$npv = -$totalInvestasi;
$cumNCF = -$totalInvestasi; $nilaiBuku = $totalInvestasi;
$sumYears = ($jangkaWaktu * ($jangkaWaktu + 1)) / 2;

for ($t = 1; $t <= $jangkaWaktu; $t++) {
    $prod   = ($t<=$tahunPuncak) ? $prodAwal*pow(1+$lajuKenaikan,$t-1) : $prodPeak*pow(1-$declineRate,$t-$tahunPuncak);
    $income = $prod * $hargaMinyak;
    $opex   = ($t < $tahunMulaiEskalasi) ? $opexBase : $opexBase*pow(1+$opexEskalasi,$t-$tahunMulaiEskalasi+1);
    switch ($metode) {
        case 'declining_balance': $di=$nilaiBuku*(2/$jangkaWaktu); $nilaiBuku-=$di; break;
        case 'sum_years_digits':  $di=(($jangkaWaktu-$t+1)/$sumYears)*$totalInvestasi; break;
        default: $di = $totalInvestasi / $jangkaWaktu;
    }
    $taxable=$income-$opex-$di; $tax=($taxable>0)?$taxable*$pajakRate:0;
    $ncf_nd=$taxable-$tax; $ncf_d=$ncf_nd/pow(1+$discountRate,$t);
    $cumNCF+=$ncf_nd; $totalNCF_ND+=$ncf_nd; $totalNCF_D+=$ncf_d; $npv+=$ncf_d; $cashflows[]=$ncf_nd;
    $rows[] = ['t'=>$t,'prod'=>$prod,'income'=>$income,'opex'=>$opex,'di'=>$di,
               'taxable'=>$taxable,'tax'=>$tax,'ncf_nd'=>$ncf_nd,'ncf_d'=>$ncf_d,'cum_ncf'=>$cumNCF];
}
$irr     = hitungIRR($cashflows);
$payback = hitungPayback($cashflows);
$pi      = $totalInvestasi > 0 ? ($npv + $totalInvestasi) / $totalInvestasi : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil FM<?= $projectName ? ' – '.htmlspecialchars($projectName) : '' ?> – EkoMigas Pro</title>
    <link rel="stylesheet" href="style.css">
<style>
/* ── Save panel ──────────────────────── */
.save-panel {
    background: var(--card); border: 1px solid var(--border); border-radius: var(--radius);
    padding: 1.1rem 1.4rem; margin-bottom: 1.75rem; position: relative;
}
.save-panel::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 1px;
    background: linear-gradient(90deg, var(--success) 0%, var(--accent) 50%, transparent 100%);
}
.sp-row { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
.sp-info { display: flex; align-items: center; gap: 10px; }
.sp-badge {
    background: var(--success-bg); border: 1px solid var(--success-bdr);
    color: var(--success); font-size: 10px; font-weight: 700;
    padding: 3px 9px; border-radius: 20px; font-family: 'DM Mono', monospace;
    text-transform: uppercase; letter-spacing: .5px; white-space: nowrap;
}
.sp-name { font-size: 15px; font-weight: 500; color: var(--text); }
.sp-sub  { font-size: 11px; color: var(--muted); margin-top: .1rem; font-family: 'DM Mono', monospace; }
.sp-actions { display: flex; gap: .55rem; flex-wrap: wrap; }

.sp-new-row { display: flex; align-items: center; gap: .65rem; }
.sp-input {
    flex: 1; min-width: 240px; background: var(--in-bg); border: 1px solid var(--in-bdr);
    color: var(--text); border-radius: var(--radius-sm); padding: 9px 13px;
    font-family: 'DM Sans', sans-serif; font-size: 13.5px; font-weight: 300;
    transition: .18s all;
}
.sp-input:focus { outline: none; border-color: var(--success); box-shadow: 0 0 0 3px rgba(110,155,122,.1); }
.sp-input::placeholder { color: var(--muted); }

/* ── Summary params ──────────────────── */
.summary-strip {
    display: flex; gap: .6rem; flex-wrap: wrap; margin-bottom: 1.75rem;
}
.sum-item {
    background: var(--card); border: 1px solid var(--border); border-radius: 8px;
    padding: 6px 12px; display: flex; flex-direction: column; gap: 1px;
}
.sum-k { font-size: 9.5px; text-transform: uppercase; letter-spacing: .7px; color: var(--muted); font-weight: 600; }
.sum-v { font-family: 'DM Mono', monospace; font-size: 12.5px; color: var(--text2); }

/* ── Metrics grid ────────────────────── */
.metrics-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 1rem; margin-bottom: 1.75rem; }
.mcard {
    background: var(--card); border: 1px solid var(--border); border-radius: var(--radius);
    padding: 1.2rem 1.3rem; position: relative; overflow: hidden; transition: .18s transform;
}
.mcard:hover { transform: translateY(-2px); }
.mcard::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 1px; }
.mc-a::before { background: var(--accent);  }
.mc-b::before { background: #7A9BAE; }
.mc-c::before { background: var(--success); }
.mc-d::before { background: var(--danger);  }

.mc-icon { width: 32px; height: 32px; border-radius: 7px; display: flex; align-items: center; justify-content: center; font-size: 15px; margin-bottom: .75rem; }
.mc-a .mc-icon { background: var(--accent-bg);           color: var(--accent);  }
.mc-b .mc-icon { background: rgba(122,155,174,.1);        color: #7A9BAE; }
.mc-c .mc-icon { background: var(--success-bg);           color: var(--success); }
.mc-d .mc-icon { background: var(--danger-bg);            color: var(--danger);  }

.mc-label { font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: .8px; color: var(--muted); margin-bottom: .3rem; }
.mc-value { font-family: 'DM Mono', monospace; font-size: 22px; font-weight: 300; color: var(--text); line-height: 1; }
.mc-note  { font-size: 11.5px; color: var(--dim); margin-top: .4rem; }

/* ── Table ───────────────────────────── */
.tcard { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; margin-bottom: 1.5rem; }
.tcard-hdr {
    padding: 1rem 1.5rem; border-bottom: 1px solid var(--border);
    display: flex; align-items: center; gap: 8px;
    font-size: 11.5px; font-weight: 600; text-transform: uppercase;
    letter-spacing: 1px; color: var(--dim);
}
.tcard-hdr i { color: var(--accent); font-size: 14px; }
.table-wrap { overflow-x: auto; }

table { width: 100%; border-collapse: collapse; font-size: 12px; }
thead th {
    background: var(--in-bg); padding: 9px 10px; text-align: center;
    font-family: 'DM Sans', sans-serif; font-weight: 600; font-size: 10.5px;
    text-transform: uppercase; letter-spacing: .6px; color: var(--dim);
    border-bottom: 1px solid var(--border); white-space: nowrap;
}
thead tr:nth-child(2) th { background: #0E0E0E; font-size: 10px; color: var(--muted); }
.th-inv { border-left: 1px solid rgba(147,138,135,.18); border-right: 1px solid rgba(147,138,135,.18); color: var(--accent) !important; background: rgba(147,138,135,.04) !important; }

tbody tr { border-bottom: 1px solid rgba(39,39,39,.7); transition: .12s background; }
tbody tr:hover { background: rgba(147,138,135,.03); }
tbody tr:nth-child(even) { background: rgba(12,12,12,.5); }
tbody tr:nth-child(even):hover { background: rgba(147,138,135,.035); }

tbody td { padding: 8px 10px; text-align: right; font-family: 'DM Mono', monospace; font-size: 11.5px; color: var(--text2); white-space: nowrap; }
tbody td:first-child { text-align: center; font-weight: 500; color: var(--accent); background: rgba(12,12,12,.4); }

.td-inv   { border-left:  1px solid rgba(147,138,135,.1); }
.td-inv-r { border-right: 1px solid rgba(147,138,135,.1); }

.row-zero td { color: var(--accent) !important; background: rgba(147,138,135,.04) !important; }
.row-total td { font-weight: 500; color: var(--accent) !important; background: rgba(147,138,135,.06) !important; border-top: 1px solid rgba(147,138,135,.2) !important; font-size: 12px; }

.tdneg { color: var(--danger)  !important; }
.tdpos { color: var(--success) !important; }
.tddim { color: var(--muted)   !important; }

/* ── Formula notes ───────────────────── */
.formula-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.2rem 1.5rem; }
.formula-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-top: .75rem; }
.fg-col h4 { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--accent-dim); margin-bottom: .55rem; padding-bottom: .4rem; border-bottom: 1px solid var(--border); }
.fg-col p  { font-family: 'DM Mono', monospace; font-size: 11.5px; color: var(--muted); line-height: 1.95; }
.fg-col p span { color: var(--text2); }
.fg-col p .sc  { color: #7A9BAE; }
.fg-col p .sg  { color: var(--success); }
</style>
</head>
<body>

<!-- ═══ NAVBAR ══════════════════════════════════════ -->
<nav class="nav" id="topNav">
    <a class="nav-brand" href="home.php">
        <div class="nav-logo">FM</div>
        <div>
            <div class="nav-wordmark">EkoMigas Pro</div>
            <div class="nav-sub">Petroleum Economics</div>
        </div>
    </a>
    <div class="nav-links">
        <a href="home.php"    class="nav-a">Beranda</a>
        <a href="project.php" class="nav-a">Proyek</a>
    </div>
    <div class="nav-right">
        <a href="<?= $projectId ? 'index.php?load='.urlencode($projectId) : 'index.php' ?>" class="btn btn-ghost btn-sm">
            <i class="bi bi-arrow-left"></i> Input Ulang
        </a>
        <a href="javascript:window.print()" class="btn btn-ghost btn-sm">
            <i class="bi bi-printer"></i>
        </a>
    </div>
</nav>

<!-- ═══ PAGE ════════════════════════════════════════ -->
<div class="page-wrap">
<div class="page-body">
<div class="container-lg" style="padding:0 2.5rem">

    <!-- Save panel -->
    <div class="save-panel">
        <?php if ($projectId): ?>
        <div class="sp-row">
            <div class="sp-info">
                <span class="sp-badge"><i class="bi bi-check2"></i> Tersimpan</span>
                <div>
                    <div class="sp-name"><?= htmlspecialchars($projectName) ?></div>
                    <div class="sp-sub"><?= $projectId ?></div>
                </div>
            </div>
            <div class="sp-actions">
                <form method="POST" action="project.php" style="display:contents">
                    <input type="hidden" name="action"       value="save">
                    <input type="hidden" name="project_id"   value="<?= htmlspecialchars($projectId) ?>">
                    <input type="hidden" name="project_name" value="<?= htmlspecialchars($projectName) ?>">
                    <?php foreach (extractFmParams($_POST) as $k => $v): ?>
                    <input type="hidden" name="<?= $k ?>" value="<?= htmlspecialchars($v) ?>">
                    <?php endforeach; ?>
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="bi bi-arrow-clockwise"></i> Perbarui Proyek
                    </button>
                </form>
                <a href="project.php" class="btn btn-ghost btn-sm"><i class="bi bi-folder2-open"></i> Semua Proyek</a>
            </div>
        </div>
        <?php else: ?>
        <div style="font-size:11.5px;font-weight:600;text-transform:uppercase;letter-spacing:1px;color:var(--success);display:flex;align-items:center;gap:7px;margin-bottom:.9rem">
            <i class="bi bi-floppy2"></i> Simpan sebagai Proyek Baru
        </div>
        <form method="POST" action="project.php">
            <input type="hidden" name="action"     value="save">
            <input type="hidden" name="project_id" value="">
            <?php foreach (extractFmParams($_POST) as $k => $v): ?>
            <input type="hidden" name="<?= $k ?>" value="<?= htmlspecialchars($v) ?>">
            <?php endforeach; ?>
            <div class="sp-new-row">
                <input type="text" name="project_name" class="sp-input"
                       placeholder="Nama proyek, mis: Lapangan A – Skenario Base"
                       value="<?= htmlspecialchars($projectName !== 'Proyek Tanpa Nama' ? $projectName : '') ?>">
                <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-floppy2-fill"></i> Simpan</button>
                <a href="project.php" class="btn btn-ghost btn-sm"><i class="bi bi-folder2-open"></i> Semua Proyek</a>
            </div>
        </form>
        <?php endif; ?>
    </div>

    <!-- Page header -->
    <div class="page-hdr" style="margin-bottom:1.4rem">
        <h1>Hasil Analisis<?= $projectName && $projectName !== 'Proyek Tanpa Nama' ? ': <strong>'.htmlspecialchars($projectName).'</strong>' : ' <strong>Keekonomian</strong>' ?></h1>
        <p>
            Metode: <span style="color:var(--accent)"><?= $metodeLabel[$metode] ?? $metode ?></span>
            &nbsp;·&nbsp; Discount Rate: <span style="color:var(--text2)"><?= fmt($discountRate*100,1) ?>%</span>
            &nbsp;·&nbsp; Tax Rate: <span style="color:var(--text2)"><?= fmt($pajakRate*100,1) ?>%</span>
        </p>
    </div>

    <!-- Summary strip -->
    <div class="summary-strip">
        <div class="sum-item"><div class="sum-k">Jangka Waktu</div><div class="sum-v"><?= $jangkaWaktu ?> thn</div></div>
        <div class="sum-item"><div class="sum-k">Total Investasi</div><div class="sum-v">$<?= fmt($totalInvestasi,1) ?>M</div></div>
        <div class="sum-item"><div class="sum-k">Harga Minyak</div><div class="sum-v">$<?= fmt($hargaMinyak,2) ?>/bbl</div></div>
        <div class="sum-item"><div class="sum-k">Prod. Thn-1</div><div class="sum-v"><?= fmt($prodAwal,0) ?> Mbbl</div></div>
        <div class="sum-item"><div class="sum-k">Decline Rate</div><div class="sum-v"><?= fmt($declineRate*100,1) ?>%/thn</div></div>
        <div class="sum-item"><div class="sum-k">Base Opex</div><div class="sum-v">$<?= fmt($opexBase,1) ?>M</div></div>
    </div>

    <!-- Metric cards -->
    <div class="metrics-grid">
        <div class="mcard mc-a">
            <div class="mc-icon"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="mc-label">NPV</div>
            <div class="mc-value <?= $npv>=0?'positive':'negative' ?>">$<?= fmt(abs($npv),1) ?>M</div>
            <div class="mc-note"><?= $npv>=0?'▲ Proyek layak':'▼ NPV negatif' ?></div>
        </div>
        <div class="mcard mc-b">
            <div class="mc-icon"><i class="bi bi-percent"></i></div>
            <div class="mc-label">IRR</div>
            <div class="mc-value <?= ($irr!==null&&$irr>=$discountRate)?'positive':'negative' ?>"><?= $irr!==null?fmt($irr*100,2).'%':'N/A' ?></div>
            <div class="mc-note"><?= $irr!==null?($irr>=$discountRate?'▲ IRR > WACC':'▼ IRR < WACC'):'Tidak dapat dihitung' ?></div>
        </div>
        <div class="mcard mc-c">
            <div class="mc-icon"><i class="bi bi-clock-history"></i></div>
            <div class="mc-label">Payback Period</div>
            <div class="mc-value"><?= $payback!==null?fmt($payback,2).' thn':'N/A' ?></div>
            <div class="mc-note"><?= $payback!==null?'Pengembalian investasi':'Tidak tercapai' ?></div>
        </div>
        <div class="mcard mc-d">
            <div class="mc-icon"><i class="bi bi-cash-coin"></i></div>
            <div class="mc-label">Total NCF Undiscounted</div>
            <div class="mc-value <?= $totalNCF_ND>=0?'positive':'negative' ?>">$<?= fmt(abs($totalNCF_ND),1) ?>M</div>
            <div class="mc-note">PI = <?= fmt($pi,3) ?></div>
        </div>
    </div>

    <!-- FM Table -->
    <div class="tcard">
        <div class="tcard-hdr"><i class="bi bi-table"></i> Tabel Perhitungan Financial Model (FM)</div>
        <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th rowspan="2">Tahun</th>
                    <th rowspan="2">Produksi<br><small style="font-weight:400;opacity:.6">(Mbbl)</small></th>
                    <th rowspan="2">Income<br><small style="font-weight:400;opacity:.6">($M)</small></th>
                    <th colspan="2" class="th-inv">Investasi</th>
                    <th rowspan="2">Opex<br><small style="font-weight:400;opacity:.6">($M)</small></th>
                    <th rowspan="2">Di<br><small style="font-weight:400;opacity:.6">($M)</small></th>
                    <th rowspan="2">Taxable Income<br><small style="font-weight:400;opacity:.6">($M)</small></th>
                    <th rowspan="2">Tax<br><small style="font-weight:400;opacity:.6">($M)</small></th>
                    <th rowspan="2">NCF Undiscounted<br><small style="font-weight:400;opacity:.6">($M)</small></th>
                    <th rowspan="2">NCF Discounted<br><small style="font-weight:400;opacity:.6">($M)</small></th>
                    <th rowspan="2">Kumulatif NCF<br><small style="font-weight:400;opacity:.6">($M)</small></th>
                </tr>
                <tr>
                    <th class="th-inv">Capital ($M)</th>
                    <th class="th-inv">Non-Capital ($M)</th>
                </tr>
            </thead>
            <tbody>
                <tr class="row-zero">
                    <td>0</td><td class="tddim">—</td><td class="tddim">—</td>
                    <td class="td-inv"><?= fmt($capital,1) ?></td>
                    <td class="td-inv-r"><?= fmt($nonCapital,1) ?></td>
                    <td class="tddim">—</td><td class="tddim">—</td><td class="tddim">—</td><td class="tddim">—</td>
                    <td class="tdneg">(<?= fmt($totalInvestasi,1) ?>)</td>
                    <td class="tdneg">(<?= fmt($totalInvestasi,1) ?>)</td>
                    <td class="tdneg">(<?= fmt($totalInvestasi,1) ?>)</td>
                </tr>
                <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= $r['t'] ?></td>
                    <td><?= fmt($r['prod'],0) ?></td>
                    <td><?= fmt($r['income'],1) ?></td>
                    <td class="td-inv tddim">0</td><td class="td-inv-r tddim">0</td>
                    <td><?= fmt($r['opex'],1) ?></td>
                    <td><?= fmt($r['di'],1) ?></td>
                    <td class="<?= $r['taxable']<0?'tdneg':'' ?>"><?= fmt($r['taxable'],1) ?></td>
                    <td><?= fmt($r['tax'],1) ?></td>
                    <td class="<?= $r['ncf_nd']<0?'tdneg':'tdpos' ?>"><?= fmt($r['ncf_nd'],1) ?></td>
                    <td class="<?= $r['ncf_d']<0?'tdneg':'' ?>"><?= fmt($r['ncf_d'],1) ?></td>
                    <td class="<?= $r['cum_ncf']<0?'tdneg':'tdpos' ?>"><?= fmt($r['cum_ncf'],1) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="row-total">
                    <td colspan="9" style="text-align:right;letter-spacing:.3px;font-size:11px">TOTAL</td>
                    <td><?= fmt($totalNCF_ND,1) ?></td>
                    <td><?= fmt($totalNCF_D,1)  ?></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
        </div>
    </div>

    <!-- Formula notes -->
    <div class="formula-card">
        <div class="sec-eyebrow">Catatan Formula &amp; Definisi</div>
        <div class="formula-grid">
            <div class="fg-col">
                <h4>Alur Perhitungan</h4>
                <p><span>Income</span> = Produksi × Harga Minyak<br><span>Taxable Income</span> = Income − Opex − Di<br><span>Tax</span> = Taxable × <?= fmt($pajakRate*100,1) ?>% (jika &gt; 0)<br><span>NCF Undiscounted</span> = Taxable − Tax</p>
            </div>
            <div class="fg-col">
                <h4>Metrik Investasi</h4>
                <p><span class="sc">NCF Discounted</span> = NCF / (1+r)ᵗ<br><span class="sc">r</span> = <?= fmt($discountRate*100,1) ?>% (Discount Rate)<br><span class="sc">NPV</span> = Σ NCF_Disc (t=0..<?= $jangkaWaktu ?>)<br><span class="sc">PI</span> = (NPV + Investasi) / Investasi<br><span class="sc">IRR</span> = Newton-Raphson iteration</p>
            </div>
            <div class="fg-col">
                <h4>Profil Produksi &amp; Opex</h4>
                <p><span class="sg">Build-up</span>: P₁×(1+g)^(t−1), t ≤ <?= $tahunPuncak ?><br><span class="sg">Decline</span>: Pmax×(1−d)^(t−<?= $tahunPuncak ?>)<br><span class="sg">Opex eskalasi</span> mulai thn <?= $tahunMulaiEskalasi ?><br>&nbsp;&nbsp;@<?= fmt($opexEskalasi*100,1) ?>%/thn dari $<?= fmt($opexBase,1) ?>M</p>
            </div>
        </div>
    </div>

</div>
</div>
</div>

<script>
const nav = document.getElementById('topNav');
window.addEventListener('scroll', () => {
    nav.style.background = window.scrollY > 50
        ? 'rgba(12,12,12,.97)' : 'rgba(12,12,12,.82)';
}, { passive: true });
</script>
</body>
</html>