<?php
/**
 * SITAPSI - SP Helper (FIXED - Insert per Kategori)
 */

function recalculateStatusSP($id_anggota) {
    // Ambil poin terkini siswa
    $siswa = fetchOne("
        SELECT 
            poin_kelakuan, 
            poin_kerajinan, 
            poin_kerapian,
            status_sp_kelakuan,
            status_sp_kerajinan,
            status_sp_kerapian,
            status_sp_terakhir
        FROM tb_anggota_kelas 
        WHERE id_anggota = :id
    ", ['id' => $id_anggota]);
    
    if (!$siswa) return;
    
    // Ambil aturan SP per kategori
    $aturan_sp = fetchAll("
        SELECT id_kategori, level_sp, batas_bawah_poin
        FROM tb_aturan_sp
        ORDER BY id_kategori, batas_bawah_poin DESC
    ");
    
    // Group by kategori
    $aturan_by_kategori = [];
    foreach ($aturan_sp as $a) {
        $aturan_by_kategori[$a['id_kategori']][] = $a;
    }
    
    $level_order = ['Aman' => 0, 'SP1' => 1, 'SP2' => 2, 'SP3' => 3, 'Dikeluarkan' => 4];
    
    $poin_by_kategori = [
        1 => $siswa['poin_kelakuan'],
        2 => $siswa['poin_kerajinan'],
        3 => $siswa['poin_kerapian']
    ];
    
    $status_lama_by_kategori = [
        1 => $siswa['status_sp_kelakuan'],
        2 => $siswa['status_sp_kerajinan'],
        3 => $siswa['status_sp_kerapian']
    ];
    
    $nama_kategori = [1 => 'KELAKUAN', 2 => 'KERAJINAN', 3 => 'KERAPIAN'];
    $nama_field_sp = [
        1 => 'status_sp_kelakuan',
        2 => 'status_sp_kerajinan',
        3 => 'status_sp_kerapian'
    ];
    
    $status_baru_per_kategori = [];
    
    // HITUNG SP PER KATEGORI
    foreach ($poin_by_kategori as $id_kategori => $poin) {
        if (!isset($aturan_by_kategori[$id_kategori])) {
            $status_baru_per_kategori[$id_kategori] = 'Aman';
            continue;
        }
        
        // Cari SP tertinggi yang terpenuhi untuk kategori ini
        $status_baru = 'Aman';
        foreach ($aturan_by_kategori[$id_kategori] as $aturan) {
            if ($poin >= $aturan['batas_bawah_poin']) {
                $status_baru = $aturan['level_sp'];
                break;
            }
        }
        
        $status_baru_per_kategori[$id_kategori] = $status_baru;
        $status_lama = $status_lama_by_kategori[$id_kategori];
        
        // Update field SP kategori ini
        executeQuery("
            UPDATE tb_anggota_kelas 
            SET {$nama_field_sp[$id_kategori]} = :status
            WHERE id_anggota = :id
        ", [
            'status' => $status_baru,
            'id' => $id_anggota
        ]);
        
        // ============================================
        // CRITICAL FIX: Insert riwayat UNTUK SETIAP KATEGORI yang naik
        // ============================================
        if (($level_order[$status_baru] ?? 0) > ($level_order[$status_lama] ?? 0)) {
            
            // CEK: Apakah sudah ada riwayat SP dengan tingkat dan kategori yang sama?
            $existing = fetchOne("
                SELECT id_sp 
                FROM tb_riwayat_sp 
                WHERE id_anggota = :id_anggota 
                AND tingkat_sp = :tingkat_sp 
                AND kategori_pemicu = :kategori
                AND status = 'Pending'
            ", [
                'id_anggota' => $id_anggota,
                'tingkat_sp' => $status_baru,
                'kategori' => $nama_kategori[$id_kategori]
            ]);
            
            // Hanya insert jika belum ada
            if (!$existing) {
                executeQuery("
                    INSERT INTO tb_riwayat_sp (id_anggota, tingkat_sp, kategori_pemicu, tanggal_terbit, status)
                    VALUES (:id_anggota, :tingkat_sp, :kategori_pemicu, CURDATE(), 'Pending')
                ", [
                    'id_anggota' => $id_anggota,
                    'tingkat_sp' => $status_baru,
                    'kategori_pemicu' => $nama_kategori[$id_kategori]
                ]);
            }
        }
        
        // Jika SP turun di kategori ini, hapus riwayat SP Pending yang tidak relevan
        if (($level_order[$status_baru] ?? 0) < ($level_order[$status_lama] ?? 0)) {
            $levels_to_remove = [];
            foreach ($level_order as $level => $order) {
                if ($order > ($level_order[$status_baru] ?? 0)) {
                    $levels_to_remove[] = "'$level'";
                }
            }
            
            if (!empty($levels_to_remove)) {
                $levels_str = implode(',', $levels_to_remove);
                executeQuery("
                    DELETE FROM tb_riwayat_sp 
                    WHERE id_anggota = :id 
                    AND tingkat_sp IN ($levels_str)
                    AND kategori_pemicu = :kategori
                    AND status = 'Pending'
                ", [
                    'id' => $id_anggota,
                    'kategori' => $nama_kategori[$id_kategori]
                ]);
            }
        }
    }
    
    // UPDATE status_sp_terakhir = MAX dari 3 kategori
    $status_tertinggi = 'Aman';
    $max_order = 0;
    foreach ($status_baru_per_kategori as $id_kat => $status) {
        $order = $level_order[$status] ?? 0;
        if ($order > $max_order) {
            $max_order = $order;
            $status_tertinggi = $status;
        }
    }
    
    executeQuery("
        UPDATE tb_anggota_kelas 
        SET status_sp_terakhir = :status
        WHERE id_anggota = :id
    ", [
        'status' => $status_tertinggi,
        'id' => $id_anggota
    ]);
    
    return [
        'kelakuan' => $status_baru_per_kategori[1],
        'kerajinan' => $status_baru_per_kategori[2],
        'kerapian' => $status_baru_per_kategori[3],
        'tertinggi' => $status_tertinggi
    ];
}
?>