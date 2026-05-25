<?php
require_once __DIR__ . '/storage.php';
$projects       = listProjects();
$recentProjects = array_slice($projects, 0, 3);
$totalProjects  = count($projects);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EkoMigas Pro – Platform Keekonomian Lapangan Migas</title>
<link rel="stylesheet" href="style.css">
<style>
/* ═══ DOT GRID HERO BG ══════════════════════════════════════ */
body::before {
    background-image: radial-gradient(circle, rgba(255,255,255,.045) 1px, transparent 1px) !important;
    background-size: 26px 26px !important;
}

/* ═══ HERO ══════════════════════════════════════════════════ */
.hero {
    min-height: 100vh; display: flex; flex-direction: column; align-items: center;
    justify-content: center; position: relative; overflow: hidden;
    padding: 62px 2rem 7rem; text-align: center;
}
.hero-glow {
    position: absolute; width: 800px; height: 800px; border-radius: 50%;
    background: radial-gradient(circle, rgba(147,138,135,.045) 0%, transparent 65%);
    left: 50%; top: 45%; transform: translate(-50%,-50%); pointer-events: none;
}
.hero-wm {
    position: absolute; font-size: 38vw; font-weight: 800; color: rgba(255,255,255,.011);
    right: -4vw; bottom: -8vw; line-height: 1; letter-spacing: -.06em;
    pointer-events: none; user-select: none; font-family: 'DM Sans', sans-serif;
}
.hero-fade {
    position: absolute; top: 0; left: 0; right: 0; height: 180px;
    background: linear-gradient(to bottom, var(--bg), transparent);
    pointer-events: none; z-index: 1;
}
.hero-inner { position: relative; z-index: 2; max-width: 800px; }

/* Badge pill */
.hero-badge {
    display: inline-flex; align-items: center; gap: 8px;
    background: var(--accent-bg); border: 1px solid var(--accent-bdr);
    color: var(--accent); font-size: 11.5px; font-weight: 500;
    padding: 6px 14px; border-radius: 20px; margin-bottom: 2rem;
    font-family: 'DM Mono', monospace; letter-spacing: .06em;
}
.badge-dot {
    width: 6px; height: 6px; border-radius: 50%; background: var(--accent);
    animation: pls 1.6s ease infinite;
}
@keyframes pls { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.4;transform:scale(.75)} }

/* Headline */
.hero-h1 {
    font-size: clamp(46px, 8.5vw, 92px); font-weight: 800; line-height: 1.03;
    letter-spacing: -.045em; margin-bottom: 1.6rem;
}
.hero-h1 .ln1 { color: var(--text); display: block; }
.hero-h1 .ln2 { color: var(--accent); display: block; }
.hero-h1 .ln3 {
    color: var(--dim); font-weight: 400; font-size: .58em;
    letter-spacing: -.02em; display: block; margin-top: .15em;
}

.hero-p {
    font-size: clamp(14.5px, 1.8vw, 17px); color: var(--dim); line-height: 1.72;
    font-weight: 400; margin-bottom: 2.5rem; max-width: 520px;
    margin-left: auto; margin-right: auto;
}

/* CTAs */
.hero-ctas {
    display: flex; align-items: center; justify-content: center;
    gap: .9rem; margin-bottom: 2.8rem; flex-wrap: wrap;
}
.btn-primary-hero {
    padding: 14px 28px; border-radius: 10px; background: var(--accent); color: var(--bg);
    font-size: 15px; font-weight: 600; text-decoration: none;
    display: flex; align-items: center; gap: 8px; transition: .18s all; letter-spacing: -.01em;
}
.btn-primary-hero:hover {
    background: var(--accent-h); color: var(--bg);
    transform: translateY(-2px); box-shadow: 0 14px 34px rgba(147,138,135,.2);
}
.btn-secondary-hero {
    padding: 14px 28px; border-radius: 10px; background: transparent;
    border: 1px solid var(--border); color: var(--text2);
    font-size: 15px; font-weight: 500; text-decoration: none;
    display: flex; align-items: center; gap: 8px; transition: .18s all;
}
.btn-secondary-hero:hover { border-color: var(--accent-bdr); color: var(--accent); background: var(--accent-bg); }

/* Chips */
.hero-chips { display: flex; align-items: center; justify-content: center; gap: .75rem; flex-wrap: wrap; }
.chip {
    display: flex; align-items: center; gap: 7px; background: var(--card);
    border: 1px solid var(--border); border-radius: 8px;
    padding: 7px 13px; font-family: 'DM Mono', monospace; font-size: 12px; color: var(--dim);
}
.chip i { font-size: 12px; color: var(--accent); }

/* Scroll indicator */
.scroll-ind {
    position: absolute; bottom: 2.5rem; left: 50%; transform: translateX(-50%);
    z-index: 2; display: flex; flex-direction: column; align-items: center; gap: 6px;
}
.si-label { font-size: 10px; color: var(--muted); text-transform: uppercase; letter-spacing: .14em; font-family: 'DM Mono', monospace; }
.si-line {
    width: 1px; height: 38px;
    background: linear-gradient(to bottom, var(--accent), transparent);
    animation: sdrop 1.4s ease infinite;
}
@keyframes sdrop { 0%,100%{opacity:1;transform:translateY(0)} 55%{opacity:.2;transform:translateY(8px)} }

/* ═══ STATS BAR ═════════════════════════════════════════════ */
.stats-bar {
    border-top: 1px solid var(--border); border-bottom: 1px solid var(--border);
    background: var(--bg2); position: relative; z-index: 2;
}
.stats-inner {
    max-width: 1120px; margin: 0 auto;
    display: grid; grid-template-columns: repeat(4,1fr);
}
.stat {
    padding: 2.2rem 2.5rem; border-right: 1px solid var(--border);
    transition: .18s background; cursor: default;
}
.stat:last-child { border-right: none; }
.stat:hover { background: rgba(255,255,255,.018); }
.stat-n {
    font-family: 'DM Mono', monospace; font-size: 40px; font-weight: 500;
    color: var(--text); line-height: 1; margin-bottom: .45rem; letter-spacing: -.03em;
}
.stat-n .a { color: var(--accent); }
.stat-l { font-size: 13px; color: var(--muted); }

/* ═══ SECTIONS ══════════════════════════════════════════════ */
.sec { padding: 5.5rem 2rem; position: relative; z-index: 2; }
.sec-inner { max-width: 1120px; margin: 0 auto; }
.sec-alt { background: var(--bg2); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); }
.sec-label {
    font-family: 'DM Mono', monospace; font-size: 10.5px; font-weight: 500;
    text-transform: uppercase; letter-spacing: .14em; color: var(--accent);
    margin-bottom: .85rem; display: flex; align-items: center; gap: 8px;
}
.sec-label::before { content: ''; display: block; width: 22px; height: 1px; background: var(--accent); }
.sec-title {
    font-size: clamp(26px, 4vw, 38px); font-weight: 700; letter-spacing: -.035em;
    color: var(--text); margin-bottom: .75rem; line-height: 1.1;
}
.sec-desc { font-size: 14.5px; color: var(--dim); line-height: 1.65; }
.sec-hdr {
    display: flex; align-items: flex-end; justify-content: space-between;
    margin-bottom: 2.2rem; gap: 1rem;
}
.btn-all {
    font-size: 13px; color: var(--accent); text-decoration: none; font-weight: 500;
    display: flex; align-items: center; gap: 5px; transition: .15s all; white-space: nowrap;
    border-bottom: 1px solid transparent; padding-bottom: 1px;
}
.btn-all:hover { color: var(--accent-h); border-bottom-color: var(--accent); }

/* ═══ PROJECT CARDS ═════════════════════════════════════════ */
.proj-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(310px, 1fr)); gap: 1.1rem; }
.pc {
    background: var(--card); border: 1px solid var(--border); border-radius: 14px;
    overflow: hidden; transition: .2s transform, .2s border-color; position: relative;
}
.pc::after {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 1px;
    background: linear-gradient(90deg, var(--accent) 0%, transparent 60%);
    opacity: 0; transition: .2s opacity;
}
.pc:hover { transform: translateY(-3px); border-color: var(--accent-bdr); }
.pc:hover::after { opacity: 1; }
.pc-body { padding: 1.25rem 1.3rem .95rem; }
.pc-id { font-family: 'DM Mono', monospace; font-size: 10px; color: var(--muted); margin-bottom: .35rem; }
.pc-name {
    font-size: 15.5px; font-weight: 600; color: var(--text); margin-bottom: .85rem;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis; letter-spacing: -.015em;
}
.pc-params { display: grid; grid-template-columns: 1fr 1fr; gap: 5px; margin-bottom: .8rem; }
.pp { background: rgba(255,255,255,.025); border: 1px solid var(--border); border-radius: 7px; padding: 5px 9px; }
.pp-k { font-size: 9.5px; color: var(--muted); text-transform: uppercase; letter-spacing: .05em; }
.pp-v { font-family: 'DM Mono', monospace; font-size: 12px; color: var(--text2); margin-top: 1px; }
.pc-date { font-size: 11px; color: var(--muted); display: flex; align-items: center; gap: 5px; }
.pc-actions { border-top: 1px solid var(--border); padding: .85rem 1.3rem; display: flex; gap: .5rem; }
.btn-pc {
    flex: 1; padding: 8px 10px; border-radius: 8px; font-size: 13px; font-weight: 600;
    text-decoration: none; cursor: pointer; display: flex; align-items: center;
    justify-content: center; gap: 5px; transition: .16s all; border: 1px solid transparent;
}
.btn-pc-e { background: var(--accent-bg); border-color: var(--accent-bdr); color: var(--accent); }
.btn-pc-e:hover { background: var(--accent); color: var(--bg); }
.btn-pc-c { background: rgba(255,255,255,.04); border-color: var(--border); color: var(--text2); }
.btn-pc-c:hover { background: rgba(255,255,255,.08); color: var(--text); }

/* New project card (dashed) */
.pc-new {
    min-height: 195px; display: flex; align-items: center; justify-content: center;
    border-style: dashed !important; background: transparent !important; cursor: pointer; text-decoration: none;
}
.pc-new:hover { border-color: var(--accent-bdr) !important; background: var(--accent-bg) !important; }
.pc-new-inner { text-align: center; color: var(--muted); }
.pc-new-inner i { font-size: 28px; display: block; margin-bottom: .6rem; color: var(--accent); transition: .16s; }
.pc-new:hover .pc-new-inner i { transform: scale(1.1); }
.pc-new-title { font-size: 14px; font-weight: 600; color: var(--text2); }
.pc-new-sub { font-size: 12px; margin-top: .25rem; color: var(--muted); }

/* Empty state */
.empty {
    background: var(--card); border: 1px dashed var(--border); border-radius: 16px;
    padding: 4.5rem 2rem; text-align: center;
}
.empty i { font-size: 44px; color: var(--muted); margin-bottom: 1rem; display: block; }
.empty-t { font-size: 18px; font-weight: 600; color: var(--text2); margin-bottom: .5rem; letter-spacing: -.01em; }
.empty-p { font-size: 13.5px; color: var(--muted); margin-bottom: 1.5rem; }
.btn-start {
    display: inline-flex; align-items: center; gap: 7px;
    background: var(--accent); color: var(--bg);
    padding: 11px 24px; border-radius: 9px; font-size: 14px; font-weight: 600;
    text-decoration: none; transition: .16s all;
}
.btn-start:hover { background: var(--accent-h); color: var(--bg); transform: translateY(-1px); }

/* ═══ FEATURES ══════════════════════════════════════════════ */
.feat-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 1.1rem; margin-top: 2.75rem; }
.feat {
    background: var(--card); border: 1px solid var(--border); border-radius: 14px;
    padding: 1.5rem 1.6rem; transition: .2s all; position: relative; overflow: hidden;
}
.feat::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 1px;
    background: linear-gradient(90deg, var(--accent) 0%, transparent 55%);
    opacity: 0; transition: .2s opacity;
}
.feat:hover { border-color: var(--accent-bdr); transform: translateY(-2px); }
.feat:hover::before { opacity: 1; }
.feat-ico {
    width: 40px; height: 40px; background: var(--accent-bg); border: 1px solid var(--accent-bdr);
    border-radius: 10px; display: flex; align-items: center; justify-content: center;
    font-size: 17px; color: var(--accent); margin-bottom: 1.1rem;
}
.feat-t { font-size: 15px; font-weight: 600; color: var(--text); margin-bottom: .45rem; letter-spacing: -.015em; }
.feat-d { font-size: 13px; color: var(--dim); line-height: 1.65; }

/* ═══ FORMULA SECTION ═══════════════════════════════════════ */
.formula-inner {
    max-width: 1120px; margin: 0 auto;
    display: grid; grid-template-columns: 1fr 1fr; gap: 4rem; align-items: start;
}
.formula-block {
    background: var(--card); border: 1px solid var(--border); border-radius: 14px;
    padding: 1.6rem 1.8rem; font-family: 'DM Mono', monospace; font-size: 12.5px;
    line-height: 2.1; color: var(--dim);
}
.fc  { color: var(--muted); font-style: italic; }
.fk  { color: var(--accent); }
.fr  { color: var(--success); }
.metrics-preview { display: grid; grid-template-columns: 1fr 1fr; gap: .9rem; }
.mprev { background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 1.1rem 1.25rem; }
.mprev-l { font-size: 10px; color: var(--muted); text-transform: uppercase; letter-spacing: .1em; margin-bottom: .35rem; font-family: 'DM Mono', monospace; }
.mprev-v { font-family: 'DM Mono', monospace; font-size: 21px; font-weight: 500; color: var(--text); letter-spacing: -.03em; }
.mprev-v.pos { color: var(--success); }
.mprev-v.neu { color: var(--text2); }
.mprev-s { font-size: 11px; color: var(--muted); margin-top: .3rem; }

/* ═══ FOOTER ════════════════════════════════════════════════ */
footer {
    padding: 2.25rem 2rem; border-top: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between;
    position: relative; z-index: 2; flex-wrap: wrap; gap: .75rem;
}
.footer-brand { display: flex; align-items: center; gap: 9px; text-decoration: none; }
.footer-logo {
    width: 28px; height: 28px; background: var(--accent); border-radius: 6px;
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 700; color: var(--bg);
}
.footer-name { font-size: 14px; font-weight: 600; color: var(--text2); letter-spacing: -.01em; }
.footer-copy { font-size: 12px; color: var(--muted); }
</style>
</head>
<body>

<!-- ═══ NAVBAR ═══════════════════════════════════════════════ -->
<nav class="nav" id="topNav">
    <a class="nav-brand" href="home.php">
        <div class="nav-logo">FM</div>
        <div>
            <div class="nav-wordmark">EkoMigas Pro</div>
            <div class="nav-sub">Petroleum Economics</div>
        </div>
    </a>
    <div class="nav-links">
        <a href="home.php"    class="nav-a on">Beranda</a>
        <a href="project.php" class="nav-a">Proyek</a>
    </div>
    <a href="index.php" class="btn-nav">
        <i class="bi bi-plus-lg"></i> Proyek Baru
    </a>
</nav>

<!-- ═══ HERO ═════════════════════════════════════════════════ -->
<section class="hero">
    <div class="hero-glow"></div>
    <div class="hero-wm">FM</div>
    <div class="hero-fade"></div>

    <div class="hero-inner">
        <div class="hero-badge">
            <span class="badge-dot"></span>
            Financial Model · Petroleum Economics · v2.1
        </div>

        <h1 class="hero-h1">
            <span class="ln1">Keekonomian</span>
            <span class="ln2">Lapangan Migas</span>
            <span class="ln3">Platform Analisis Financial Model Modern</span>
        </h1>

        <p class="hero-p">
            Hitung tabel FM lengkap, NPV, IRR, dan Payback Period secara otomatis.
            Simpan dan kelola berbagai skenario lapangan minyak &amp; gas dalam satu workspace yang terorganisir.
        </p>

        <div class="hero-ctas">
            <a href="index.php" class="btn-primary-hero">
                <i class="bi bi-lightning-charge-fill"></i>
                Mulai Proyek Baru
            </a>
            <a href="project.php" class="btn-secondary-hero">
                <i class="bi bi-folder2-open"></i>
                <?= $totalProjects > 0 ? "Lihat {$totalProjects} Proyek Tersimpan" : 'Lihat Semua Proyek' ?>
            </a>
        </div>

        <div class="hero-chips">
            <div class="chip"><i class="bi bi-graph-up-arrow"></i> NPV Otomatis</div>
            <div class="chip"><i class="bi bi-percent"></i> IRR &amp; Payback</div>
            <div class="chip"><i class="bi bi-table"></i> Tabel FM Lengkap</div>
            <div class="chip"><i class="bi bi-folder2"></i> Multi-Proyek</div>
            <div class="chip"><i class="bi bi-printer"></i> Export &amp; Print</div>
        </div>
    </div>

    <div class="scroll-ind">
        <div class="si-label">scroll</div>
        <div class="si-line"></div>
    </div>
</section>

<!-- ═══ STATS BAR ═════════════════════════════════════════════ -->
<div class="stats-bar">
    <div class="stats-inner">
        <div class="stat">
            <div class="stat-n"><?= $totalProjects ?><span class="a">+</span></div>
            <div class="stat-l">Proyek Tersimpan</div>
        </div>
        <div class="stat">
            <div class="stat-n">3<span class="a">×</span></div>
            <div class="stat-l">Metode Depresiasi</div>
        </div>
        <div class="stat">
            <div class="stat-n">4<span class="a">+</span></div>
            <div class="stat-l">Metrik Keuangan</div>
        </div>
        <div class="stat">
            <div class="stat-n">∞</div>
            <div class="stat-l">Skenario Proyek</div>
        </div>
    </div>
</div>

<!-- ═══ RECENT PROJECTS ═══════════════════════════════════════ -->
<section class="sec" id="projects">
<div class="sec-inner">
    <div class="sec-hdr">
        <div>
            <div class="sec-label">Workspace</div>
            <h2 class="sec-title">Proyek Tersimpan</h2>
            <p class="sec-desc">Lanjutkan analisis yang ada atau buat skenario baru kapan saja.</p>
        </div>
        <?php if (!empty($recentProjects)): ?>
        <a href="project.php" class="btn-all">Lihat Semua <i class="bi bi-arrow-right"></i></a>
        <?php endif; ?>
    </div>

    <?php if (empty($recentProjects)): ?>
    <div class="empty">
        <i class="bi bi-folder2"></i>
        <div class="empty-t">Belum Ada Proyek</div>
        <p class="empty-p">Buat proyek pertama untuk memulai analisis keekonomian lapangan migas.</p>
        <a href="index.php" class="btn-start"><i class="bi bi-plus-lg"></i> Buat Proyek Pertama</a>
    </div>
    <?php else: ?>
    <div class="proj-grid">
        <?php foreach ($recentProjects as $proj):
            $p     = $proj['params'];
            $total = number_format((float)($p['capital']??0)+(float)($p['non_capital']??0),0,'.',',');
            $ms    = ['straight_line'=>'SL','declining_balance'=>'DB','sum_years_digits'=>'SYD'][$p['metode_depresiasi']??'']??'—';
        ?>
        <div class="pc">
            <div class="pc-body">
                <div class="pc-id"><?= htmlspecialchars($proj['id']) ?></div>
                <div class="pc-name" title="<?= htmlspecialchars($proj['name']) ?>"><?= htmlspecialchars($proj['name']) ?></div>
                <div class="pc-params">
                    <div class="pp"><div class="pp-k">Durasi</div><div class="pp-v"><?= $p['jangka_waktu']??'—' ?> thn</div></div>
                    <div class="pp"><div class="pp-k">Harga Minyak</div><div class="pp-v">$<?= number_format((float)($p['harga_minyak']??0),2) ?>/bbl</div></div>
                    <div class="pp"><div class="pp-k">Total Investasi</div><div class="pp-v">$<?= $total ?>M</div></div>
                    <div class="pp"><div class="pp-k">Tax · Dep.</div><div class="pp-v"><?= $p['pajak']??'—' ?>% · <?= $ms ?></div></div>
                </div>
                <div class="pc-date"><i class="bi bi-clock"></i> <?= date('d M Y, H:i', strtotime($proj['updated_at'])) ?></div>
            </div>
            <div class="pc-actions">
                <a href="index.php?load=<?= urlencode($proj['id']) ?>" class="btn-pc btn-pc-e">
                    <i class="bi bi-pencil-square"></i> Edit
                </a>
                <form method="POST" action="calculate.php" style="flex:1;display:flex">
                    <?php foreach ($p as $k=>$v): ?><input type="hidden" name="<?= htmlspecialchars($k) ?>" value="<?= htmlspecialchars($v) ?>"><?php endforeach; ?>
                    <input type="hidden" name="project_id"   value="<?= htmlspecialchars($proj['id']) ?>">
                    <input type="hidden" name="project_name" value="<?= htmlspecialchars($proj['name']) ?>">
                    <button type="submit" class="btn-pc btn-pc-c" style="width:100%">
                        <i class="bi bi-lightning-charge"></i> Hitung
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- New project card -->
        <a href="index.php" class="pc pc-new">
            <div class="pc-new-inner">
                <i class="bi bi-plus-circle"></i>
                <div class="pc-new-title">Proyek Baru</div>
                <div class="pc-new-sub">Tambah skenario baru</div>
            </div>
        </a>
    </div>
    <?php endif; ?>
</div>
</section>

<!-- ═══ FEATURES ══════════════════════════════════════════════ -->
<section class="sec sec-alt" id="features">
<div class="sec-inner">
    <div class="sec-label">Kemampuan</div>
    <h2 class="sec-title">Fitur Utama Platform</h2>
    <p class="sec-desc" style="max-width:480px">Semua yang dibutuhkan untuk analisis keekonomian lapangan migas yang akurat dan profesional.</p>
    <div class="feat-grid">
        <div class="feat">
            <div class="feat-ico"><i class="bi bi-table"></i></div>
            <div class="feat-t">Tabel FM Lengkap</div>
            <div class="feat-d">Tabel Financial Model dengan kolom Income, Investasi, Opex, Di, Taxable Income, Tax, NCF Undiscounted &amp; Discounted, serta Kumulatif NCF.</div>
        </div>
        <div class="feat">
            <div class="feat-ico"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="feat-t">Metrik NPV, IRR &amp; Payback</div>
            <div class="feat-d">Net Present Value, Internal Rate of Return (Newton-Raphson), Payback Period, dan Profitability Index dihitung otomatis dari parameter proyek.</div>
        </div>
        <div class="feat">
            <div class="feat-ico"><i class="bi bi-folder2-open"></i></div>
            <div class="feat-t">Manajemen Multi-Proyek</div>
            <div class="feat-d">Simpan, kelola, dan bandingkan berbagai skenario lapangan migas. Edit dan hitung ulang kapan saja tanpa kehilangan data sebelumnya.</div>
        </div>
        <div class="feat">
            <div class="feat-ico"><i class="bi bi-droplet-half"></i></div>
            <div class="feat-t">Profil Produksi Realistis</div>
            <div class="feat-d">Model produksi dengan fase build-up eksponensial menuju puncak, lalu decline rate eksponensial yang dapat dikonfigurasi secara bebas.</div>
        </div>
        <div class="feat">
            <div class="feat-ico"><i class="bi bi-calculator"></i></div>
            <div class="feat-t">3 Metode Depresiasi</div>
            <div class="feat-d">Pilih antara Straight-Line, Declining Balance (Double-Declining), atau Sum of Years Digits sesuai standar akuntansi yang digunakan.</div>
        </div>
        <div class="feat">
            <div class="feat-ico"><i class="bi bi-sliders2"></i></div>
            <div class="feat-t">Parameter Lengkap &amp; Fleksibel</div>
            <div class="feat-d">Atur tax rate, discount rate, eskalasi Opex per tahun, harga minyak, laju kenaikan produksi, dan semua variabel keekonomian secara bebas.</div>
        </div>
    </div>
</div>
</section>

<!-- ═══ FORMULA PREVIEW ════════════════════════════════════════ -->
<section class="sec" id="formula">
<div class="formula-inner">
    <div>
        <div class="sec-label">Metodologi</div>
        <h2 class="sec-title">Alur Perhitungan FM</h2>
        <p class="sec-desc" style="margin-bottom:1.75rem">Setiap langkah perhitungan transparan dan dapat ditelusuri. Berbasis standar keekonomian migas yang umum digunakan di industri perminyakan.</p>
        <div class="metrics-preview">
            <div class="mprev">
                <div class="mprev-l">NPV</div>
                <div class="mprev-v pos">+ $XX,XXX M</div>
                <div class="mprev-s">Net Present Value</div>
            </div>
            <div class="mprev">
                <div class="mprev-l">IRR</div>
                <div class="mprev-v pos">XX.XX %</div>
                <div class="mprev-s">Internal Rate of Return</div>
            </div>
            <div class="mprev">
                <div class="mprev-l">Payback Period</div>
                <div class="mprev-v neu">X.X thn</div>
                <div class="mprev-s">Pengembalian Investasi</div>
            </div>
            <div class="mprev">
                <div class="mprev-l">Profitability Index</div>
                <div class="mprev-v neu">X.XXX</div>
                <div class="mprev-s">PI = (NPV + Inv) / Inv</div>
            </div>
        </div>
    </div>

    <div class="formula-block">
        <span class="fc"># Setiap tahun t = 1 ... N</span><br><br>
        <span class="fk">Produksi</span>  = f(P₁, g, d, t)<br>
        <span class="fk">Income</span>    = Produksi × Harga<br>
        <span class="fk">Opex</span>      = Base × (1+esc)^t<br>
        <span class="fk">Di</span>        = f(metode, Inv, t)<br>
        <span class="fk">Taxable</span>   = Income − Opex − Di<br>
        <span class="fk">Tax</span>       = Taxable × rate<br><br>
        <span class="fr">NCF</span>       = Taxable − Tax<br>
        <span class="fr">NCF_disc</span>  = NCF / (1+r)ᵗ<br><br>
        <span class="fc"># Metrik Agregat</span><br><br>
        <span class="fr">NPV</span>       = Σ NCF_disc (t=0..N)<br>
        <span class="fr">IRR</span>       = Newton–Raphson<br>
        <span class="fr">Payback</span>   = interpolasi kumulatif NCF
    </div>
</div>
</section>

<!-- ═══ FOOTER ════════════════════════════════════════════════ -->
<footer>
    <a class="footer-brand" href="home.php">
        <div class="footer-logo">FM</div>
        <span class="footer-name">EkoMigas Pro</span>
    </a>
    <div class="footer-copy">Platform Analisis Keekonomian Lapangan Minyak &amp; Gas · Manajemen Pengelolaan Lapangan Migas</div>
</footer>

<script>
const nav = document.getElementById('topNav');
window.addEventListener('scroll', () => {
    nav.style.background = window.scrollY > 50
        ? 'rgba(12,12,12,.97)' : 'rgba(12,12,12,.82)';
}, { passive: true });
</script>
</body>
</html>