<?php
/**
 * SITAPSI - Halaman Login (UI GLOBAL PORTAL)
 * PENYESUAIAN: Shortcut Link ke Portal Wali Murid
 */
session_start();
require_once '../config/database.php';

// Cek jika sudah login, arahkan ke dashboard sesuai role
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'Admin' || $_SESSION['role'] === 'SuperAdmin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: guru/input_pelanggaran.php');
    }
    exit;
}

// Ambil pesan error dari auth.php jika ada
$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);

// Ambil daftar guru aktif untuk dropdown login guru
$guru_list = fetchAll("SELECT id_guru, nama_guru FROM tb_guru WHERE status = 'Aktif' ORDER BY nama_guru");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SITAPSI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .tab-active { color: #000080; border-bottom: 2px solid #000080; }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center bg-slate-50 p-4 py-10 relative overflow-x-hidden">
    
    <div class="absolute top-0 right-0 -mr-16 -mt-16 w-64 h-64 bg-blue-900/5 rounded-full blur-3xl animate-pulse"></div>
    <div class="absolute bottom-0 left-0 -ml-16 -mb-16 w-64 h-64 bg-blue-900/5 rounded-full blur-3xl"></div>

    <div class="w-full max-w-md bg-white border border-slate-200 shadow-xl shadow-blue-900/5 rounded-2xl relative z-10 transition-all duration-300 overflow-hidden mb-6">
        
        <div class="p-8 text-center space-y-4 pb-4">
            <div class="mx-auto w-20 h-20 flex items-center justify-center">
                <img src="../assets/img/logo.png" alt="Logo Santa Maria" class="w-full h-full object-contain">
            </div>
            <div>
                <h1 class="text-2xl font-extrabold text-[#000080] tracking-tight">SITAPSI</h1>
                <p class="text-slate-500 font-medium text-sm">Selamat datang, silakan masuk ke akun Anda</p>
            </div>
        </div>

        <div class="flex border-b border-slate-100">
            <button onclick="switchRole('guru')" id="btn-tab-guru" class="flex-1 py-3 text-sm font-bold transition-all tab-active">Portal Guru</button>
            <button onclick="switchRole('admin')" id="btn-tab-admin" class="flex-1 py-3 text-sm font-bold text-slate-400 transition-all">Administrator</button>
        </div>

        <div class="p-8 pt-6">
            <form action="../actions/auth.php" method="POST" id="form-login" class="space-y-5">
                <input type="hidden" name="login_type" id="login_type" value="guru">

                <?php if ($error): ?>
                <div class="bg-red-50 border border-red-100 text-red-600 px-4 py-3 rounded-xl text-sm font-medium text-center animate-in fade-in slide-in-from-top-1">
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <div id="dynamic-inputs" class="space-y-5">
                    <div id="guru-fields" class="space-y-5">
                        <div class="space-y-2">
                            <label class="text-slate-700 text-sm font-semibold ml-1">Pilih Nama Guru</label>
                            <select name="guru_id" class="w-full bg-white border border-slate-200 text-slate-900 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/10 h-12 rounded-xl px-4 transition-all outline-none text-sm font-medium cursor-pointer">
                                <option value="">-- Pilih Nama Anda --</option>
                                <?php foreach($guru_list as $g): ?>
                                    <option value="<?= $g['id_guru'] ?>"><?= htmlspecialchars($g['nama_guru']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-slate-700 text-sm font-semibold ml-1">PIN Akses (6 Digit)</label>
                            <input type="password" name="pin" maxlength="6" placeholder="Masukkan 6 digit PIN"
                                   class="w-full bg-white border border-slate-200 text-slate-900 placeholder:text-slate-400 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/10 h-12 rounded-xl px-4 transition-all outline-none font-mono tracking-widest">
                        </div>
                        <label class="flex items-center space-x-2 cursor-pointer group">
                            <input type="checkbox" name="remember_me" class="w-4 h-4 rounded border-slate-300 text-[#000080] focus:ring-[#000080]">
                            <span class="text-xs text-slate-500 font-medium group-hover:text-slate-700 transition-colors">Ingat Saya di Perangkat Ini</span>
                        </label>
                    </div>

                    <div id="admin-fields" class="space-y-5 hidden">
                        <div class="space-y-2">
                            <label class="text-slate-700 text-sm font-semibold ml-1">Username Admin</label>
                            <input type="text" name="username" placeholder="Masukkan username"
                                   class="w-full bg-white border border-slate-200 text-slate-900 placeholder:text-slate-400 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/10 h-12 rounded-xl px-4 transition-all outline-none">
                        </div>
                        <div class="space-y-2">
                            <label class="text-slate-700 text-sm font-semibold ml-1">Password</label>
                            <div class="relative">
                                <input type="password" name="password" id="pass-admin" placeholder="••••••••"
                                       class="w-full bg-white border border-slate-200 text-slate-900 placeholder:text-slate-400 focus:border-[#000080] focus:ring-2 focus:ring-[#000080]/10 h-12 rounded-xl px-4 pr-11 transition-all outline-none">
                                <button type="button" onclick="togglePass()" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-[#000080]">
                                    <svg id="eye-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" id="btn-submit" class="w-full h-12 bg-[#000080] hover:bg-blue-900 text-white font-bold rounded-xl shadow-lg shadow-blue-900/10 transition-all duration-300 transform active:scale-[0.98] flex items-center justify-center">
                    <span id="text-submit">Masuk ke Portal</span>
                    <svg id="spinner" class="hidden w-5 h-5 ml-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="12" y1="2" x2="12" y2="6"></line><line x1="12" y1="18" x2="12" y2="22"></line><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line><line x1="2" y1="12" x2="6" y2="12"></line><line x1="18" y1="12" x2="22" y2="12"></line><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg>
                </button>
            </form>

            <p class="mt-8 text-center text-xs text-slate-400 font-medium italic uppercase tracking-wider">
                Sistem Informasi Tata Tertib Siswa (SITAPSI) v1.0
            </p>
        </div>
    </div>

    <div class="w-full max-w-md text-center relative z-10">
        <a href="ortu/login.php" class="inline-flex items-center justify-center px-6 py-2.5 bg-white border border-slate-200 text-slate-600 hover:text-[#000080] hover:border-[#000080] rounded-full text-sm font-bold shadow-sm transition-all group">
            <svg class="w-4 h-4 mr-2 text-slate-400 group-hover:text-[#000080] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            Masuk sebagai Wali Murid
        </a>
    </div>

    <script>
        function switchRole(role) {
            const typeInput = document.getElementById('login_type');
            const guruFields = document.getElementById('guru-fields');
            const adminFields = document.getElementById('admin-fields');
            const tabGuru = document.getElementById('btn-tab-guru');
            const tabAdmin = document.getElementById('btn-tab-admin');

            typeInput.value = role;

            if (role === 'admin') {
                adminFields.classList.remove('hidden');
                guruFields.classList.add('hidden');
                tabAdmin.classList.add('tab-active');
                tabAdmin.classList.remove('text-slate-400');
                tabGuru.classList.remove('tab-active');
                tabGuru.classList.add('text-slate-400');
            } else {
                guruFields.classList.remove('hidden');
                adminFields.classList.add('hidden');
                tabGuru.classList.add('tab-active');
                tabGuru.classList.remove('text-slate-400');
                tabAdmin.classList.remove('tab-active');
                tabAdmin.classList.add('text-slate-400');
            }
        }

        function togglePass() {
            const input = document.getElementById('pass-admin');
            const icon = document.getElementById('eye-icon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
            } else {
                input.type = 'password';
                icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
            }
        }

        document.getElementById('form-login').addEventListener('submit', function() {
            document.getElementById('text-submit').innerText = 'Memverifikasi...';
            document.getElementById('spinner').classList.remove('hidden');
            document.getElementById('btn-submit').classList.add('opacity-80', 'pointer-events-none');
        });
    </script>
</body>
</html>