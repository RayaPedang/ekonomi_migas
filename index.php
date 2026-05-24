<?php
require_once __DIR__ . '/storage.php';

$loadedProject = null;
$p = [];

if (!empty($_GET['load'])) {
    $loadedProject = loadProject($_GET['load']);
    if ($loadedProject) $p = $loadedProject['params'];
}

function val(array $p, string $key, string $default): string {
    return htmlspecialchars($p[$key] ?? $default);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EkoMigas Pro – Input Parameter</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;0,9..40,800;1,9..40,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root{
            --bg:#0C0C0C;--bg2:#141414;--card:#1A1A1A;--sidebar:#191919;
            --border:#272727;--accent:#938A87;--accent-h:#B0A8A5;
            --accent-bg:rgba(147,138,135,.08);--accent-bdr:rgba(147,138,135,.22);
            --amber:var(--accent);--amber2:var(--accent-h);--amber-bg:var(--accent-bg);
            --text:#EDEAE6;--text2:#B8B3AE;--muted:#605E5E;--dim:#7A7572;
            --success:#6E9B7A;--danger:#9B6E6E;--cyan:#22d3ee;
            --in-bg:#101110;--in-bdr:#2c2c2c;--sidebar-w:240px;
        }
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        body{background:var(--bg);color:var(--text);font-family:'DM Sans',sans-serif;min-height:100vh}
        body::before{content:'';position:fixed;inset:0;background-image:radial-gradient(circle,rgba(255,255,255,.045) 1px,transparent 1px);background-size:26px 26px;pointer-events:none;z-index:0}

        /* ── Navbar ── */
        .top-nav{position:fixed;top:0;left:0;right:0;height:62px;background:rgba(12,12,12,.82);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 1.5rem;z-index:300;backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px);transition:.25s background}
        .nav-left{display:flex;align-items:center;gap:14px}
        .btn-toggle{width:38px;height:38px;background:transparent;border:1px solid var(--border);border-radius:9px;color:var(--dim);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:.18s all;flex-shrink:0}
        .btn-toggle:hover{background:var(--accent-bg);border-color:var(--accent-bdr);color:var(--accent)}
        .btn-toggle i{font-size:17px}
        .nav-brand{display:flex;align-items:center;gap:10px}
        .nav-logo{width:36px;height:36px;background:var(--accent);border-radius:8px;display:flex;align-items:center;justify-content:center;font-family:'DM Sans',sans-serif;font-weight:700;font-size:14px;color:var(--bg);box-shadow:0 0 18px rgba(147,138,135,.35);flex-shrink:0}
        .nav-title{font-family:'DM Sans',sans-serif;font-weight:700;font-size:18px;color:var(--text)}
        .nav-sub{font-size:10.5px;color:var(--muted);letter-spacing:.06em;text-transform:uppercase}
        .nav-tag{background:var(--amber-bg);border:1px solid var(--accent-bdr);color:var(--accent);font-family:'DM Mono',monospace;font-size:11px;padding:4px 11px;border-radius:20px}

        /* ── Sidebar ── */
        .sidebar{position:fixed;left:0;top:62px;bottom:0;width:var(--sidebar-w);background:var(--sidebar);border-right:1px solid var(--border);padding:1.4rem .9rem;overflow-y:auto;z-index:200;transition:transform .25s ease}
        .sidebar.collapsed{transform:translateX(-100%)}
        .sb-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:var(--muted);padding:.3rem .6rem;margin-top:.5rem;display:block}
        .sb-item{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:9px;color:var(--dim);font-size:13.5px;text-decoration:none;transition:.18s all;margin-bottom:2px}
        .sb-item:hover{background:rgba(147,138,135,.08);color:var(--text)}
        .sb-item.active{background:var(--accent-bg);color:var(--accent);border:1px solid var(--accent-bdr)}
        .sb-item i{font-size:15px;flex-shrink:0}
        .sb-hr{border:none;border-top:1px solid var(--border);margin:.75rem 0}

        /* ── Main ── */
        .main{margin-left:var(--sidebar-w);padding-top:62px;position:relative;z-index:1;transition:margin-left .25s ease}
        .main.expanded{margin-left:0}
        .page-body{padding:2rem 2.5rem 3rem;max-width:1020px}

        /* ── Overlay (mobile feel) ── */
        .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:150}
        .sidebar-overlay.show{display:block}

        /* ── Edit banner ── */
        .edit-banner{background:rgba(147,138,135,.07);border:1px solid rgba(147,138,135,.3);border-radius:11px;padding:13px 18px;margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;gap:12px}
        .eb-left{display:flex;align-items:center;gap:12px}
        .eb-icon{font-size:19px;color:var(--amber)}
        .eb-title{font-family:'Rajdhani',sans-serif;font-weight:700;font-size:15px;color:var(--amber)}
        .eb-sub{font-size:12px;color:var(--dim)}
        .btn-clear{font-family:'Rajdhani',sans-serif;font-size:12px;font-weight:600;color:var(--muted);border:1px solid var(--border);background:transparent;border-radius:7px;padding:5px 12px;cursor:pointer;text-decoration:none;transition:.15s all}
        .btn-clear:hover{color:var(--text);border-color:var(--dim)}

        .page-hdr{margin-bottom:1.75rem}
        .page-hdr h1{font-family:'Rajdhani',sans-serif;font-weight:700;font-size:25px;color:var(--text);margin-bottom:.3rem}
        .page-hdr p{color:var(--muted);font-size:13px}

        /* ── Nama proyek ── */
        .proj-name-field{background:rgba(147,138,135,.05);border:1px solid rgba(147,138,135,.2);border-radius:11px;padding:13px 17px;margin-bottom:1.3rem;display:flex;align-items:center;gap:13px}
        .pnf-icon{font-size:17px;color:var(--amber);flex-shrink:0}
        .pnf-group{flex:1}
        .pnf-label{font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin-bottom:.35rem}

        /* ── Section cards ── */
        .scard{background:var(--card);border:1px solid var(--border);border-radius:13px;padding:1.5rem 1.65rem;margin-bottom:1.3rem;position:relative;overflow:hidden}
        .scard::after{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--amber) 0%,transparent 65%)}
        .sc-title{font-family:'DM Sans',sans-serif;font-weight:700;font-size:14px;color:var(--accent);text-transform:uppercase;letter-spacing:1px;margin-bottom:1.2rem;display:flex;align-items:center;gap:8px}

        /* ── Form ── */
        label{display:block;font-size:11px;font-weight:600;color:var(--dim);text-transform:uppercase;letter-spacing:.5px;margin-bottom:.4rem}
        input[type=number],input[type=text],select{width:100%;background:var(--in-bg);border:1px solid var(--in-bdr);color:var(--text);border-radius:8px;padding:9px 12px;font-family:'DM Mono',monospace;font-size:13px;transition:.18s all;appearance:none;-webkit-appearance:none}
        input:focus,select:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(147,138,135,.12)}
        input::placeholder{color:var(--muted)}
        select option{background:var(--card);color:var(--text)}
        .fg{margin-bottom:1.1rem}
        .g2{display:grid;grid-template-columns:1fr 1fr;gap:0 1.4rem}
        .g3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:0 1.4rem}
        .g4{display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:0 1.4rem}
        .hint{font-size:10.5px;color:var(--muted);margin-top:.3rem;font-style:italic}

        /* ── Referensi formula card ── */
        .ref-card{background:var(--card);border:1px solid var(--border);border-radius:13px;margin-bottom:1.3rem;overflow:hidden}
        .ref-header{padding:1rem 1.5rem;display:flex;align-items:center;justify-content:space-between;cursor:pointer;user-select:none;transition:.18s background}
        .ref-header:hover{background:rgba(147,138,135,.04)}
        .ref-header-left{display:flex;align-items:center;gap:9px;font-family:'Rajdhani',sans-serif;font-weight:700;font-size:13.5px;color:var(--dim);text-transform:uppercase;letter-spacing:1px}
        .ref-header-left i{font-size:15px;color:var(--amber)}
        .ref-chevron{color:var(--muted);transition:transform .25s ease;font-size:14px}
        .ref-card.open .ref-chevron{transform:rotate(180deg)}
        .ref-body{display:none;padding:0 1.5rem 1.4rem;border-top:1px solid var(--border)}
        .ref-card.open .ref-body{display:block}
        .ref-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;padding-top:1.1rem}
        .ref-col-title{font-family:'Rajdhani',sans-serif;font-size:11px;font-weight:700;color:var(--amber);text-transform:uppercase;letter-spacing:.8px;margin-bottom:.6rem;padding-bottom:.4rem;border-bottom:1px solid var(--border)}
        .ref-body p{font-family:'IBM Plex Mono',monospace;font-size:11.5px;color:var(--dim);line-height:1.9}
        .ref-body p span{color:var(--cyan)}

        /* ── Submit ── */
        .btn-calc{width:100%;padding:14px;background:var(--accent);border:none;border-radius:11px;color:var(--bg);font-family:'DM Sans',sans-serif;font-weight:700;font-size:16px;letter-spacing:1.5px;text-transform:uppercase;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:.2s all}
        .btn-calc:hover{background:var(--accent-h);transform:translateY(-1px);box-shadow:0 8px 26px rgba(147,138,135,.3)}

        ::-webkit-scrollbar{width:6px}::-webkit-scrollbar-track{background:var(--bg)}::-webkit-scrollbar-thumb{background:var(--border);border-radius:6px}
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
                <div class="nav-sub">Financial Model · Petroleum Economics</div>
            </div>
        </div>
    </div>
    <div class="nav-tag">v2.1</div>
</nav>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="sidebar">
    <span class="sb-label">Menu</span>
    <a class="sb-item active" href="index.php"><i class="bi bi-sliders2"></i> Input Parameter</a>
    <a class="sb-item" href="project.php"><i class="bi bi-folder2-open"></i> Semua Proyek</a>
</aside>

<main class="main" id="mainContent">
<div class="page-body">

    <?php if ($loadedProject): ?>
    <div class="edit-banner">
        <div class="eb-left">
            <i class="bi bi-pencil-square eb-icon"></i>
            <div>
                <div class="eb-title">Mengedit: <?= htmlspecialchars($loadedProject['name']) ?></div>
                <div class="eb-sub">Parameter diisi dari proyek tersimpan. Ubah dan hitung ulang kapan saja.</div>
            </div>
        </div>
        <a href="index.php" class="btn-clear"><i class="bi bi-x-lg"></i> Form Kosong</a>
    </div>
    <?php endif; ?>

    <div class="page-hdr">
        <h1><?= $loadedProject ? 'Edit Parameter Proyek' : 'Input Parameter Keekonomian' ?></h1>
        <p>Isi seluruh parameter di bawah, lalu klik <strong style="color:var(--amber)">Hitung Keekonomian</strong> untuk menghasilkan tabel FM.</p>
    </div>

    <form action="calculate.php" method="POST">
        <input type="hidden" name="project_id"   value="<?= htmlspecialchars($loadedProject['id'] ?? '') ?>">
        <input type="hidden" name="project_name" value="<?= htmlspecialchars($loadedProject['name'] ?? '') ?>">

        <!-- Nama Proyek -->
        <div class="proj-name-field">
            <i class="bi bi-tag-fill pnf-icon"></i>
            <div class="pnf-group">
                <div class="pnf-label">Nama Proyek <span style="color:var(--muted)">(opsional)</span></div>
                <input type="text" name="project_name" class style="background:var(--in-bg);border:1px solid var(--in-bdr);color:var(--text);border-radius:8px;padding:9px 12px;font-family:'IBM Plex Mono',monospace;font-size:13px;width:100%;transition:.18s all"
                       placeholder="Contoh: Lapangan Blok A – Skenario Base"
                       value="<?= htmlspecialchars($loadedProject['name'] ?? '') ?>"
                       onfocus="this.style.borderColor='var(--amber)';this.style.boxShadow='0 0 0 3px rgba(147,138,135,.12)'"
                       onblur="this.style.borderColor='var(--in-bdr)';this.style.boxShadow='none'">
            </div>
        </div>

        <!-- 1. Investasi -->
        <div class="scard">
            <div class="sc-title"><i class="bi bi-bank2"></i> Data Investasi Awal (Tahun 0)</div>
            <div class="g3">
                <div class="fg"><label>Jangka Waktu Proyek (Tahun)</label><input type="number" name="jangka_waktu" placeholder="20" value="<?= val($p,'jangka_waktu','20') ?>" min="1" max="50" required></div>
                <div class="fg"><label>Capital ($M)</label><input type="number" name="capital" placeholder="13000" value="<?= val($p,'capital','13000') ?>" step="0.01" required></div>
                <div class="fg"><label>Non-Capital ($M)</label><input type="number" name="non_capital" placeholder="8000" value="<?= val($p,'non_capital','8000') ?>" step="0.01" required></div>
            </div>
            <p class="hint">Total Investasi = Capital + Non-Capital → NCF Tahun 0 = −Total Investasi</p>
        </div>

        <!-- 2. Produksi -->
        <div class="scard">
            <div class="sc-title"><i class="bi bi-droplet-fill"></i> Profil Produksi</div>
            <div class="g4">
                <div class="fg"><label>Produksi Thn ke-1 (Mbbl/thn)</label><input type="number" name="prod_thn_1" placeholder="175" value="<?= val($p,'prod_thn_1','175') ?>" step="0.01" required></div>
                <div class="fg"><label>Laju Kenaikan (%/thn)</label><input type="number" name="laju_kenaikan" placeholder="11.3" value="<?= val($p,'laju_kenaikan','11.3') ?>" step="0.01" min="0"><p class="hint">Fase build-up</p></div>
                <div class="fg"><label>Tahun Puncak Produksi</label><input type="number" name="tahun_puncak" placeholder="3" value="<?= val($p,'tahun_puncak','3') ?>" min="1" required><p class="hint">Decline mulai thn berikutnya</p></div>
                <div class="fg"><label>Decline Rate (%/thn)</label><input type="number" name="decline" placeholder="3" value="<?= val($p,'decline','3') ?>" step="0.01" min="0" required></div>
            </div>
        </div>

        <!-- 3. Revenue & Opex -->
        <div class="scard">
            <div class="sc-title"><i class="bi bi-currency-dollar"></i> Revenue &amp; Biaya Operasi</div>
            <div class="g4">
                <div class="fg"><label>Harga Minyak ($/bbl)</label><input type="number" name="harga_minyak" placeholder="32" value="<?= val($p,'harga_minyak','32') ?>" step="0.01" required></div>
                <div class="fg"><label>Base Opex ($M/thn)</label><input type="number" name="opex_base" placeholder="180" value="<?= val($p,'opex_base','180') ?>" step="0.01" required></div>
                <div class="fg"><label>Eskalasi Opex (%/thn)</label><input type="number" name="opex_eskalasi" placeholder="2.5" value="<?= val($p,'opex_eskalasi','2.5') ?>" step="0.01" min="0"><p class="hint">0 = flat sepanjang proyek</p></div>
                <div class="fg"><label>Thn Mulai Eskalasi Opex</label><input type="number" name="tahun_mulai_eskalasi" placeholder="4" value="<?= val($p,'tahun_mulai_eskalasi','4') ?>" min="1"></div>
            </div>
        </div>

        <!-- 4. Pajak & Depresiasi -->
        <div class="scard">
            <div class="sc-title"><i class="bi bi-percent"></i> Pajak, Depresiasi &amp; Diskonto</div>
            <div class="g3">
                <div class="fg"><label>Tax Rate (%)</label><input type="number" name="pajak" placeholder="51" value="<?= val($p,'pajak','51') ?>" step="0.01" min="0" max="100" required></div>
                <div class="fg"><label>Discount Rate / WACC (%)</label><input type="number" name="discount_rate" placeholder="10" value="<?= val($p,'discount_rate','10') ?>" step="0.01" min="0" required><p class="hint">Untuk perhitungan NPV</p></div>
                <div class="fg">
                    <label>Metode Depresiasi (Di)</label>
                    <select name="metode_depresiasi">
                        <?php $sel=$p['metode_depresiasi']??'straight_line';$opts=['straight_line'=>'Garis Lurus (Straight-Line)','declining_balance'=>'Saldo Menurun (Declining Balance)','sum_years_digits'=>'Jumlah Angka Tahun (SYD)'];foreach($opts as $v=>$l):?>
                        <option value="<?=$v?>" <?=$sel===$v?'selected':''?>><?=$l?></option>
                        <?php endforeach;?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Referensi Formula (collapsible) -->
        <div class="ref-card" id="refCard">
            <div class="ref-header" id="refToggle">
                <div class="ref-header-left"><i class="bi bi-info-circle"></i> Referensi Formula &amp; Definisi</div>
                <i class="bi bi-chevron-down ref-chevron"></i>
            </div>
            <div class="ref-body">
                <div class="ref-grid">
                    <div>
                        <div class="ref-col-title">Alur Perhitungan</div>
                        <p>
                            <span>Income</span> = Produksi × Harga Minyak<br>
                            <span>Taxable Income</span><br>&nbsp;= Income − Opex − Di<br>
                            <span>Tax</span> = Taxable × Tarif<br>
                            <span>NCF</span> = Taxable − Tax<br>
                            <span>NPV</span> = Σ NCF / (1+r)ᵗ
                        </p>
                    </div>
                    <div>
                        <div class="ref-col-title">Metode Depresiasi (Di)</div>
                        <p>
                            <span>Straight-Line</span><br>Di = TotalInv / N<br><br>
                            <span>Declining Balance</span><br>Di = NilaiBuku × (2/N)<br><br>
                            <span>Sum of Years Digits</span><br>Di = [(N−t+1)/Σt] × TotalInv
                        </p>
                    </div>
                    <div>
                        <div class="ref-col-title">Profil Produksi</div>
                        <p>
                            <span>Build-up</span><br>Pₜ = P₁ × (1+g)^(t−1)<br>untuk t ≤ tPuncak<br><br>
                            <span>Decline</span><br>Pₜ = Pmax × (1−d)^(t−tPeak)<br>untuk t &gt; tPuncak
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <button type="submit" class="btn-calc">
            <i class="bi bi-lightning-charge-fill"></i>
            <?= $loadedProject ? 'Hitung Ulang' : 'Hitung Keekonomian' ?>
        </button>
    </form>
</div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const sidebar  = document.getElementById('sidebar');
    const main     = document.getElementById('mainContent');
    const overlay  = document.getElementById('sidebarOverlay');
    const toggle   = document.getElementById('sidebarToggle');
    const refCard  = document.getElementById('refCard');
    const refToggle= document.getElementById('refToggle');

    function closeSidebar(){ sidebar.classList.add('collapsed'); main.classList.add('expanded'); overlay.classList.remove('show'); }
    function openSidebar() { sidebar.classList.remove('collapsed'); main.classList.remove('expanded'); overlay.classList.add('show'); }

    toggle.addEventListener('click', () => sidebar.classList.contains('collapsed') ? openSidebar() : closeSidebar());
    overlay.addEventListener('click', closeSidebar);

    refToggle.addEventListener('click', () => refCard.classList.toggle('open'));
</script>
</body>
</html>