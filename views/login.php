<?php
/**
 * SITAPSI - Login Page
 * Halaman login dengan tab Admin dan Guru
 */
session_start();

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'Admin' || $_SESSION['role'] === 'SuperAdmin') {
        header('Location: views/admin/dashboard.php');
    } else {
        header('Location: views/guru/input_pelanggaran.php');
    }
    exit;
}

// Include database untuk load data guru
require_once '../config/database.php';

// Ambil daftar guru aktif untuk dropdown
$guru_list = fetchAll("SELECT id_guru, nama_guru FROM tb_guru WHERE status = 'Aktif' ORDER BY nama_guru ASC");

// Cek apakah ada pesan error
$error = isset($_SESSION['login_error']) ? $_SESSION['login_error'] : '';
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SITAPSI</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom Tailwind Config -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'navy': '#000080',
                        'kelakuan': '#DC2626',
                        'kerajinan': '#2563EB',
                        'kerapian': '#D97706',
                        'reward': '#16A34A'
                    }
                }
            }
        }
    </script>
    
    <style>
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .tab-button.active {
            background-color: #000080;
            color: white;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-blue-100 min-h-screen flex items-center justify-center p-4">
    
    <div class="w-full max-w-md">
        <!-- Logo & Title -->
        <div class="text-center mb-8">
            <div class="bg-white rounded-full w-24 h-24 mx-auto mb-4 flex items-center justify-center shadow-lg">
                <svg class="w-12 h-12 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-navy mb-2">SITAPSI</h1>
            <p class="text-gray-600">Sistem Informasi Tata Tertib & Poin Siswa</p>
            <p class="text-sm text-gray-500 mt-1">SMP Katolik Santa Maria 2 Malang</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
            
            <!-- Tab Navigation -->
            <div class="flex border-b border-gray-200">
                <button 
                    onclick="switchTab('admin')" 
                    id="tab-admin" 
                    class="tab-button flex-1 py-4 px-6 text-center font-semibold text-gray-600 hover:bg-gray-50 transition-colors active">
                    Admin / TU
                </button>
                <button 
                    onclick="switchTab('guru')" 
                    id="tab-guru" 
                    class="tab-button flex-1 py-4 px-6 text-center font-semibold text-gray-600 hover:bg-gray-50 transition-colors">
                    Guru / Piket
                </button>
            </div>

            <!-- Error Message -->
            <?php if ($error): ?>
            <div class="mx-6 mt-4 bg-red-50 border-l-4 border-red-500 p-4 rounded">
                <div class="flex">
                    <svg class="w-5 h-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-sm text-red-700"><?= htmlspecialchars($error) ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Tab Content: Admin -->
            <div id="content-admin" class="tab-content active p-6">
                <form action="../actions/auth.php" method="POST" class="space-y-4">
                    <input type="hidden" name="login_type" value="admin">
                    
                    <div>
                        <label for="admin_username" class="block text-sm font-medium text-gray-700 mb-2">
                            Username
                        </label>
                        <input 
                            type="text" 
                            id="admin_username" 
                            name="username" 
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent"
                            placeholder="Masukkan username">
                    </div>

                    <div>
                        <label for="admin_password" class="block text-sm font-medium text-gray-700 mb-2">
                            Password
                        </label>
                        <input 
                            type="password" 
                            id="admin_password" 
                            name="password" 
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent"
                            placeholder="Masukkan password">
                    </div>

                    <button 
                        type="submit" 
                        class="w-full bg-navy hover:bg-blue-900 text-white font-semibold py-3 px-4 rounded-lg transition-colors duration-200 shadow-lg hover:shadow-xl">
                        Login sebagai Admin
                    </button>
                </form>
            </div>

            <!-- Tab Content: Guru -->
            <div id="content-guru" class="tab-content p-6">
                <form action="../actions/auth.php" method="POST" class="space-y-4">
                    <input type="hidden" name="login_type" value="guru">
                    
                    <div>
                        <label for="guru_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Pilih Nama Guru
                        </label>
                        <select 
                            id="guru_id" 
                            name="guru_id" 
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                            <option value="">-- Pilih Guru --</option>
                            <?php foreach ($guru_list as $guru): ?>
                            <option value="<?= $guru['id_guru'] ?>">
                                <?= htmlspecialchars($guru['nama_guru']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="pin" class="block text-sm font-medium text-gray-700 mb-2">
                            PIN Validasi
                        </label>
                        <input 
                            type="password" 
                            id="pin" 
                            name="pin" 
                            required
                            maxlength="6"
                            pattern="[0-9]{6}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent text-center text-2xl tracking-widest"
                            placeholder="••••••"
                            inputmode="numeric">
                        <p class="text-xs text-gray-500 mt-1">Masukkan 6 digit PIN</p>
                    </div>

                    <div class="flex items-center">
                        <input 
                            type="checkbox" 
                            id="remember_me" 
                            name="remember_me" 
                            value="1"
                            class="w-4 h-4 text-navy border-gray-300 rounded focus:ring-navy">
                        <label for="remember_me" class="ml-2 text-sm text-gray-700">
                            Ingat Saya
                        </label>
                    </div>

                    <button 
                        type="submit" 
                        class="w-full bg-navy hover:bg-blue-900 text-white font-semibold py-3 px-4 rounded-lg transition-colors duration-200 shadow-lg hover:shadow-xl">
                        Mulai Sesi Piket
                    </button>
                </form>
            </div>

        </div>

        <!-- Footer -->
        <div class="text-center mt-6 text-sm text-gray-600">
            <p>&copy; 2025 SMP Katolik Santa Maria 2 Malang</p>
            <p class="mt-1">Developed with ❤️ for Better Education</p>
        </div>
    </div>

    <!-- JavaScript untuk Tab Switching -->
    <script>
        function switchTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById('content-' + tabName).classList.add('active');
            
            // Add active class to selected button
            document.getElementById('tab-' + tabName).classList.add('active');
        }

        // Auto-focus input pertama sesuai tab aktif
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('admin_username').focus();
        });

        // Validasi PIN hanya angka
        document.getElementById('pin').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html>