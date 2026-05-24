<?php
/* ══════════════════════════════════════════════════════════
   EkoMigas Pro – storage.php
   Manajemen proyek via JSON file storage
   ══════════════════════════════════════════════════════════ */

define('PROJECTS_DIR', __DIR__ . '/projects/');

if (!is_dir(PROJECTS_DIR)) {
    mkdir(PROJECTS_DIR, 0755, true);
}

/** Sanitasi ID agar aman sebagai nama file */
function sanitizeProjectId(string $id): string {
    return preg_replace('/[^a-zA-Z0-9_\-]/', '', $id);
}

/** Generate ID unik */
function newProjectId(): string {
    return 'proj_' . bin2hex(random_bytes(6));
}

/** Simpan / perbarui proyek */
function saveProject(string $id, string $name, array $params): bool {
    $id   = sanitizeProjectId($id);
    if (empty($id)) return false;

    $file     = PROJECTS_DIR . $id . '.json';
    $existing = file_exists($file)
        ? json_decode(file_get_contents($file), true)
        : null;

    $data = [
        'id'         => $id,
        'name'       => strip_tags(trim($name)) ?: 'Proyek Tanpa Nama',
        'params'     => $params,
        'created_at' => $existing['created_at'] ?? date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ];

    return (bool) file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

/** Muat proyek berdasarkan ID */
function loadProject(string $id): ?array {
    $id   = sanitizeProjectId($id);
    $file = PROJECTS_DIR . $id . '.json';
    if (!file_exists($file)) return null;
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : null;
}

/** Daftar semua proyek, terbaru di atas */
function listProjects(): array {
    $files    = glob(PROJECTS_DIR . '*.json') ?: [];
    $projects = [];
    foreach ($files as $f) {
        $d = json_decode(file_get_contents($f), true);
        if (is_array($d)) $projects[] = $d;
    }
    usort($projects, fn($a, $b) =>
        strcmp($b['updated_at'] ?? '', $a['updated_at'] ?? ''));
    return $projects;
}

/** Hapus proyek */
function deleteProject(string $id): bool {
    $id   = sanitizeProjectId($id);
    $file = PROJECTS_DIR . $id . '.json';
    return file_exists($file) && unlink($file);
}

/** Kunci parameter FM yang valid */
function fmParamKeys(): array {
    return [
        'jangka_waktu', 'capital', 'non_capital', 'prod_thn_1',
        'laju_kenaikan', 'tahun_puncak', 'decline', 'harga_minyak',
        'opex_base', 'opex_eskalasi', 'tahun_mulai_eskalasi',
        'pajak', 'discount_rate', 'metode_depresiasi',
    ];
}

/** Ambil params FM dari array POST */
function extractFmParams(array $post): array {
    $params = [];
    foreach (fmParamKeys() as $k) {
        $params[$k] = $post[$k] ?? '';
    }
    return $params;
}