<?php
/**
 * SITAPSI - Manajemen Aturan
 * Aturan Poin & Threshold SP
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

// Ambil daftar kategori
$kategori_list = fetchAll("SELECT id_kategori, nama_kategori FROM tb_kategori_pelanggaran ORDER BY id_kategori");

// Ambil aturan SP
$aturan_sp = fetchAll("
    SELECT 
        a.id_aturan,
        a.level_sp,
        a.batas_bawah_poin,
        a.sanksi_terkait,
        k.nama_kategori
    FROM tb_aturan_sp a
    JOIN tb_kategori_pelanggaran k ON a.id_kategori = k.id_kategori
    ORDER BY k.id_kategori, a.batas_bawah_poin
");

// Ambil jenis pelanggaran
$pelanggaran_list = fetchAll("
    SELECT 
        jp.id_jenis,
        jp.nama_pelanggaran,
        jp.poin_default,
        jp.sanksi_default,
        k.nama_kategori,
        jp.sub_kategori
    FROM tb_jenis_pelanggaran jp
    JOIN tb_kategori_pelanggaran k ON jp.id_kategori = k.id_kategori
    ORDER BY k.id_kategori, jp.sub_kategori, jp.nama_pelanggaran
");

$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Aturan - SITAPSI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { 'navy': '#000080' }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">

<div class="flex h-screen overflow-hidden">
    
    <?php include '../../includes/sidebar_admin.php'; ?>

    <div class="flex-1 overflow-auto bg-gray-100">
        
        <div class="bg-white shadow-sm border-b px-6 py-4 sticky top-0 z-30">
            <h1 class="text-2xl font-bold text-gray-800">Manajemen Aturan</h1>
            <p class="text-sm text-gray-500">Pengaturan poin pelanggaran & threshold SP</p>
        </div>

        <div class="p-6 space-y-6">

            <?php if ($success): ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded">
                <p class="text-green-700 font-medium"><?= htmlspecialchars($success) ?></p>
            </div>
            <?php endif; ?>

            <!-- Aturan SP -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="p-4 border-b font-bold text-gray-700 flex justify-between items-center">
                    <span>⚖️ Aturan Threshold Surat Peringatan</span>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <?php 
                        $grouped_sp = [];
                        foreach ($aturan_sp as $a) {
                            $grouped_sp[$a['nama_kategori']][] = $a;
                        }
                        
                        $colors = [
                            'KELAKUAN' => ['bg' => 'bg-red-50', 'border' => 'border-red-500', 'text' => 'text-red-700'],
                            'KERAJINAN' => ['bg' => 'bg-blue-50', 'border' => 'border-blue-500', 'text' => 'text-blue-700'],
                            'KERAPIAN' => ['bg' => 'bg-yellow-50', 'border' => 'border-yellow-500', 'text' => 'text-yellow-700']
                        ];
                        
                        foreach ($grouped_sp as $kategori => $aturan):
                            $color = $colors[$kategori] ?? ['bg' => 'bg-gray-50', 'border' => 'border-gray-500', 'text' => 'text-gray-700'];
                        ?>
                        <div class="<?= $color['bg'] ?> border-l-4 <?= $color['border'] ?> p-4 rounded-lg">
                            <h3 class="font-bold <?= $color['text'] ?> mb-3"><?= $kategori ?></h3>
                            <div class="space-y-2">
                                <?php foreach ($aturan as $a): ?>
                                <div class="bg-white p-3 rounded border">
                                    <div class="flex justify-between items-center">
                                        <span class="font-bold text-gray-800"><?= $a['level_sp'] ?></span>
                                        <span class="text-sm font-medium text-gray-600">&ge; <?= $a['batas_bawah_poin'] ?> poin</span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Daftar Jenis Pelanggaran -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="p-4 border-b font-bold text-gray-700">
                    📋 Daftar Jenis Pelanggaran
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                            <tr>
                                <th class="p-4">Kategori</th>
                                <th class="p-4">Sub Kategori</th>
                                <th class="p-4">Nama Pelanggaran</th>
                                <th class="p-4">Poin</th>
                                <th class="p-4">Sanksi Default</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach($pelanggaran_list as $p): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="p-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium
                                        <?= $p['nama_kategori'] === 'KELAKUAN' ? 'bg-red-100 text-red-800' : '' ?>
                                        <?= $p['nama_kategori'] === 'KERAJINAN' ? 'bg-blue-100 text-blue-800' : '' ?>
                                        <?= $p['nama_kategori'] === 'KERAPIAN' ? 'bg-yellow-100 text-yellow-800' : '' ?>">
                                        <?= $p['nama_kategori'] ?>
                                    </span>
                                </td>
                                <td class="p-4 text-gray-600"><?= htmlspecialchars($p['sub_kategori']) ?></td>
                                <td class="p-4 font-medium text-gray-800"><?= htmlspecialchars($p['nama_pelanggaran']) ?></td>
                                <td class="p-4">
                                    <span class="px-3 py-1 bg-gray-100 rounded-full font-bold text-gray-800">
                                        <?= $p['poin_default'] ?>
                                    </span>
                                </td>
                                <td class="p-4 text-gray-600 text-xs"><?= htmlspecialchars($p['sanksi_default']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <h4 class="font-bold text-blue-800 mb-1">ℹ️ Informasi Penting</h4>
                        <p class="text-sm text-blue-700">
                            Perubahan aturan poin dan SP dapat dilakukan langsung di database. 
                            Fitur edit via UI akan dikembangkan di versi selanjutnya.
                        </p>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>

</body>
</html>