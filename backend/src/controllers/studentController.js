import xlsx from 'xlsx';
import path from 'path';
import fs from 'fs';
import { executeQuery, executeTransaction } from '../config/database.js';
import { sendSuccessResponse, sendErrorResponse } from '../utils/responseHandler.js';

/**
 * @desc    Get Daftar Siswa (dengan Pencarian)
 * @route   GET /api/students
 * @access  Private (Guru & Admin)
 * @query   ?search=nama&kelas=7A&status=Aktif
 */
export const getStudents = async (req, res) => {
  try {
    const { search, kelas, status = 'Aktif', page = 1, limit = 50 } = req.query;
    const offset = (page - 1) * limit;

    let whereConditions = ['s.status_aktif = ?'];
    let params = [status];

    if (search) {
      whereConditions.push('(s.nama_siswa LIKE ? OR s.nis LIKE ?)');
      params.push(`%${search}%`, `%${search}%`);
    }

    if (kelas) {
      whereConditions.push('k.nama_kelas = ?');
      params.push(kelas);
    }

    const whereClause = whereConditions.join(' AND ');

    // Get tahun aktif
    const [tahunAktif] = await executeQuery(
      "SELECT id_tahun FROM tb_tahun_ajaran WHERE status = 'Aktif' LIMIT 1"
    );

    const query = `
      SELECT 
        a.id_anggota,
        s.nis,
        s.nama_siswa,
        s.jenis_kelamin,
        s.foto_profil,
        k.nama_kelas,
        k.tingkat,
        a.total_poin_umum,
        a.status_sp_terakhir
      FROM tb_siswa s
      LEFT JOIN tb_anggota_kelas a ON s.nis = a.nis AND a.id_tahun = ?
      LEFT JOIN tb_kelas k ON a.id_kelas = k.id_kelas
      WHERE ${whereClause}
      ORDER BY s.nama_siswa ASC
      LIMIT ? OFFSET ?
    `;

    const data = await executeQuery(query, [tahunAktif.id_tahun, ...params, parseInt(limit), offset]);

    sendSuccessResponse(res, 'Daftar siswa berhasil diambil', data);

  } catch (error) {
    console.error('Get Students Error:', error);
    sendErrorResponse(res, 'Terjadi kesalahan saat mengambil daftar siswa', 500);
  }
};

/**
 * @desc    Import Data Siswa dari Excel
 * @route   POST /api/students/import
 * @access  Private (Admin)
 */
export const importStudents = async (req, res) => {
  try {
    if (!req.file) {
      return sendErrorResponse(res, 'File Excel harus diupload', 400);
    }

    const { id_kelas, id_tahun } = req.body;

    if (!id_kelas || !id_tahun) {
      return sendErrorResponse(res, 'ID Kelas dan Tahun Ajaran harus diisi', 400);
    }

    // Read Excel file
    const workbook = xlsx.readFile(req.file.path);
    const sheetName = workbook.SheetNames[0];
    const worksheet = workbook.Sheets[sheetName];
    const data = xlsx.utils.sheet_to_json(worksheet);

    if (data.length === 0) {
      return sendErrorResponse(res, 'File Excel kosong', 400);
    }

    // Validate required columns
    const requiredColumns = ['nis', 'nama_siswa', 'jenis_kelamin'];
    const firstRow = data[0];
    const missingColumns = requiredColumns.filter(col => !(col in firstRow));

    if (missingColumns.length > 0) {
      return sendErrorResponse(res, `Kolom wajib tidak ditemukan: ${missingColumns.join(', ')}`, 400);
    }

    // Import dengan Transaction
    const result = await executeTransaction(async (connection) => {
      let inserted = 0;
      let updated = 0;
      let errors = [];

      for (let i = 0; i < data.length; i++) {
        const row = data[i];
        
        try {
          // Validasi jenis kelamin
          if (!['L', 'P'].includes(row.jenis_kelamin)) {
            throw new Error(`Jenis kelamin tidak valid (harus L/P)`);
          }

          // Check apakah NIS sudah ada
          const [existing] = await connection.execute(
            'SELECT nis FROM tb_siswa WHERE nis = ?',
            [row.nis]
          );

          if (existing.length > 0) {
            // Update data siswa
            await connection.execute(
              `UPDATE tb_siswa SET 
               nama_siswa = ?, 
               jenis_kelamin = ?,
               tempat_lahir = ?,
               tanggal_lahir = ?,
               alamat_ortu = ?,
               nama_ortu = ?,
               no_hp_ortu = ?
               WHERE nis = ?`,
              [
                row.nama_siswa,
                row.jenis_kelamin,
                row.tempat_lahir || null,
                row.tanggal_lahir || null,
                row.alamat_ortu || null,
                row.nama_ortu || null,
                row.no_hp_ortu || null,
                row.nis
              ]
            );
            updated++;
          } else {
            // Insert siswa baru
            await connection.execute(
              `INSERT INTO tb_siswa 
               (nis, nama_siswa, jenis_kelamin, tempat_lahir, tanggal_lahir, 
                alamat_ortu, nama_ortu, no_hp_ortu, status_aktif) 
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Aktif')`,
              [
                row.nis,
                row.nama_siswa,
                row.jenis_kelamin,
                row.tempat_lahir || null,
                row.tanggal_lahir || null,
                row.alamat_ortu || null,
                row.nama_ortu || null,
                row.no_hp_ortu || null
              ]
            );
            inserted++;
          }

          // Check apakah sudah ada di anggota_kelas untuk tahun ini
          const [anggotaExist] = await connection.execute(
            'SELECT id_anggota FROM tb_anggota_kelas WHERE nis = ? AND id_tahun = ?',
            [row.nis, id_tahun]
          );

          // Jika belum ada, insert ke anggota_kelas
          if (anggotaExist.length === 0) {
            await connection.execute(
              'INSERT INTO tb_anggota_kelas (nis, id_kelas, id_tahun) VALUES (?, ?, ?)',
              [row.nis, id_kelas, id_tahun]
            );
          }

        } catch (error) {
          errors.push({
            row: i + 2, // +2 karena Excel mulai dari row 1 dan ada header
            nis: row.nis,
            error: error.message
          });
        }
      }

      return { inserted, updated, errors, total: data.length };
    });

    // Delete uploaded file
    fs.unlinkSync(req.file.path);

    sendSuccessResponse(res, 'Import siswa selesai', result, 201);

  } catch (error) {
    // Delete file jika ada error
    if (req.file) {
      fs.unlinkSync(req.file.path);
    }

    console.error('Import Students Error:', error);
    sendErrorResponse(res, 'Terjadi kesalahan saat import siswa', 500);
  }
};

/**
 * @desc    Get Daftar Kelas
 * @route   GET /api/students/classes
 * @access  Private (Guru & Admin)
 */
export const getClasses = async (req, res) => {
  try {
    const query = 'SELECT * FROM tb_kelas ORDER BY tingkat, nama_kelas';
    const data = await executeQuery(query);

    sendSuccessResponse(res, 'Daftar kelas berhasil diambil', data);

  } catch (error) {
    console.error('Get Classes Error:', error);
    sendErrorResponse(res, 'Terjadi kesalahan', 500);
  }
};

/**
 * @desc    Proses Kenaikan Kelas (Promosi Siswa)
 * @route   POST /api/students/promote
 * @access  Private (Admin)
 */
export const promoteStudents = async (req, res) => {
  try {
    const { id_tahun_lama, id_tahun_baru } = req.body;

    if (!id_tahun_lama || !id_tahun_baru) {
      return sendErrorResponse(res, 'Tahun ajaran lama dan baru harus diisi', 400);
    }

    const result = await executeTransaction(async (connection) => {
      // 1. Get semua siswa di tahun lama
      const [siswaList] = await connection.execute(
        `SELECT a.nis, k.tingkat 
         FROM tb_anggota_kelas a
         JOIN tb_kelas k ON a.id_kelas = k.id_kelas
         WHERE a.id_tahun = ?`,
        [id_tahun_lama]
      );

      let promoted = 0;
      let graduated = 0;

      // 2. Promosi setiap siswa
      for (const siswa of siswaList) {
        if (siswa.tingkat === 9) {
          // Kelas 9 -> Lulus
          await connection.execute(
            "UPDATE tb_siswa SET status_aktif = 'Lulus' WHERE nis = ?",
            [siswa.nis]
          );
          graduated++;
        } else {
          // Kelas 7 -> 8, Kelas 8 -> 9
          const tingkatBaru = siswa.tingkat + 1;
          
          // Get kelas baru (asumsi: mapping otomatis, bisa disesuaikan)
          const [kelasBaru] = await connection.execute(
            'SELECT id_kelas FROM tb_kelas WHERE tingkat = ? LIMIT 1',
            [tingkatBaru]
          );

          if (kelasBaru.length > 0) {
            await connection.execute(
              'INSERT INTO tb_anggota_kelas (nis, id_kelas, id_tahun) VALUES (?, ?, ?)',
              [siswa.nis, kelasBaru[0].id_kelas, id_tahun_baru]
            );
            promoted++;
          }
        }
      }

      return { promoted, graduated };
    });

    sendSuccessResponse(res, 'Kenaikan kelas berhasil diproses', result);

  } catch (error) {
    console.error('Promote Students Error:', error);
    sendErrorResponse(res, 'Terjadi kesalahan saat proses kenaikan kelas', 500);
  }
};

/**
 * @desc    Add/Update Single Student
 * @route   POST /api/students
 * @access  Private (Admin)
 */
export const addStudent = async (req, res) => {
  try {
    const { nis, nama_siswa, jenis_kelamin, tempat_lahir, tanggal_lahir, 
            alamat_ortu, nama_ortu, no_hp_ortu, id_kelas, id_tahun } = req.body;

    await executeTransaction(async (connection) => {
      // Insert/Update tb_siswa
      const [existing] = await connection.execute(
        'SELECT nis FROM tb_siswa WHERE nis = ?',
        [nis]
      );

      if (existing.length > 0) {
        await connection.execute(
          `UPDATE tb_siswa SET 
           nama_siswa = ?, jenis_kelamin = ?, tempat_lahir = ?, 
           tanggal_lahir = ?, alamat_ortu = ?, nama_ortu = ?, no_hp_ortu = ?
           WHERE nis = ?`,
          [nama_siswa, jenis_kelamin, tempat_lahir, tanggal_lahir, 
           alamat_ortu, nama_ortu, no_hp_ortu, nis]
        );
      } else {
        await connection.execute(
          `INSERT INTO tb_siswa 
           (nis, nama_siswa, jenis_kelamin, tempat_lahir, tanggal_lahir, 
            alamat_ortu, nama_ortu, no_hp_ortu, status_aktif) 
           VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Aktif')`,
          [nis, nama_siswa, jenis_kelamin, tempat_lahir, tanggal_lahir, 
           alamat_ortu, nama_ortu, no_hp_ortu]
        );
      }

      // Insert ke anggota_kelas jika ada id_kelas dan id_tahun
      if (id_kelas && id_tahun) {
        const [anggotaExist] = await connection.execute(
          'SELECT id_anggota FROM tb_anggota_kelas WHERE nis = ? AND id_tahun = ?',
          [nis, id_tahun]
        );

        if (anggotaExist.length === 0) {
          await connection.execute(
            'INSERT INTO tb_anggota_kelas (nis, id_kelas, id_tahun) VALUES (?, ?, ?)',
            [nis, id_kelas, id_tahun]
          );
        }
      }
    });

    sendSuccessResponse(res, 'Data siswa berhasil disimpan', null, 201);

  } catch (error) {
    console.error('Add Student Error:', error);
    sendErrorResponse(res, 'Terjadi kesalahan saat menyimpan data siswa', 500);
  }
};