<?php
/**
 * SITAPSI - Pelaporan & Rekap
 * Hub untuk memilih: Rekapitulasi atau Rapor Karakter
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/session_check.php';

requireAdmin();

$tahun_aktif = fetchOne("SELECT id_tahun, nama_tahun, semester_aktif FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pelaporan & Rekap - SITAPSI</title>
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
            <h1 class="text-2xl font-bold text-gray-800">Pelaporan & Rekap</h1>
            <p class="text-sm text-gray-500">Pilih jenis laporan yang ingin dibuat</p>
        </div>

        <div class="p-6">
            
            <!-- Info Header -->
            <div class="bg-gradient-to-r from-navy to-blue-800 text-white rounded-xl shadow-lg p-6 mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold mb-2">Sistem Pelaporan</h2>
                        <p class="text-blue-200">Tahun Ajaran <?= $tahun_aktif['nama_tahun'] ?> • Semester <?= $tahun_aktif['semester_aktif'] ?></p>
                    </div>
                    <div class="text-6xl">📊</div>
                </div>
            </div>

            <!-- 2 Pilihan Laporan -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                
                <!-- Rekapitulasi Kelas -->
                <a href="rekapitulasi_kelas.php" class="block group">
                    <div class="bg-white rounded-xl shadow-lg p-8 transition-all transform hover:scale-105 hover:shadow-2xl border-l-4 border-blue-600">
                        <div class="flex items-center justify-between mb-6">
                            <div class="text-6xl">📋</div>
                            <div class="bg-blue-100 rounded-full p-4">
                                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-3 group-hover:text-blue-600 transition-colors">
                            Rekapitulasi Kelas
                        </h3>
                        <p class="text-gray-600 mb-6">
                            Laporan poin pelanggaran siswa per kelas dalam bentuk matriks (Leger). 
                            Menampilkan total poin setiap kategori dan status SP.
                        </p>
                        <div class="space-y-2 text-sm text-gray-700">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <span>Tabel matriks per kelas</span>
                            </div>
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <span>Export ke Excel</span>
                            </div>
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <span>Real-time data</span>
                            </div>
                        </div>
                        <div class="mt-6 flex items-center text-blue-600 font-semibold group-hover:translate-x-2 transition-transform">
                            <span>Buka Rekapitulasi</span>
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </div>
                    </div>
                </a>

                <!-- Rapor Karakter -->
                <a href="rapor_karakter.php" class="block group">
                    <div class="bg-white rounded-xl shadow-lg p-8 transition-all transform hover:scale-105 hover:shadow-2xl border-l-4 border-purple-600">
                        <div class="flex items-center justify-between mb-6">
                            <div class="text-6xl">🎓</div>
                            <div class="bg-purple-100 rounded-full p-4">
                                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-3 group-hover:text-purple-600 transition-colors">
                            Rapor Karakter Akhir
                        </h3>
                        <p class="text-gray-600 mb-6">
                            Laporan akhir semester dengan konversi poin menjadi nilai predikat (A/B/C/D) 
                            untuk 3 aspek karakter siswa.
                        </p>
                        <div class="space-y-2 text-sm text-gray-700">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <span>Konversi nilai A/B/C/D</span>
                            </div>
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <span>Export ke PDF</span>
                            </div>
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <span>Per siswa atau per kelas</span>
                            </div>
                        </div>
                        <div class="mt-6 flex items-center text-purple-600 font-semibold group-hover:translate-x-2 transition-transform">
                            <span>Buat Rapor Karakter</span>
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </div>
                    </div>
                </a>

            </div>

        </div>

    </div>

</div>

</body>
</html>