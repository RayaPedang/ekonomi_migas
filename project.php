<?php
require_once __DIR__ . '/storage.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id   = trim($_POST['project_id'] ?? '') ?: newProjectId();
        $name = trim($_POST['project_name'] ?? '') ?: 'Proyek Tanpa Nama';
        saveProject($id, $name, extractFmParams($_POST));
        header("Location: project.php?toast=saved");
        exit;
    }
    if ($action === 'delete') {
        $id = trim($_POST['project_id'] ?? '');
        if ($id) deleteProject($id);
        header("Location: project.php?toast=deleted");
        exit;
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;0,9..40,800;1,9..40,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root{--bg:#0C0C0C;--bg2:#141414;--card:#1A1A1A;--sidebar:#191919;--border:#272727;--accent:#938A87;--accent-h:#B0A8A5;--accent-bg:rgba(147,138,135,.08);--accent-bdr:rgba(147,138,135,.22);--amber:var(--accent);--amber2:var(--accent-h);--amber-bg:var(--accent-bg);--text:#EDEAE6;--text2:#B8B3AE;--muted:#605E5E;--dim:#7A7572;--success:#6E9B7A;--danger:#9B6E6E;--cyan:#22d3ee;--sidebar-w:240px}
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        body{background:var(--bg);color:var(--text);font-family:'DM Sans',sans-serif;min-height:100vh}
        body::before{content:'';position:fixed;inset:0;background-image:radial-gradient(circle,rgba(255,255,255,.045) 1px,transparent 1px);background-size:26px 26px;pointer-events:none;z-index:0}

        .top-nav{position:fixed;top:0;left:0;right:0;height:62px;background:rgba(12,12,12,.82);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 1.5rem;z-index:300;backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px);transition:.25s background}
        .nav-left{display:flex;align-items:center;gap:14px}
        .btn-toggle{width:38px;height:38px;background:transparent;border:1px solid var(--border);border-radius:9px;color:var(--dim);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:.18s all;flex-shrink:0}
        .btn-toggle:hover{background:var(--accent-bg);border-color:var(--accent-bdr);color:var(--accent)}
        .btn-toggle i{font-size:17px}
        .nav-brand{display:flex;align-items:center;gap:10px}
        .nav-logo{width:36px;height:36px;background:var(--accent);border-radius:8px;display:flex;align-items:center;justify-content:center;font-family:'DM Sans',sans-serif;font-weight:700;font-size:14px;color:var(--bg);box-shadow:0 0 18px rgba(147,138,135,.35);flex-shrink:0}
        .nav-title{font-family:'DM Sans',sans-serif;font-weight:700;font-size:18px;color:var(--text)}
        .nav-sub{font-size:10.5px;color:var(--muted);letter-spacing:.06em;text-transform:uppercase}
        .btn-new{background:var(--accent);border:none;color:var(--bg);font-family:'DM Sans',sans-serif;font-weight:700;font-size:13px;padding:8px 18px;border-radius:8px;cursor:pointer;text-decoration:none;display:flex;align-items:center;gap:6px;transition:.18s all}
        .btn-new:hover{background:var(--accent-h);transform:translateY(-1px);box-shadow:0 6px 18px rgba(147,138,135,.3);color:var(--bg)}

        .sidebar{position:fixed;left:0;top:62px;bottom:0;width:var(--sidebar-w);background:var(--sidebar);border-right:1px solid var(--border);padding:1.4rem .9rem;overflow-y:auto;z-index:200;transition:transform .25s ease}
        .sidebar.collapsed{transform:translateX(-100%)}
        .sb-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:var(--muted);padding:.3rem .6rem;margin-top:.5rem;display:block}
        .sb-item{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:9px;color:var(--dim);font-size:13.5px;text-decoration:none;transition:.18s all;margin-bottom:2px}
        .sb-item:hover{background:rgba(147,138,135,.08);color:var(--text)}
        .sb-item.active{background:var(--accent-bg);color:var(--accent);border:1px solid var(--accent-bdr)}
        .sb-item i{font-size:15px;flex-shrink:0}
        .sb-hr{border:none;border-top:1px solid var(--border);margin:.75rem 0}

        .main{margin-left:var(--sidebar-w);padding-top:62px;position:relative;z-index:1;transition:margin-left .25s ease}
        .main.expanded{margin-left:0}
        .page-body{padding:2rem 2.5rem 3rem}
        .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:150}
        .sidebar-overlay.show{display:block}

        .toast-bar{border-radius:10px;padding:12px 18px;display:flex;align-items:center;gap:10px;margin-bottom:1.5rem;font-size:13.5px;animation:slideIn .3s ease}
        .toast-bar.success{background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);color:var(--success)}
        .toast-bar.danger{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);color:var(--danger)}
        @keyframes slideIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}

        .page-hdr{margin-bottom:1.75rem;display:flex;align-items:flex-start;justify-content:space-between}
        .page-hdr h1{font-family:'Rajdhani',sans-serif;font-weight:700;font-size:25px;margin-bottom:.3rem}
        .page-hdr p{color:var(--muted);font-size:13px}

        .stats-bar{display:flex;gap:1rem;margin-bottom:1.75rem}
        .stat-pill{background:var(--card);border:1px solid var(--border);border-radius:10px;padding:10px 18px;display:flex;align-items:center;gap:10px}
        .sp-val{font-family:'IBM Plex Mono',monospace;font-size:20px;font-weight:600;color:var(--amber)}
        .sp-lbl{font-size:12px;color:var(--muted)}

        .empty-state{background:var(--card);border:1px dashed var(--border);border-radius:16px;padding:4rem 2rem;text-align:center}
        .empty-state i{font-size:48px;color:var(--muted);margin-bottom:1rem;display:block}
        .empty-state h3{font-family:'Rajdhani',sans-serif;font-size:20px;color:var(--dim);margin-bottom:.5rem}
        .empty-state p{font-size:13.5px;color:var(--muted);margin-bottom:1.5rem}

        .proj-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(330px,1fr));gap:1.2rem}
        .proj-card{background:var(--card);border:1px solid var(--border);border-radius:14px;overflow:hidden;transition:.2s transform,.2s box-shadow;position:relative}
        .proj-card:hover{transform:translateY(-3px);box-shadow:0 12px 38px rgba(0,0,0,.4)}
        .proj-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--amber) 0%,var(--cyan) 100%)}

        .pc-body{padding:1.2rem 1.3rem .9rem}
        .pc-id{font-family:'IBM Plex Mono',monospace;font-size:10px;color:var(--muted);letter-spacing:.5px;margin-bottom:.4rem}
        .pc-name{font-family:'Rajdhani',sans-serif;font-weight:700;font-size:17px;color:var(--text);margin-bottom:.85rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .pc-params{display:grid;grid-template-columns:1fr 1fr;gap:5px 9px;margin-bottom:.85rem}
        .pp-item{background:rgba(7,16,30,.5);border:1px solid var(--border);border-radius:7px;padding:5px 8px}
        .pp-label{font-size:9.5px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px}
        .pp-value{font-family:'IBM Plex Mono',monospace;font-size:12px;color:var(--accent)}
        .pc-date{font-size:11px;color:var(--muted);display:flex;align-items:center;gap:6px;margin-bottom:.8rem}
        .pc-actions{border-top:1px solid var(--border);padding:.8rem 1.3rem;display:flex;gap:.55rem}
        .btn-edit{flex:1;padding:8px;background:var(--amber-bg);border:1px solid var(--accent-bdr);color:var(--accent);font-family:'DM Sans',sans-serif;font-weight:700;font-size:13px;border-radius:8px;cursor:pointer;text-decoration:none;display:flex;align-items:center;justify-content:center;gap:5px;transition:.18s all}
        .btn-edit:hover{background:var(--accent);color:var(--bg)}
        .btn-calc-again{flex:1;padding:8px;background:rgba(34,211,238,.08);border:1px solid rgba(34,211,238,.25);color:var(--cyan);font-family:'Rajdhani',sans-serif;font-weight:700;font-size:13px;border-radius:8px;cursor:pointer;text-decoration:none;display:flex;align-items:center;justify-content:center;gap:5px;transition:.18s all}
        .btn-calc-again:hover{background:var(--cyan);color:#000}
        .btn-del{padding:8px 12px;background:rgba(239,68,68,.07);border:1px solid rgba(239,68,68,.2);color:var(--danger);font-family:'Rajdhani',sans-serif;font-weight:700;font-size:13px;border-radius:8px;cursor:pointer;display:flex;align-items:center;gap:5px;transition:.18s all}
        .btn-del:hover{background:var(--danger);color:#fff}

        ::-webkit-scrollbar{width:6px;height:6px}::-webkit-scrollbar-track{background:var(--bg)}::-webkit-scrollbar-thumb{background:var(--border);border-radius:6px}
    </style>
</head>
<body>

<nav class="top-nav">
    <div class="nav-left">
        <button class="btn-toggle" id="sidebarToggle" title="Buka/Tutup Sidebar">
            <i class="bi bi-list"></i>
        </button>
        <div class="nav-brand">
            <div class="nav-logo">FM</div>
            <div>
                <div class="nav-title">EkoMigas Pro</div>
                <div class="nav-sub">Manajemen Proyek</div>
            </div>
        </div>
    </div>
    <a href="index.php" class="btn-new"><i class="bi bi-plus-lg"></i> Proyek Baru</a>
</nav>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="sidebar">
    <span class="sb-label">Menu</span>
    <a class="sb-item" href="index.php"><i class="bi bi-sliders2"></i> Input Parameter</a>
    <a class="sb-item active" href="project.php"><i class="bi bi-folder2-open"></i> Semua Proyek</a>
</aside>

<main class="main" id="mainContent">
<div class="page-body">

    <?php if ($toast === 'saved'): ?>
    <div class="toast-bar success"><i class="bi bi-check-circle-fill"></i> Proyek berhasil disimpan!</div>
    <?php elseif ($toast === 'deleted'): ?>
    <div class="toast-bar danger"><i class="bi bi-trash3-fill"></i> Proyek berhasil dihapus.</div>
    <?php endif; ?>

    <div class="page-hdr">
        <div>
            <h1>Semua Proyek</h1>
            <p>Kelola semua perhitungan Financial Model lapangan migas Anda.</p>
        </div>
    </div>

    <div class="stats-bar">
        <div class="stat-pill"><div><div class="sp-val"><?= count($projects) ?></div><div class="sp-lbl">Total Proyek</div></div></div>
        <?php if (!empty($projects)): ?>
        <div class="stat-pill"><div><div class="sp-val" style="color:var(--cyan);font-size:14px"><?= htmlspecialchars($projects[0]['name']) ?></div><div class="sp-lbl">Terakhir Diperbarui</div></div></div>
        <?php endif; ?>
    </div>

    <?php if (empty($projects)): ?>
    <div class="empty-state">
        <i class="bi bi-folder2"></i>
        <h3>Belum Ada Proyek</h3>
        <p>Buat proyek baru untuk mulai menghitung keekonomian lapangan migas.</p>
        <a href="index.php" class="btn-new" style="display:inline-flex"><i class="bi bi-plus-lg"></i> Buat Proyek Pertama</a>
    </div>
    <?php else: ?>
    <div class="proj-grid">
        <?php foreach ($projects as $proj):
            $p = $proj['params'];
            $ms = ['straight_line'=>'SL','declining_balance'=>'DB','sum_years_digits'=>'SYD'][$p['metode_depresiasi']??'']??'—';
        ?>
        <div class="proj-card">
            <div class="pc-body">
                <div class="pc-id"><?= htmlspecialchars($proj['id']) ?></div>
                <div class="pc-name" title="<?= htmlspecialchars($proj['name']) ?>"><?= htmlspecialchars($proj['name']) ?></div>
                <div class="pc-params">
                    <div class="pp-item"><div class="pp-label">Durasi</div><div class="pp-value"><?= htmlspecialchars($p['jangka_waktu']??'—') ?> thn</div></div>
                    <div class="pp-item"><div class="pp-label">Harga Minyak</div><div class="pp-value">$<?= number_format((float)($p['harga_minyak']??0),2) ?>/bbl</div></div>
                    <div class="pp-item"><div class="pp-label">Tax Rate</div><div class="pp-value"><?= htmlspecialchars($p['pajak']??'—') ?>%</div></div>
                    <div class="pp-item"><div class="pp-label">Discount Rate</div><div class="pp-value"><?= htmlspecialchars($p['discount_rate']??'—') ?>%</div></div>
                    <div class="pp-item"><div class="pp-label">Total Investasi</div><div class="pp-value">$<?= number_format((float)($p['capital']??0)+(float)($p['non_capital']??0),0,'.',',') ?>M</div></div>
                    <div class="pp-item"><div class="pp-label">Depresiasi</div><div class="pp-value"><?= $ms ?></div></div>
                </div>
                <div class="pc-date"><i class="bi bi-clock-history"></i> Diperbarui <?= date('d M Y, H:i', strtotime($proj['updated_at'])) ?></div>
            </div>
            <div class="pc-actions">
                <a href="index.php?load=<?= urlencode($proj['id']) ?>" class="btn-edit"><i class="bi bi-pencil-square"></i> Edit</a>
                <form method="POST" action="calculate.php" style="flex:1;display:flex">
                    <?php foreach ($p as $k => $v): ?><input type="hidden" name="<?= htmlspecialchars($k) ?>" value="<?= htmlspecialchars($v) ?>"><?php endforeach; ?>
                    <input type="hidden" name="project_id"   value="<?= htmlspecialchars($proj['id']) ?>">
                    <input type="hidden" name="project_name" value="<?= htmlspecialchars($proj['name']) ?>">
                    <button type="submit" class="btn-calc-again" style="width:100%"><i class="bi bi-lightning-charge"></i> Hitung</button>
                </form>
                <form method="POST" action="project.php" onsubmit="return confirm('Hapus proyek \'<?= addslashes(htmlspecialchars($proj['name'])) ?>\'?')">
                    <input type="hidden" name="action"     value="delete">
                    <input type="hidden" name="project_id" value="<?= htmlspecialchars($proj['id']) ?>">
                    <button type="submit" class="btn-del"><i class="bi bi-trash3"></i></button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

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

    setTimeout(()=>{ const t=document.querySelector('.toast-bar'); if(t) t.style.transition='.4s opacity',t.style.opacity='0',setTimeout(()=>t.remove(),400); }, 4000);
</script>
</body>
</html>