<?php

/* EkoMigas Pro - storage.php
   Penyimpanan proyek berbasis JSON file (prosedural) */

define('PROJECTS_DIR', __DIR__ . '/projects/');
if (!is_dir(PROJECTS_DIR)) {
    mkdir(PROJECTS_DIR, 0755, true);
}

function sanitizeProjectId(string $id): string
{
    return preg_replace('/[^a-zA-Z0-9_\-]/', '', $id);
}

function newProjectId(): string
{
    return 'proj_' . bin2hex(random_bytes(6));
}

function saveProject(string $id, string $name, array $params): bool
{
    $id = sanitizeProjectId($id);
    if ($id === '') {
        return false;
    }

    $file = PROJECTS_DIR . $id . '.json';
    $existing = file_exists($file)
        ? json_decode(file_get_contents($file), true)
        : null;

    $data = [
        'id' => $id,
        'name' => strip_tags(trim($name)) ?: 'Proyek Tanpa Nama',
        'params' => $params,
        'created_at' => $existing['created_at'] ?? date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ];

    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return false;
    }

    return file_put_contents($file, $json) !== false;
}

function loadProject(string $id): ?array
{
    $id = sanitizeProjectId($id);
    $file = PROJECTS_DIR . $id . '.json';
    if (!file_exists($file)) {
        return null;
    }

    $contents = file_get_contents($file);
    if ($contents === false) {
        return null;
    }

    $data = json_decode($contents, true);
    return is_array($data) ? $data : null;
}

function listProjects(): array
{
    $files = glob(PROJECTS_DIR . '*.json') ?: [];
    $projects = [];

    foreach ($files as $file) {
        $contents = file_get_contents($file);
        if ($contents === false) {
            continue;
        }

        $data = json_decode($contents, true);
        if (is_array($data)) {
            $projects[] = $data;
        }
    }

    usort($projects, fn($a, $b) => strcmp($b['updated_at'] ?? '', $a['updated_at'] ?? ''));
    return $projects;
}

function deleteProject(string $id): bool
{
    $id = sanitizeProjectId($id);
    $file = PROJECTS_DIR . $id . '.json';
    return file_exists($file) && unlink($file);
}

function fmParamKeys(): array
{
    return [
        'jangka_waktu', 'capital', 'non_capital', 'prod_thn_1', 'prod_thn_2', 'prod_thn_3', 'prod_thn_4',
        'laju_kenaikan', 'tahun_puncak', 'decline', 'harga_minyak',
        'opex_base', 'opex_eskalasi', 'tahun_mulai_eskalasi',
        'pajak', 'discount_rate', 'metode_depresiasi',
    ];
}

function extractFmParams(array $post): array
{
    $params = [];
    foreach (fmParamKeys() as $key) {
        $params[$key] = $post[$key] ?? '';
    }
    return $params;
}
