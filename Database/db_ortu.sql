    USE db_sitapsi;

    -- 1. Buat Tabel Khusus Orang Tua / Wali
    CREATE TABLE tb_orang_tua (
        id_ortu INT AUTO_INCREMENT PRIMARY KEY,
        nik_ortu VARCHAR(20) UNIQUE NOT NULL, 
        password VARCHAR(255) NOT NULL, 
        nama_ayah VARCHAR(150),
        pekerjaan_ayah VARCHAR(100),
        nama_ibu VARCHAR(150),
        pekerjaan_ibu VARCHAR(100),
        no_hp_ortu VARCHAR(15),
        alamat TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    -- 2. Pasang Gembok Relasi (Foreign Key) ke tabel Siswa
    ALTER TABLE tb_siswa 
    ADD CONSTRAINT fk_ortu_siswa 
    FOREIGN KEY (id_ortu) REFERENCES tb_orang_tua(id_ortu) ON DELETE SET NULL;

    -- ==========================================================
    -- 3. INSERT DATA DUMMY ORANG TUA (UNTUK TESTING LOGIN NANTI)
    -- Username NIK: 3573012345678901
    -- Password: 'e10adc3949ba59abbe56e057f20f883e' (MD5 dari '123456')
    -- ==========================================================
    INSERT INTO tb_orang_tua (nik_ortu, password, nama_ayah, pekerjaan_ayah, nama_ibu, pekerjaan_ibu, no_hp_ortu, alamat) 
    VALUES ('3573012345678901', 'e10adc3949ba59abbe56e057f20f883e', 'Bpk. Dani', 'Swasta', 'Ibu Dani', 'Ibu Rumah Tangga', '081234567890', 'Jl. Merdeka No. 1, Malang');

    -- ==========================================================
    -- 4. SIMULASI RELASI KAKAK-ADIK
    -- ==========================================================

    -- A. Ikat Siswa Pertama (Sang Kakak - Ahmad Roland yang sudah ada di script 1) ke Akun Bapak Dani
    UPDATE tb_siswa SET id_ortu = 1 WHERE no_induk = '2024001';

    -- B. Buat Siswa Kedua (Sang Adik - Budi Roland) dan langsung diikat ke Akun Bapak Dani
    INSERT INTO tb_siswa (no_induk, nama_siswa, jenis_kelamin, kota, tanggal_lahir, alamat, id_ortu, status_aktif) 
    VALUES ('2025002', 'Budi Roland', 'L', 'Malang', '2012-08-20', 'Jl. Merdeka No. 1', 1, 'Aktif');

    -- Masukkan Sang Adik ke Kelas 7B
    INSERT INTO tb_anggota_kelas (no_induk, id_kelas, id_tahun) VALUES ('2025002', 2, 1);