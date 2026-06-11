<?php
require_once __DIR__ . '/storage.php';
$page = 'index';

$loaded = null;
$p = [];
if (!empty($_GET['load'])) {
    $loaded = loadProject($_GET['load']);
    if ($loaded) $p = $loaded['params'];
}
function v(array $p, string $k, string $d = ''): string {
    return htmlspecialchars($p[$k] ?? $d);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $loaded ? htmlspecialchars($loaded['name']).' – ' : '' ?>Input Parameter – EkoMigas Pro</title>
    <link rel="stylesheet" href="style.css">
<style>
/* ── Edit banner ────────────────────── */
.edit-banner {
    display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    background: var(--accent-bg); border: 1px solid var(--accent-bdr);
    border-radius: var(--radius); padding: 13px 18px; margin-bottom: 1.5rem;
    animation: fadeUp .4s ease both;
}
.eb-info { display: flex; align-items: center; gap: 11px; }
.eb-dot  { width: 7px; height: 7px; border-radius: 50%; background: var(--accent); flex-shrink: 0; }
.eb-name { font-size: 14px; font-weight: 500; color: var(--text); }
.eb-sub  { font-size: 11.5px; color: var(--muted); }

/* ── Project name row ────────────────── */
.proj-name-wrap {
    display: flex; align-items: center; gap: 12px;
    background: var(--card); border: 1px solid var(--border);
    border-radius: var(--radius); padding: 14px 18px;
    margin-bottom: 1.25rem; transition: border-color .18s;
}
.proj-name-wrap:focus-within { border-color: var(--accent); }
.pnw-icon { font-size: 15px; color: var(--accent-dim); flex-shrink: 0; }
.pnw-label { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: var(--muted); margin-bottom: .2rem; }
.pnw-input {
    flex: 1; background: transparent; border: none !important; outline: none !important;
    box-shadow: none !important; font-family: 'DM Sans', sans-serif;
    font-size: 16px; font-weight: 300; color: var(--text); padding: 0 !important;
    letter-spacing: -.01em; width: 100%;
}
.pnw-input::placeholder { color: var(--muted); font-style: italic; }

/* ── Form sections ────────────────────── */
.form-sec {
    background: var(--card); border: 1px solid var(--border);
    border-radius: var(--radius); padding: 1.5rem 1.75rem;
    margin-bottom: 1.1rem; position: relative;
}
.form-sec::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 1px;
    background: linear-gradient(90deg, var(--accent) 0%, transparent 55%);
    opacity: 0; transition: opacity .3s;
}
.form-sec:focus-within::before { opacity: 1; }

.fsec-hdr {
    display: flex; align-items: center; gap: 12px;
    padding-bottom: 1.1rem; margin-bottom: 1.25rem;
    border-bottom: 1px solid var(--border);
}
.fsec-num {
    font-family: 'DM Mono', monospace; font-size: 11px; font-weight: 300;
    color: var(--muted); letter-spacing: 1px;
}
.fsec-title {
    font-size: 11.5px; font-weight: 600; text-transform: uppercase;
    letter-spacing: 1.2px; color: var(--accent);
}

/* ── Collapsible formula ref ──────────── */
.ref-block { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); margin-bottom: 1.25rem; overflow: hidden; }
.ref-toggle {
    display: flex; align-items: center; justify-content: space-between;
    padding: 1rem 1.5rem; cursor: pointer; user-select: none;
    transition: background .18s;
}
.ref-toggle:hover { background: rgba(90,104,130,.04); }
.ref-toggle-left { display: flex; align-items: center; gap: 9px; font-size: 12px; font-weight: 500; color: var(--dim); letter-spacing: .3px; }
.ref-toggle-left i { color: var(--accent); font-size: 14px; }
.ref-chevron { color: var(--muted); font-size: 13px; transition: transform .25s; }
.ref-block.open .ref-chevron { transform: rotate(180deg); }
.ref-body { display: none; border-top: 1px solid var(--border); padding: 1.25rem 1.5rem; }
.ref-block.open .ref-body { display: block; }
.ref-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.25rem; }
.ref-col h4 { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--accent); margin-bottom: .65rem; padding-bottom: .45rem; border-bottom: 1px solid var(--border); }
.ref-col p  { font-family: 'DM Mono', monospace; font-size: 11.5px; color: var(--muted); line-height: 1.95; }
.ref-col p span { color: var(--text2); }
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

    <!-- Edit banner -->
    <?php if ($loaded): ?>
    <div class="edit-banner">
        <div class="eb-info">
            <div class="eb-dot"></div>
            <div>
                <div class="eb-name"><?= htmlspecialchars($loaded['name']) ?></div>
                <div class="eb-sub">Mengedit proyek tersimpan · Perbarui dan hitung ulang kapan saja</div>
            </div>
        </div>
        <a href="index.php" class="btn btn-ghost btn-sm"><i class="bi bi-x-lg"></i> Form Kosong</a>
    </div>
    <?php endif; ?>

    <!-- Page header -->
    <div class="page-hdr">
        <h1><?= $loaded ? 'Edit Parameter Proyek' : 'Input Parameter <strong>Keekonomian</strong>' ?></h1>
        <p>Isi seluruh parameter di bawah, lalu klik <span style="color:var(--accent)">Hitung Keekonomian</span> untuk menghasilkan tabel FM lengkap.</p>
    </div>

    <form action="calculate.php" method="POST">
        <input type="hidden" name="project_id"   value="<?= v($loaded??[],'id') ?>">

        <!-- Project name -->
        <div class="proj-name-wrap">
            <i class="bi bi-tag-fill pnw-icon"></i>
            <div style="flex:1">
                <div class="pnw-label">Nama Proyek <span style="color:var(--muted);font-weight:400;text-transform:none;letter-spacing:0">(opsional)</span></div>
                <input type="text" name="project_name" class="pnw-input"
                       placeholder="Contoh: Lapangan Blok A — Skenario Base"
                       value="<?= htmlspecialchars($loaded['name'] ?? '') ?>">
            </div>
        </div>

        <!-- 01 · Investasi -->
        <div class="form-sec">
            <div class="fsec-hdr">
                <span class="fsec-num">01</span>
                <span class="fsec-title">Data Investasi Awal (Tahun 0)</span>
            </div>
            <div class="g3">
                <div class="fg"><label>Jangka Waktu (Tahun)</label><input type="number" name="jangka_waktu" value="<?= v($p,'jangka_waktu','20') ?>" min="1" max="50" required></div>
                <div class="fg"><label>Capital ($M)</label><input type="number" name="capital" value="<?= v($p,'capital','13000') ?>" step="0.01" required></div>
                <div class="fg"><label>Non-Capital ($M)</label><input type="number" name="non_capital" value="<?= v($p,'non_capital','8000') ?>" step="0.01" required></div>
            </div>
            <p class="hint">NCF Tahun 0 = −(Capital + Non-Capital)</p>
        </div>

        <!-- 02 · Produksi -->
        <div class="form-sec">
            <div class="fsec-hdr">
                <span class="fsec-num">02</span>
                <span class="fsec-title">Profil Produksi</span>
            </div>
            <div class="g4">
                <div class="fg"><label>Produksi Tahun ke-1 (Mbbl/thn)</label><input type="number" name="prod_thn_1" value="<?= v($p,'prod_thn_1','175') ?>" step="0.01" required></div>
                <div class="fg"><label>Produksi Tahun ke-2 (Mbbl/thn)</label><input type="number" name="prod_thn_2" value="<?= v($p,'prod_thn_2','190') ?>" step="0.01"></div>
                <div class="fg"><label>Produksi Tahun ke-3 (Mbbl/thn)</label><input type="number" name="prod_thn_3" value="<?= v($p,'prod_thn_3','205') ?>" step="0.01"></div>
                <div class="fg"><label>Produksi Tahun ke-4 (Mbbl/thn)</label><input type="number" name="prod_thn_4" value="<?= v($p,'prod_thn_4','220') ?>" step="0.01"></div>
            </div>
            <p class="hint">Tahun 1–4 bisa Anda sesuaikan manual; tahun 5 ke atas akan mengikuti pola decline otomatis.</p>
            <div class="g4" style="margin-top: .8rem;">
                <div class="fg"><label>Laju Kenaikan (%/thn)</label><input type="number" name="laju_kenaikan" value="<?= v($p,'laju_kenaikan','11.3') ?>" step="0.01" min="0"><p class="hint">Fase build-up</p></div>
                <div class="fg"><label>Tahun Puncak Produksi</label><input type="number" name="tahun_puncak" value="<?= v($p,'tahun_puncak','3') ?>" min="1" required><p class="hint">Decline mulai thn berikutnya</p></div>
                <div class="fg"><label>Decline Rate (%/thn)</label><input type="number" name="decline" value="<?= v($p,'decline','3') ?>" step="0.01" min="0" required></div>
                <div class="fg"></div>
            </div>
        </div>

        <!-- 03 · Revenue & Opex -->
        <div class="form-sec">
            <div class="fsec-hdr">
                <span class="fsec-num">03</span>
                <span class="fsec-title">Revenue &amp; Biaya Operasi</span>
            </div>
            <div class="g4">
                <div class="fg"><label>Harga Minyak ($/bbl)</label><input type="number" name="harga_minyak" value="<?= v($p,'harga_minyak','32') ?>" step="0.01" required></div>
                <div class="fg"><label>Base Opex ($M/thn)</label><input type="number" name="opex_base" value="<?= v($p,'opex_base','180') ?>" step="0.01" required></div>
                <div class="fg"><label>Eskalasi Opex (%/thn)</label><input type="number" name="opex_eskalasi" value="<?= v($p,'opex_eskalasi','2.5') ?>" step="0.01" min="0"><p class="hint">0 = flat</p></div>
                <div class="fg"><label>Thn Mulai Eskalasi Opex</label><input type="number" name="tahun_mulai_eskalasi" value="<?= v($p,'tahun_mulai_eskalasi','4') ?>" min="1"></div>
            </div>
        </div>

        <!-- 04 · Pajak & Depresiasi -->
        <div class="form-sec">
            <div class="fsec-hdr">
                <span class="fsec-num">04</span>
                <span class="fsec-title">Pajak, Depresiasi &amp; Diskonto</span>
            </div>
            <div class="g3">
                <div class="fg"><label>Tax Rate (%)</label><input type="number" name="pajak" value="<?= v($p,'pajak','51') ?>" step="0.01" min="0" max="100" required></div>
                <div class="fg"><label>Discount Rate / WACC (%)</label><input type="number" name="discount_rate" value="<?= v($p,'discount_rate','10') ?>" step="0.01" min="0" required><p class="hint">Untuk perhitungan NPV</p></div>
                <div class="fg">
                    <label>Metode Depresiasi (Di)</label>
                    <select name="metode_depresiasi">
                        <?php $sel=$p['metode_depresiasi']??'straight_line';
                        foreach(['straight_line'=>'Garis Lurus (Straight-Line)','declining_balance'=>'Saldo Menurun (Declining Balance)','sum_years_digits'=>'Jumlah Angka Tahun (SYD)'] as $val=>$lbl): ?>
                        <option value="<?=$val?>" <?=$sel===$val?'selected':''?>><?=$lbl?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Formula reference (collapsible) -->
        <div class="ref-block" id="refBlock">
            <div class="ref-toggle" onclick="document.getElementById('refBlock').classList.toggle('open')">
                <div class="ref-toggle-left"><i class="bi bi-info-circle"></i> Referensi Formula &amp; Definisi</div>
                <i class="bi bi-chevron-down ref-chevron"></i>
            </div>
            <div class="ref-body">
                <div class="ref-grid">
                    <div class="ref-col">
                        <h4>Alur Perhitungan</h4>
                        <p><span>Income</span> = Produksi × Harga<br><span>Taxable Income</span><br>&nbsp;= Income − Opex − Di<br><span>Tax</span> = Taxable × Tarif<br><span>NCF</span> = Taxable − Tax<br><span>NPV</span> = Σ NCF / (1+r)ᵗ</p>
                    </div>
                    <div class="ref-col">
                        <h4>Metode Depresiasi</h4>
                        <p><span>Straight-Line</span><br>Di = TotalInv / N<br><br><span>Declining Balance</span><br>Di = NilaiBuku × (2/N)<br><br><span>Sum of Years Digits</span><br>Di = [(N−t+1)/Σt] × TotalInv</p>
                    </div>
                    <div class="ref-col">
                        <h4>Profil Produksi</h4>
                        <p><span>Build-up</span><br>Pₜ = P₁ × (1+g)^(t−1)<br>untuk t ≤ tPuncak<br><br><span>Decline</span><br>Pₜ = Pmax × (1−d)^(t−tPeak)<br>untuk t &gt; tPuncak</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <button type="submit" class="btn btn-primary btn-full">
            <i class="bi bi-lightning-charge-fill"></i>
            <?= $loaded ? 'Hitung Ulang' : 'Hitung Keekonomian' ?>
        </button>
    </form>

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
</script>
</body>
</html>