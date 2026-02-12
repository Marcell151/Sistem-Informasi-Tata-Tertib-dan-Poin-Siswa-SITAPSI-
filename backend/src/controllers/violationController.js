import { executeQuery, executeTransaction } from '../config/database.js';
import { sendSuccessResponse, sendErrorResponse } from '../utils/responseHandler.js';

/**
 * @desc    Input Pelanggaran (Form Piket/Kelas)
 * @route   POST /api/violations/input
 * @access  Private (Guru & Admin)
 */
export const inputPelanggaran = async (req, res) => {
  try {
    const { id_anggota, tipe_form, pelanggaran, sanksi } = req.body;
    const id_guru = req.user.id;

    // 1. Validasi: Apakah siswa exists?
    const checkSiswa = await executeQuery(
      'SELECT * FROM tb_anggota_kelas WHERE id_anggota = ?',
      [id_anggota]
    );

    if (checkSiswa.length === 0) {
      return sendErrorResponse(res, 'Siswa tidak ditemukan', 404);
    }

    const siswa = checkSiswa[0];

    // 2. Get tahun ajaran & semester aktif
    const tahunAktif = await executeQuery(
      "SELECT id_tahun, semester_aktif FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1"
    );

    if (tahunAktif.length === 0) {
      return sendErrorResponse(res, 'Tidak ada tahun ajaran aktif', 400);
    }

    const { id_tahun, semester_aktif } = tahunAktif[0];

    // 3. Gunakan TRANSACTION untuk memastikan data konsisten
    const result = await executeTransaction(async (connection) => {
      
      // 3a. Insert ke tb_pelanggaran_header
      const [headerResult] = await connection.execute(
        `INSERT INTO tb_pelanggaran_header 
        (id_anggota, id_guru, id_tahun, tanggal, waktu, semester, tipe_form) 
        VALUES (?, ?, ?, CURDATE(), CURTIME(), ?, ?)`,
        [id_anggota, id_guru, id_tahun, semester_aktif, tipe_form]
      );

      const id_transaksi = headerResult.insertId;

      // 3b. Insert detail pelanggaran (BATCH INSERT untuk performa)
      const detailValues = pelanggaran.map(p => 
        [id_transaksi, p.id_jenis, p.poin]
      );

      await connection.query(
        `INSERT INTO tb_pelanggaran_detail (id_transaksi, id_jenis, poin_saat_itu) 
         VALUES ?`,
        [detailValues]
      );

      // 3c. Insert sanksi jika ada
      if (sanksi && sanksi.length > 0) {
        const sanksiValues = sanksi.map(s => [id_transaksi, s]);
        
        await connection.query(
          `INSERT INTO tb_pelanggaran_sanksi (id_transaksi, id_sanksi_ref) 
           VALUES ?`,
          [sanksiValues]
        );
      }

      // 3d. Update akumulasi poin di tb_anggota_kelas
      const totalPoin = pelanggaran.reduce((sum, p) => sum + p.poin, 0);

      // Get kategori dari pelanggaran pertama (asumsi: 1 input = 1 kategori)
      const [jenisInfo] = await connection.execute(
        'SELECT id_kategori FROM tb_jenis_pelanggaran WHERE id_jenis = ?',
        [pelanggaran[0].id_jenis]
      );

      const id_kategori = jenisInfo[0].id_kategori;

      // Update poin sesuai kategori
      let updateField;
      switch (id_kategori) {
        case 1: updateField = 'poin_kelakuan'; break;
        case 2: updateField = 'poin_kerajinan'; break;
        case 3: updateField = 'poin_kerapian'; break;
      }

      await connection.execute(
        `UPDATE tb_anggota_kelas 
         SET ${updateField} = ${updateField} + ?, 
             total_poin_umum = total_poin_umum + ?
         WHERE id_anggota = ?`,
        [totalPoin, totalPoin, id_anggota]
      );

      // 3e. Cek apakah perlu trigger SP
      await checkAndTriggerSP(connection, id_anggota, id_kategori);

      return { id_transaksi, total_poin: totalPoin };
    });

    sendSuccessResponse(res, 'Pelanggaran berhasil dicatat', result, 201);

  } catch (error) {
    console.error('Input Pelanggaran Error:', error);
    sendErrorResponse(res, 'Terjadi kesalahan saat mencatat pelanggaran', 500);
  }
};

/**
 * Helper: Check dan Trigger SP jika poin melebihi batas
 */
const checkAndTriggerSP = async (connection, id_anggota, id_kategori) => {
  // Get poin siswa saat ini
  const [anggota] = await connection.execute(
    'SELECT poin_kelakuan, poin_kerajinan, poin_kerapian FROM tb_anggota_kelas WHERE id_anggota = ?',
    [id_anggota]
  );

  const poinMapping = {
    1: anggota[0].poin_kelakuan,
    2: anggota[0].poin_kerajinan,
    3: anggota[0].poin_kerapian
  };

  const currentPoin = poinMapping[id_kategori];

  // Get aturan SP untuk kategori ini
  const aturanSP = await connection.execute(
    `SELECT level_sp, batas_bawah_poin 
     FROM tb_aturan_sp 
     WHERE id_kategori = ? 
     ORDER BY batas_bawah_poin ASC`,
    [id_kategori]
  );

  // Cek level SP tertinggi yang sudah tercapai
  let triggeredSP = null;
  for (const rule of aturanSP[0]) {
    if (currentPoin >= rule.batas_bawah_poin) {
      triggeredSP = rule.level_sp;
    }
  }

  // Jika ada SP yang triggered, cek apakah sudah pernah dibuat
  if (triggeredSP && triggeredSP !== 'Dikeluarkan') {
    const [existingSP] = await connection.execute(
      `SELECT * FROM tb_riwayat_sp 
       WHERE id_anggota = ? AND tingkat_sp = ?`,
      [id_anggota, triggeredSP]
    );

    // Jika belum ada, buat SP baru
    if (existingSP.length === 0) {
      const kategoriNama = ['', 'KELAKUAN', 'KERAJINAN', 'KERAPIAN'][id_kategori];
      
      await connection.execute(
        `INSERT INTO tb_riwayat_sp 
         (id_anggota, tingkat_sp, kategori_pemicu, tanggal_terbit, status) 
         VALUES (?, ?, ?, CURDATE(), 'Pending')`,
        [id_anggota, triggeredSP, kategoriNama]
      );

      // Update status SP terakhir di anggota
      await connection.execute(
        'UPDATE tb_anggota_kelas SET status_sp_terakhir = ? WHERE id_anggota = ?',
        [triggeredSP, id_anggota]
      );
    }
  }
};

/**
 * @desc    Get Riwayat Pelanggaran (dengan Filter)
 * @route   GET /api/violations/history
 * @access  Private (Admin)
 * @query   ?page=1&limit=20&kelas=7A&tanggal_mulai=2024-01-01&tanggal_akhir=2024-12-31
 */
export const getRiwayatPelanggaran = async (req, res) => {
  try {
    const { page = 1, limit = 20, kelas, tanggal_mulai, tanggal_akhir, tipe_form } = req.query;
    const offset = (page - 1) * limit;

    // Build dynamic WHERE clause
    let whereConditions = [];
    let params = [];

    if (kelas) {
      whereConditions.push('k.nama_kelas = ?');
      params.push(kelas);
    }

    if (tanggal_mulai && tanggal_akhir) {
      whereConditions.push('ph.tanggal BETWEEN ? AND ?');
      params.push(tanggal_mulai, tanggal_akhir);
    }

    if (tipe_form) {
      whereConditions.push('ph.tipe_form = ?');
      params.push(tipe_form);
    }

    const whereClause = whereConditions.length > 0 
      ? 'WHERE ' + whereConditions.join(' AND ') 
      : '';

    // Get total count
    const countQuery = `
      SELECT COUNT(*) as total
      FROM tb_pelanggaran_header ph
      JOIN tb_anggota_kelas a ON ph.id_anggota = a.id_anggota
      JOIN tb_siswa s ON a.nis = s.nis
      JOIN tb_kelas k ON a.id_kelas = k.id_kelas
      ${whereClause}
    `;

    const [countResult] = await executeQuery(countQuery, params);
    const totalRecords = countResult.total;

    // Get paginated data
    const dataQuery = `
      SELECT 
        ph.id_transaksi,
        ph.tanggal,
        ph.waktu,
        ph.semester,
        ph.tipe_form,
        s.nis,
        s.nama_siswa,
        k.nama_kelas,
        g.nama_guru as pelapor,
        GROUP_CONCAT(DISTINCT jp.nama_pelanggaran SEPARATOR ', ') as pelanggaran,
        SUM(pd.poin_saat_itu) as total_poin
      FROM tb_pelanggaran_header ph
      JOIN tb_anggota_kelas a ON ph.id_anggota = a.id_anggota
      JOIN tb_siswa s ON a.nis = s.nis
      JOIN tb_kelas k ON a.id_kelas = k.id_kelas
      JOIN tb_guru g ON ph.id_guru = g.id_guru
      LEFT JOIN tb_pelanggaran_detail pd ON ph.id_transaksi = pd.id_transaksi
      LEFT JOIN tb_jenis_pelanggaran jp ON pd.id_jenis = jp.id_jenis
      ${whereClause}
      GROUP BY ph.id_transaksi
      ORDER BY ph.tanggal DESC, ph.waktu DESC
      LIMIT ? OFFSET ?
    `;

    const data = await executeQuery(dataQuery, [...params, parseInt(limit), offset]);

    sendSuccessResponse(res, 'Riwayat pelanggaran berhasil diambil', {
      data,
      pagination: {
        current_page: parseInt(page),
        per_page: parseInt(limit),
        total_records: totalRecords,
        total_pages: Math.ceil(totalRecords / limit)
      }
    });

  } catch (error) {
    console.error('Get Riwayat Error:', error);
    sendErrorResponse(res, 'Terjadi kesalahan saat mengambil riwayat', 500);
  }
};

/**
 * @desc    Get Detail Siswa (Rapor 3 Silo dengan Filter Semester)
 * @route   GET /api/violations/student/:id_anggota
 * @access  Private (Guru & Admin)
 * @query   ?semester=Ganjil (opsional, default: semester aktif)
 */
export const getDetailSiswa = async (req, res) => {
  try {
    const { id_anggota } = req.params;
    const { semester, id_tahun } = req.query;

    // 1. Get info siswa
    const siswaQuery = `
      SELECT 
        a.id_anggota,
        a.nis,
        s.nama_siswa,
        s.jenis_kelamin,
        s.foto_profil,
        k.nama_kelas,
        k.tingkat,
        t.nama_tahun,
        t.semester_aktif,
        a.poin_kelakuan,
        a.poin_kerajinan,
        a.poin_kerapian,
        a.total_poin_umum,
        a.status_sp_terakhir,
        a.status_reward
      FROM tb_anggota_kelas a
      JOIN tb_siswa s ON a.nis = s.nis
      JOIN tb_kelas k ON a.id_kelas = k.id_kelas
      JOIN tb_tahun_ajaran t ON a.id_tahun = t.id_tahun
      WHERE a.id_anggota = ?
    `;

    const siswaData = await executeQuery(siswaQuery, [id_anggota]);

    if (siswaData.length === 0) {
      return sendErrorResponse(res, 'Siswa tidak ditemukan', 404);
    }

    const siswa = siswaData[0];

    // 2. Get poin PER SEMESTER (untuk tampilan Clean Slate)
    const semesterFilter = semester || siswa.semester_aktif;
    const tahunFilter = id_tahun || siswa.id_tahun;

    const poinSemesterQuery = `
      SELECT 
        SUM(CASE WHEN jp.id_kategori = 1 THEN pd.poin_saat_itu ELSE 0 END) as poin_kelakuan_semester,
        SUM(CASE WHEN jp.id_kategori = 2 THEN pd.poin_saat_itu ELSE 0 END) as poin_kerajinan_semester,
        SUM(CASE WHEN jp.id_kategori = 3 THEN pd.poin_saat_itu ELSE 0 END) as poin_kerapian_semester
      FROM tb_pelanggaran_header ph
      JOIN tb_pelanggaran_detail pd ON ph.id_transaksi = pd.id_transaksi
      JOIN tb_jenis_pelanggaran jp ON pd.id_jenis = jp.id_jenis
      WHERE ph.id_anggota = ? AND ph.semester = ? AND ph.id_tahun = ?
    `;

    const [poinSemester] = await executeQuery(poinSemesterQuery, [id_anggota, semesterFilter, tahunFilter]);

    // 3. Get riwayat pelanggaran detail
    const riwayatQuery = `
      SELECT 
        ph.id_transaksi,
        ph.tanggal,
        ph.waktu,
        ph.semester,
        ph.tipe_form,
        jp.id_kategori,
        jp.sub_kategori,
        jp.nama_pelanggaran,
        pd.poin_saat_itu,
        g.nama_guru as pelapor
      FROM tb_pelanggaran_header ph
      JOIN tb_pelanggaran_detail pd ON ph.id_transaksi = pd.id_transaksi
      JOIN tb_jenis_pelanggaran jp ON pd.id_jenis = jp.id_jenis
      JOIN tb_guru g ON ph.id_guru = g.id_guru
      WHERE ph.id_anggota = ?
      ORDER BY ph.tanggal DESC, ph.waktu DESC
    `;

    const riwayat = await executeQuery(riwayatQuery, [id_anggota]);

    // 4. Get predikat nilai (A/B/C/D)
    const predikatQuery = `
      SELECT 
        id_kategori,
        huruf_mutu,
        keterangan
      FROM tb_predikat_nilai
      WHERE 
        (id_kategori = 1 AND ? BETWEEN batas_bawah AND batas_atas) OR
        (id_kategori = 2 AND ? BETWEEN batas_bawah AND batas_atas) OR
        (id_kategori = 3 AND ? BETWEEN batas_bawah AND batas_atas)
    `;

    const predikat = await executeQuery(predikatQuery, [
      siswa.poin_kelakuan,
      siswa.poin_kerajinan,
      siswa.poin_kerapian
    ]);

    sendSuccessResponse(res, 'Detail siswa berhasil diambil', {
      siswa: {
        ...siswa,
        // Poin untuk tampilan (Clean Slate per semester)
        poin_tampilan: {
          kelakuan: poinSemester.poin_kelakuan_semester || 0,
          kerajinan: poinSemester.poin_kerajinan_semester || 0,
          kerapian: poinSemester.poin_kerapian_semester || 0
        },
        // Poin akumulasi (untuk SP)
        poin_akumulasi: {
          kelakuan: siswa.poin_kelakuan,
          kerajinan: siswa.poin_kerajinan,
          kerapian: siswa.poin_kerapian
        }
      },
      predikat,
      riwayat
    });

  } catch (error) {
    console.error('Get Detail Siswa Error:', error);
    sendErrorResponse(res, 'Terjadi kesalahan saat mengambil detail siswa', 500);
  }
};

/**
 * @desc    Get Rekapitulasi Kelas
 * @route   GET /api/violations/recap/class/:id_kelas
 * @access  Private (Guru & Admin)
 */
export const getRekapKelas = async (req, res) => {
  try {
    const { id_kelas } = req.params;
    const { id_tahun } = req.query;

    // Get tahun aktif jika tidak dispesifikkan
    let tahunFilter = id_tahun;
    if (!tahunFilter) {
      const [tahunAktif] = await executeQuery(
        "SELECT id_tahun FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1"
      );
      tahunFilter = tahunAktif.id_tahun;
    }

    const query = `
      SELECT 
        a.id_anggota,
        s.nis,
        s.nama_siswa,
        s.jenis_kelamin,
        s.foto_profil,
        a.poin_kelakuan,
        a.poin_kerajinan,
        a.poin_kerapian,
        a.total_poin_umum,
        a.status_sp_terakhir,
        a.status_reward
      FROM tb_anggota_kelas a
      JOIN tb_siswa s ON a.nis = s.nis
      WHERE a.id_kelas = ? AND a.id_tahun = ?
      ORDER BY s.nama_siswa ASC
    `;

    const data = await executeQuery(query, [id_kelas, tahunFilter]);

    sendSuccessResponse(res, 'Rekapitulasi kelas berhasil diambil', data);

  } catch (error) {
    console.error('Get Rekap Kelas Error:', error);
    sendErrorResponse(res, 'Terjadi kesalahan saat mengambil rekap kelas', 500);
  }
};

/**
 * @desc    Delete Pelanggaran (Admin Only - untuk koreksi error)
 * @route   DELETE /api/violations/:id_transaksi
 * @access  Private (Admin)
 */
export const deletePelanggaran = async (req, res) => {
  try {
    const { id_transaksi } = req.params;

    await executeTransaction(async (connection) => {
      // 1. Get data pelanggaran sebelum dihapus (untuk rollback poin)
      const [headerData] = await connection.execute(
        `SELECT ph.id_anggota, SUM(pd.poin_saat_itu) as total_poin, jp.id_kategori
         FROM tb_pelanggaran_header ph
         JOIN tb_pelanggaran_detail pd ON ph.id_transaksi = pd.id_transaksi
         JOIN tb_jenis_pelanggaran jp ON pd.id_jenis = jp.id_jenis
         WHERE ph.id_transaksi = ?
         GROUP BY ph.id_anggota, jp.id_kategori`,
        [id_transaksi]
      );

      if (headerData.length === 0) {
        throw new Error('Data pelanggaran tidak ditemukan');
      }

      const { id_anggota, total_poin, id_kategori } = headerData[0];

      // 2. Delete transaksi (cascade akan delete detail & sanksi)
      await connection.execute(
        'DELETE FROM tb_pelanggaran_header WHERE id_transaksi = ?',
        [id_transaksi]
      );

      // 3. Rollback poin di anggota_kelas
      let updateField;
      switch (id_kategori) {
        case 1: updateField = 'poin_kelakuan'; break;
        case 2: updateField = 'poin_kerajinan'; break;
        case 3: updateField = 'poin_kerapian'; break;
      }

      await connection.execute(
        `UPDATE tb_anggota_kelas 
         SET ${updateField} = ${updateField} - ?,
             total_poin_umum = total_poin_umum - ?
         WHERE id_anggota = ?`,
        [total_poin, total_poin, id_anggota]
      );
    });

    sendSuccessResponse(res, 'Pelanggaran berhasil dihapus');

  } catch (error) {
    console.error('Delete Pelanggaran Error:', error);
    sendErrorResponse(res, error.message || 'Terjadi kesalahan saat menghapus pelanggaran', 500);
  }
};

/**
 * @desc    Get Jenis Pelanggaran (untuk dropdown input)
 * @route   GET /api/violations/types/:id_kategori
 * @access  Private (Guru & Admin)
 */
export const getJenisPelanggaran = async (req, res) => {
  try {
    const { id_kategori } = req.params;

    const query = `
      SELECT 
        id_jenis,
        sub_kategori,
        nama_pelanggaran,
        poin_default,
        sanksi_default
      FROM tb_jenis_pelanggaran
      WHERE id_kategori = ?
      ORDER BY sub_kategori, nama_pelanggaran
    `;

    const data = await executeQuery(query, [id_kategori]);

    sendSuccessResponse(res, 'Jenis pelanggaran berhasil diambil', data);

  } catch (error) {
    console.error('Get Jenis Pelanggaran Error:', error);
    sendErrorResponse(res, 'Terjadi kesalahan', 500);
  }
};

/**
 * @desc    Get Referensi Sanksi
 * @route   GET /api/violations/sanctions
 * @access  Private (Guru & Admin)
 */
export const getSanksiRef = async (req, res) => {
  try {
    const query = 'SELECT * FROM tb_sanksi_ref ORDER BY id_sanksi_ref';
    const data = await executeQuery(query);

    sendSuccessResponse(res, 'Referensi sanksi berhasil diambil', data);

  } catch (error) {
    console.error('Get Sanksi Error:', error);
    sendErrorResponse(res, 'Terjadi kesalahan', 500);
  }
};