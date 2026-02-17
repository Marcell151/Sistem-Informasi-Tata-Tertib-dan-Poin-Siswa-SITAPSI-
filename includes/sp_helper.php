<?php
/**
 * SITAPSI - SP Helper
 * Recalculate status SP otomatis berdasarkan poin terkini
 * Dipanggil setelah setiap perubahan poin siswa
 */

function recalculateStatusSP($id_anggota) {
    // Ambil poin terkini siswa
    $siswa = fetchOne("
        SELECT poin_kelakuan, poin_kerajinan, poin_kerapian, status_sp_terakhir
        FROM tb_anggota_kelas 
        WHERE id_anggota = :id
    ", ['id' => $id_anggota]);
    
    if (!$siswa) return;
    
    // Ambil aturan SP per kategori
    $aturan_sp = fetchAll("
        SELECT sp.id_kategori, sp.level_sp, sp.batas_bawah_poin
        FROM tb_aturan_sp sp
        ORDER BY sp.id_kategori, sp.batas_bawah_poin DESC
    ");
    
    // Group aturan by kategori
    $aturan_by_kategori = [];
    foreach ($aturan_sp as $a) {
        $aturan_by_kategori[$a['id_kategori']][] = $a;
    }
    
    // Tentukan status SP tertinggi dari semua kategori
    $level_order = ['Aman' => 0, 'SP1' => 1, 'SP2' => 2, 'SP3' => 3, 'Dikeluarkan' => 4];
    $status_tertinggi = 'Aman';
    $kategori_pemicu = null;
    
    $poin_by_kategori = [
        1 => $siswa['poin_kelakuan'],
        2 => $siswa['poin_kerajinan'],
        3 => $siswa['poin_kerapian']
    ];
    
    $nama_kategori = [1 => 'KELAKUAN', 2 => 'KERAJINAN', 3 => 'KERAPIAN'];
    
    foreach ($poin_by_kategori as $id_kategori => $poin) {
        if (!isset($aturan_by_kategori[$id_kategori])) continue;
        
        // Cari SP tertinggi yang terpenuhi untuk kategori ini
        foreach ($aturan_by_kategori[$id_kategori] as $aturan) {
            if ($poin >= $aturan['batas_bawah_poin']) {
                $level_ini = $aturan['level_sp'];
                
                // Bandingkan dengan status_tertinggi
                if (($level_order[$level_ini] ?? 0) > ($level_order[$status_tertinggi] ?? 0)) {
                    $status_tertinggi = $level_ini;
                    $kategori_pemicu = $nama_kategori[$id_kategori];
                }
                break; // Sudah dapat level tertinggi untuk kategori ini
            }
        }
    }
    
    $status_lama = $siswa['status_sp_terakhir'];
    
    // Update status SP di anggota_kelas
    executeQuery("
        UPDATE tb_anggota_kelas 
        SET status_sp_terakhir = :status
        WHERE id_anggota = :id
    ", [
        'status' => $status_tertinggi,
        'id' => $id_anggota
    ]);
    
    // Jika ada peningkatan SP, catat di riwayat
    if (($level_order[$status_tertinggi] ?? 0) > ($level_order[$status_lama] ?? 0)) {
        executeQuery("
            INSERT INTO tb_riwayat_sp (id_anggota, tingkat_sp, kategori_pemicu, tanggal_terbit, status)
            VALUES (:id_anggota, :tingkat_sp, :kategori_pemicu, CURDATE(), 'Pending')
        ", [
            'id_anggota' => $id_anggota,
            'tingkat_sp' => $status_tertinggi,
            'kategori_pemicu' => $kategori_pemicu
        ]);
    }
    
    // Jika SP turun (karena poin dikurangi), hapus riwayat SP yang tidak relevan
    if (($level_order[$status_tertinggi] ?? 0) < ($level_order[$status_lama] ?? 0)) {
        // Hapus riwayat SP yang level-nya lebih tinggi dari status baru
        $levels_to_remove = [];
        foreach ($level_order as $level => $order) {
            if ($order > ($level_order[$status_tertinggi] ?? 0)) {
                $levels_to_remove[] = "'$level'";
            }
        }
        
        if (!empty($levels_to_remove)) {
            $levels_str = implode(',', $levels_to_remove);
            executeQuery("
                DELETE FROM tb_riwayat_sp 
                WHERE id_anggota = :id 
                AND tingkat_sp IN ($levels_str)
                AND status = 'Pending'
            ", ['id' => $id_anggota]);
        }
    }
    
    return $status_tertinggi;
}