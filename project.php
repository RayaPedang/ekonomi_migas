<?php
require_once __DIR__ . '/storage.php';
$page = 'project';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        $id = trim($_POST['project_id'] ?? '');
        if ($id) deleteProject($id);
        header('Location: project.php?toast=deleted'); exit;
    }
    if ($action === 'save') {
        $id   = trim($_POST['project_id'] ?? '') ?: newProjectId();
        $name = trim($_POST['project_name'] ?? '') ?: 'Proyek Tanpa Nama';
        saveProject($id, $name, extractFmParams($_POST));
        header('Location: project.php?toast=saved'); exit;
    }
}

$projects = listProjects();
$toast    = $_GET['toast'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semua Proyek – EkoMigas Pro</title>
    <link rel="stylesheet" href="style.css">
<style>
/* ── Stats strip ─────────────────────── */
.stats-strip {
    display: flex; gap: .75rem; margin-bottom: 2rem; flex-wrap: wrap;
}
.stat-pill {
    background: var(--card); border: 1px solid var(--border); border-radius: 10px;
    padding: 10px 18px; display: flex; align-items: center; gap: 8px;
}
.sp-val   { font-family: 'DM Mono', monospace; font-size: 28px; font-weight: 300; color: var(--accent); line-height: 1; align-items: center; }
.sp-label { font-size: 12px; color: var(--muted); }

/* ── Project grid ────────────────────── */
.proj-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.1rem; }

.proj-card {
    background: var(--card); border: 1px solid var(--border); border-radius: var(--radius);
    overflow: hidden; display: flex; flex-direction: column;
    transition: transform .2s, box-shadow .2s; position: relative;
}
.proj-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 1px;
    background: linear-gradient(90deg, var(--accent) 0%, transparent 60%);
    opacity: 0; transition: opacity .25s;
}
.proj-card:hover { transform: translateY(-2px); box-shadow: 0 10px 36px rgba(5,3,4,.6); }
.proj-card:hover::before { opacity: 1; }

.pc-body { padding: 1.25rem 1.4rem; flex: 1; }
.pc-id   { font-family: 'DM Mono', monospace; font-size: 10px; color: var(--muted); letter-spacing: .3px; margin-bottom: .4rem; }
.pc-name { font-size: 16px; font-weight: 500; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 1rem; letter-spacing: -.01em; }

.pc-params { display: grid; grid-template-columns: 1fr 1fr; gap: 5px; margin-bottom: 1rem; }
.pp { background: var(--in-bg); border: 1px solid var(--border); border-radius: 7px; padding: 6px 9px; }
.pp-k { font-size: 9.5px; text-transform: uppercase; letter-spacing: .7px; color: var(--muted); font-weight: 600; margin-bottom: 1px; }
.pp-v { font-family: 'DM Mono', monospace; font-size: 12.5px; color: var(--accent); }

.pc-date { font-size: 11px; color: var(--muted); display: flex; align-items: center; gap: 5px; font-family: 'DM Mono', monospace; }

.pc-actions {
    border-top: 1px solid var(--border); padding: .85rem 1.4rem;
    display: flex; gap: .55rem;
}
.pc-actions .btn { flex: 1; justify-content: center; font-size: 12.5px; padding: 8px; }
.pc-actions .btn-del { flex: 0 0 auto; padding: 8px 12px; }

/* ── Empty state ─────────────────────── */
.empty-state {
    background: var(--card); border: 1px dashed var(--border); border-radius: var(--radius);
    padding: 5rem 2rem; text-align: center;
}
.empty-state i   { font-size: 44px; color: var(--muted); display: block; margin-bottom: 1rem; }
.empty-state h3  { font-size: 18px; font-weight: 300; color: var(--dim); margin-bottom: .5rem; }
.empty-state p   { font-size: 13.5px; color: var(--muted); margin-bottom: 1.75rem; }
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
        <a href="project.php" class="nav-a on">Proyek</a>
    </div>
    <div class="nav-right">
        <button type="button" id="themeToggle" class="btn btn-ghost btn-sm theme-toggle" aria-label="Ganti tema">
            <i class="bi bi-moon-stars-fill"></i>
            <span>Tema Gelap</span>
        </button>
        <a href="index.php" class="btn-nav">
            <i class="bi bi-plus-lg"></i> Proyek Baru
        </a>
    </div>
</nav>

<!-- ═══ PAGE ════════════════════════════════════════ -->
<div class="page-wrap">
<div class="page-body">
<div class="container">

    <?php if ($toast === 'saved'): ?>
    <div class="toast toast-success"><i class="bi bi-check-circle-fill"></i> Proyek berhasil disimpan.</div>
    <?php elseif ($toast === 'deleted'): ?>
    <div class="toast toast-danger"><i class="bi bi-trash3-fill"></i> Proyek berhasil dihapus.</div>
    <?php endif; ?>

    <!-- Page header -->
    <div class="page-hdr page-hdr-row">
        <div>
            <h1>Semua <strong>Proyek</strong></h1>
            <p>Kelola semua skenario perhitungan Financial Model lapangan migas.</p>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-strip">
        <div class="stat-pill">
            <div class="sp-val"><?= count($projects) ?></div>
            <div class="sp-label">Total Proyek</div>
        </div>
        <?php if (!empty($projects)): ?>
        <div class="stat-pill" style="flex:1;min-width:0">
            <div>
                <div style="font-size:10.5px;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:.7px;margin-bottom:.2rem">Terakhir diperbarui</div>
                <div style="font-size:14px;font-weight:500;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($projects[0]['name']) ?></div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Grid or empty state -->
    <?php if (empty($projects)): ?>
    <div class="empty-state">
        <i class="bi bi-folder2"></i>
        <h3>Belum Ada Proyek</h3>
        <p>Buat proyek pertama untuk mulai menghitung keekonomian lapangan migas.</p>
        <a href="index.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Buat Proyek Pertama</a>
    </div>
    <?php else: ?>
    <div class="proj-grid">
        <?php foreach ($projects as $proj):
            $p = $proj['params'];
            $totalInv = ((float)($p['capital']??0)) + ((float)($p['non_capital']??0));
            $ms = ['straight_line'=>'SL','declining_balance'=>'DB','sum_years_digits'=>'SYD'][$p['metode_depresiasi']??''] ?? '—';
        ?>
        <div class="proj-card">
            <div class="pc-body">
                <div class="pc-id"><?= htmlspecialchars($proj['id']) ?></div>
                <div class="pc-name" title="<?= htmlspecialchars($proj['name']) ?>"><?= htmlspecialchars($proj['name']) ?></div>
                <div class="pc-params">
                    <div class="pp"><div class="pp-k">Durasi</div><div class="pp-v"><?= htmlspecialchars($p['jangka_waktu']??'—') ?> thn</div></div>
                    <div class="pp"><div class="pp-k">Investasi</div><div class="pp-v">$<?= number_format($totalInv,0,'.',',') ?>M</div></div>
                    <div class="pp"><div class="pp-k">Harga Minyak</div><div class="pp-v">$<?= number_format((float)($p['harga_minyak']??0),2) ?>/bbl</div></div>
                    <div class="pp"><div class="pp-k">Tax / WACC</div><div class="pp-v"><?= htmlspecialchars($p['pajak']??'—') ?>% / <?= htmlspecialchars($p['discount_rate']??'—') ?>%</div></div>
                    <div class="pp"><div class="pp-k">Decline Rate</div><div class="pp-v"><?= htmlspecialchars($p['decline']??'—') ?>%/thn</div></div>
                    <div class="pp"><div class="pp-k">Depresiasi</div><div class="pp-v"><?= $ms ?></div></div>
                </div>
                <div class="pc-date"><i class="bi bi-clock-history"></i> <?= date('d M Y, H:i', strtotime($proj['updated_at'])) ?></div>
            </div>
            <div class="pc-actions">
                <a href="index.php?load=<?= urlencode($proj['id']) ?>" class="btn btn-accent btn-sm">
                    <i class="bi bi-pencil-square"></i> Edit
                </a>
                <form method="POST" action="calculate.php" style="flex:1;display:flex">
                    <?php foreach ($p as $k => $v): ?>
                    <input type="hidden" name="<?= htmlspecialchars($k) ?>" value="<?= htmlspecialchars($v) ?>">
                    <?php endforeach; ?>
                    <input type="hidden" name="project_id"   value="<?= htmlspecialchars($proj['id']) ?>">
                    <input type="hidden" name="project_name" value="<?= htmlspecialchars($proj['name']) ?>">
                    <button type="submit" class="btn btn-ghost btn-sm" style="width:100%;justify-content:center">
                        <i class="bi bi-lightning-charge"></i> Hitung
                    </button>
                </form>
                <form method="POST" action="project.php"
                      onsubmit="return confirm('Hapus proyek \'<?= addslashes(htmlspecialchars($proj['name'])) ?>\'?')">
                    <input type="hidden" name="action"     value="delete">
                    <input type="hidden" name="project_id" value="<?= htmlspecialchars($proj['id']) ?>">
                    <button type="submit" class="btn btn-danger btn-sm btn-del">
                        <i class="bi bi-trash3"></i>
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>
</div>
</div>

<script>
const nav = document.getElementById('topNav');
const root = document.documentElement;
const toggle = document.getElementById('themeToggle');

function applyTheme(theme) {
    root.setAttribute('data-theme', theme);
    localStorage.setItem('ekomigas-theme', theme);
    const isLight = theme === 'light';
    toggle.querySelector('i').className = isLight ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
    toggle.querySelector('span').textContent = isLight ? 'Tema Terang' : 'Tema Gelap';
    updateNav();
}

function updateNav() {
    const isLight = root.getAttribute('data-theme') === 'light';
    nav.style.background = window.scrollY > 50
        ? (isLight ? 'rgba(255,255,255,.96)' : 'rgba(5,3,4,.97)')
        : (isLight ? 'rgba(255,255,255,.88)' : 'rgba(5,3,4,.88)');
}

const savedTheme = localStorage.getItem('ekomigas-theme') || 'dark';
applyTheme(savedTheme);
window.addEventListener('scroll', updateNav, { passive: true });
toggle?.addEventListener('click', () => applyTheme(root.getAttribute('data-theme') === 'light' ? 'dark' : 'light'));

setTimeout(() => {
    const t = document.querySelector('.toast');
    if (t) { t.style.transition = '.4s opacity'; t.style.opacity = '0'; setTimeout(() => t.remove(), 400); }
}, 4000);
</script>
</body>
</html>