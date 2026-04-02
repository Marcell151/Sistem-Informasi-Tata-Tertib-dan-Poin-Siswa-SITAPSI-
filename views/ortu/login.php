<?php
session_start();

// Jika sudah login sebagai Ortu, langsung lempar ke dashboard
if (isset($_SESSION['role']) && $_SESSION['role'] === 'Ortu') {
    header("Location: dashboard.php");
    exit;
}

$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['error_message']);

// [BARU] Cek apakah ada data login yang tersimpan di Cookie browser
$saved_nik = $_COOKIE['saved_ortu_nik'] ?? '';
$saved_pass = $_COOKIE['saved_ortu_pass'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Orang Tua - SMPK Santa Maria 2</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex flex-col items-center justify-center p-4 py-10 relative overflow-x-hidden">

    <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-[#000080] rounded-full mix-blend-multiply filter blur-3xl opacity-5"></div>
        <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-blue-400 rounded-full mix-blend-multiply filter blur-3xl opacity-10"></div>
    </div>

    <div class="w-full max-w-md relative z-10 mb-6">
        
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-white shadow-md border border-slate-100 mb-4 p-2">
                <img src="../../assets/img/logo.png" alt="Logo Santa Maria" class="w-full h-full object-contain">
            </div>
            <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Portal Wali Murid</h1>
            <p class="text-sm font-medium text-slate-500 mt-1">SMPK Santa Maria 2 Terpadu</p>
        </div>

        <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/50 border border-slate-100 p-8">
            
            <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl mb-6 flex items-center shadow-sm">
                <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                <p class="text-sm font-bold"><?= htmlspecialchars($error) ?></p>
            </div>
            <?php endif; ?>

            <form action="../../actions/login_ortu_process.php" method="POST" class="space-y-5">
                
                <div>
                    <label class="block text-xs font-extrabold text-slate-600 mb-2 uppercase tracking-wider">NIK / No. Identitas Terdaftar</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        </div>
                        <input type="text" name="nik" required placeholder="Masukkan NIK Anda..." value="<?= htmlspecialchars($saved_nik) ?>"
                            class="w-full pl-11 pr-4 py-3.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#000080]/20 focus:border-[#000080] text-sm font-semibold text-slate-800 transition-all">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-extrabold text-slate-600 mb-2 uppercase tracking-wider">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        </div>
                        <input type="password" name="password" id="password" required placeholder="••••••••" value="<?= htmlspecialchars($saved_pass) ?>"
                            class="w-full pl-11 pr-12 py-3.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#000080]/20 focus:border-[#000080] text-sm font-semibold text-slate-800 transition-all">
                        
                        <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-[#000080] transition-colors">
                            <svg id="eye-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center space-x-2 pt-1">
                    <input type="checkbox" name="remember_me" id="remember" class="w-4 h-4 rounded border-slate-300 text-[#000080] focus:ring-[#000080]" <?= $saved_nik ? 'checked' : '' ?>>
                    <label for="remember" class="text-xs text-slate-500 font-medium cursor-pointer hover:text-slate-700 transition-colors">Ingat NIK dan Password saya</label>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full py-3.5 bg-[#000080] text-white rounded-xl font-extrabold text-sm shadow-lg shadow-blue-900/20 hover:bg-blue-900 transition-all flex items-center justify-center group">
                        Masuk ke Portal
                        <svg class="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"></path></svg>
                    </button>
                </div>

            </form>

            <div class="mt-8 p-4 bg-blue-50/50 border border-blue-100 rounded-xl text-center">
                <p class="text-xs text-blue-800 font-medium leading-relaxed">
                    Gunakan <span class="font-bold">NIK</span> yang didaftarkan ke sekolah.<br>
                    Password Default: <span class="font-bold bg-white px-1.5 py-0.5 rounded shadow-sm">123456</span>
                </p>
            </div>

        </div>
        
    </div>

    <div class="w-full max-w-md text-center relative z-10">
        <a href="../login.php" class="inline-flex items-center justify-center px-6 py-2.5 bg-white border border-slate-200 text-slate-600 hover:text-[#000080] hover:border-[#000080] rounded-full text-sm font-bold shadow-sm transition-all group">
            <svg class="w-4 h-4 mr-2 text-slate-400 group-hover:text-[#000080] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            Masuk sebagai Guru / Admin
        </a>
        <p class="mt-6 text-xs text-slate-500 font-medium">&copy; <?= date('Y') ?> SITAPSI & Sistem Terpadu.</p>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('eye-icon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
            } else {
                input.type = 'password';
                icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
            }
        }
    </script>
</body>
</html>