<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'SuperAdmin'])) {
    header("Location: ../../index.php");
    exit;
}

// Panggil koneksi PDO (Mundur 2 folder dari core_admin/views/ ke folder root)
require_once '../../config/database.php';

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_setup'])) {
    
    // Tidak perlu mysqli_real_escape_string karena PDO menggunakan Parameter Binding (Jauh lebih aman!)
    $tahun_mulai = $_POST['tahun_mulai'];
    $tahun_selesai = $_POST['tahun_selesai'];
    $semester = $_POST['semester'];
    $nama_ta = $tahun_mulai . "/" . $tahun_selesai;

    try {
        // 1. Ubah status tahun ajaran lama menjadi 'Arsip' (sesuai enum database Anda)
        executeQuery("UPDATE tb_tahun_ajaran SET status = 'Arsip'");

        // 2. Masukkan Tahun Ajaran Baru menggunakan Prepared Statement
        // Kolom di db_sitapsi Anda: nama_tahun, semester_aktif, status
        $query_insert = "INSERT INTO tb_tahun_ajaran (nama_tahun, semester_aktif, status) VALUES (?, ?, 'Aktif')";
        executeQuery($query_insert, [$nama_ta, $semester]);
        
        // Jika sukses, lempar balik ke Launchpad!
        $_SESSION['pesan_sukses'] = "Inisialisasi Sistem Berhasil! Tahun Ajaran $nama_ta telah diaktifkan.";
        header("Location: dashboard.php");
        exit;
        
    } catch (PDOException $e) {
        $error_msg = "Gagal menyimpan: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Awal Sistem - Portal Terpadu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
    <script>
        // Auto-fill tahun selesai (Tahun Mulai + 1)
        function updateTahunSelesai() {
            const tahunMulai = document.getElementById('tahun_mulai').value;
            if (tahunMulai) {
                document.getElementById('tahun_selesai').value = parseInt(tahunMulai) + 1;
            }
        }
    </script>
</head>
<body class="bg-[#F8FAFC] min-h-screen flex items-center justify-center p-4 relative overflow-hidden">
    
    <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-blue-100 rounded-full blur-3xl opacity-50 -z-10"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-indigo-100 rounded-full blur-3xl opacity-50 -z-10"></div>

    <div class="bg-white rounded-3xl shadow-xl w-full max-w-lg border border-slate-100 overflow-hidden">
        <div class="bg-[#000080] p-8 text-center text-white relative">
            <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center mx-auto mb-4 border border-white/30">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path></svg>
            </div>
            <h1 class="text-2xl font-extrabold tracking-tight mb-2">Inisialisasi Sistem</h1>
            <p class="text-blue-100 text-sm">Selamat datang di Portal Terpadu. Atur Tahun Ajaran pertama Anda untuk memulai.</p>
        </div>

        <div class="p-8">
            <?php if (isset($error_msg)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6 text-sm">
                    <p class="font-bold">Error!</p>
                    <p><?= $error_msg ?></p>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="space-y-6">
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Tahun Mulai <span class="text-red-500">*</span></label>
                        <input type="number" name="tahun_mulai" id="tahun_mulai" value="<?= date('Y') ?>" required min="2020" max="2050" onkeyup="updateTahunSelesai()" onchange="updateTahunSelesai()"
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#000080] focus:border-transparent transition-all outline-none font-medium">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Tahun Selesai <span class="text-red-500">*</span></label>
                        <input type="number" name="tahun_selesai" id="tahun_selesai" value="<?= date('Y') + 1 ?>" required readonly
                            class="w-full px-4 py-3 bg-slate-100 border border-slate-200 rounded-xl text-slate-500 cursor-not-allowed font-medium">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Semester Aktif <span class="text-red-500">*</span></label>
                    <select name="semester" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#000080] focus:border-transparent transition-all outline-none font-medium appearance-none">
                        <option value="Ganjil">Semester Ganjil</option>
                        <option value="Genap">Semester Genap</option>
                    </select>
                </div>

                <div class="bg-blue-50 p-4 rounded-xl border border-blue-100 flex gap-3 mt-2">
                    <svg class="w-5 h-5 text-[#000080] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <p class="text-xs text-[#000080] leading-relaxed">
                        Pengaturan ini akan diterapkan ke seluruh modul (SITAPSI, E-Absensi, dll). Anda dapat mengubahnya nanti di menu Pengaturan Akademik.
                    </p>
                </div>

                <button type="submit" name="submit_setup" class="w-full bg-[#000080] text-white font-bold py-3.5 px-4 rounded-xl shadow-lg shadow-blue-900/30 hover:bg-blue-900 hover:shadow-xl hover:-translate-y-0.5 transition-all flex justify-center items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M5 13l4 4L19 7"></path></svg>
                    Simpan & Mulai Sistem
                </button>
            </form>
        </div>
    </div>
</body>
</html>