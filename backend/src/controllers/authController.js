import bcrypt from 'bcryptjs';
import { executeQuery } from '../config/database.js';
import { sendSuccessResponse, sendErrorResponse } from '../utils/responseHandler.js';
import { generateToken, setTokenCookie } from '../middleware/auth.js';

/**
 * @desc    Login Admin (Username + Password)
 * @route   POST /api/auth/login/admin
 * @access  Public
 */
export const loginAdmin = async (req, res) => {
  try {
    const { username, password } = req.body;

    // 1. Cek apakah admin exists
    const query = 'SELECT * FROM tb_admin WHERE username = ? LIMIT 1';
    const admins = await executeQuery(query, [username]);

    if (admins.length === 0) {
      return sendErrorResponse(res, 'Username atau password salah', 401);
    }

    const admin = admins[0];

    // 2. Verify password
    const isPasswordValid = await bcrypt.compare(password, admin.password);
    
    if (!isPasswordValid) {
      return sendErrorResponse(res, 'Username atau password salah', 401);
    }

    // 3. Generate JWT Token
    const token = generateToken({
      id: admin.id_admin,
      role: 'admin',
      nama: admin.nama_lengkap
    });

    // 4. Set token sebagai HttpOnly Cookie
    setTokenCookie(res, token);

    // 5. Response (JANGAN kirim password)
    sendSuccessResponse(res, 'Login berhasil', {
      user: {
        id: admin.id_admin,
        username: admin.username,
        nama: admin.nama_lengkap,
        role: admin.role
      },
      token // Untuk testing (di production hanya pakai cookie)
    });

  } catch (error) {
    console.error('Login Admin Error:', error);
    sendErrorResponse(res, 'Terjadi kesalahan saat login', 500);
  }
};

/**
 * @desc    Login Guru (ID Guru + PIN 6 Digit)
 * @route   POST /api/auth/login/guru
 * @access  Public
 */
export const loginGuru = async (req, res) => {
  try {
    const { id_guru, pin_validasi, remember_me } = req.body;

    // 1. Cek apakah guru exists dan aktif
    const query = 'SELECT * FROM tb_guru WHERE id_guru = ? AND status = ? LIMIT 1';
    const gurus = await executeQuery(query, [id_guru, 'Aktif']);

    if (gurus.length === 0) {
      return sendErrorResponse(res, 'Guru tidak ditemukan atau tidak aktif', 401);
    }

    const guru = gurus[0];

    // 2. Verify PIN (hashed dengan bcrypt)
    const isPinValid = await bcrypt.compare(pin_validasi, guru.pin_validasi);
    
    if (!isPinValid) {
      return sendErrorResponse(res, 'PIN tidak valid', 401);
    }

    // 3. Generate JWT Token
    // Jika remember_me = true, token berlaku lebih lama (30 hari)
    const tokenExpiry = remember_me ? '30d' : '7d';
    
    const token = generateToken({
      id: guru.id_guru,
      role: 'guru',
      nama: guru.nama_guru
    });

    // 4. Set token sebagai HttpOnly Cookie
    const cookieOptions = {
      expires: new Date(Date.now() + (remember_me ? 30 : 7) * 24 * 60 * 60 * 1000),
      httpOnly: true,
      secure: process.env.NODE_ENV === 'production',
      sameSite: 'strict'
    };
    res.cookie('token', token, cookieOptions);

    // 5. Response
    sendSuccessResponse(res, 'Login berhasil', {
      user: {
        id: guru.id_guru,
        nama: guru.nama_guru,
        nip: guru.nip,
        role: 'guru'
      },
      token
    });

  } catch (error) {
    console.error('Login Guru Error:', error);
    sendErrorResponse(res, 'Terjadi kesalahan saat login', 500);
  }
};

/**
 * @desc    Get Current User (dari token)
 * @route   GET /api/auth/me
 * @access  Private (Guru & Admin)
 */
export const getCurrentUser = async (req, res) => {
  try {
    const { id, role } = req.user; // Dari middleware protect

    let query, user;

    if (role === 'admin') {
      query = 'SELECT id_admin as id, username, nama_lengkap as nama, role FROM tb_admin WHERE id_admin = ?';
      const admins = await executeQuery(query, [id]);
      user = admins[0];
    } else {
      query = 'SELECT id_guru as id, nama_guru as nama, nip FROM tb_guru WHERE id_guru = ?';
      const gurus = await executeQuery(query, [id]);
      user = gurus[0];
    }

    if (!user) {
      return sendErrorResponse(res, 'User tidak ditemukan', 404);
    }

    sendSuccessResponse(res, 'Data user berhasil diambil', {
      ...user,
      role
    });

  } catch (error) {
    console.error('Get User Error:', error);
    sendErrorResponse(res, 'Terjadi kesalahan saat mengambil data user', 500);
  }
};

/**
 * @desc    Logout (Clear Cookie)
 * @route   POST /api/auth/logout
 * @access  Private
 */
export const logout = (req, res) => {
  res.cookie('token', 'none', {
    expires: new Date(Date.now() + 10 * 1000), // Expire dalam 10 detik
    httpOnly: true
  });

  sendSuccessResponse(res, 'Logout berhasil');
};

/**
 * @desc    Get Daftar Guru (untuk dropdown login)
 * @route   GET /api/auth/guru-list
 * @access  Public
 */
export const getGuruList = async (req, res) => {
  try {
    const query = `
      SELECT id_guru, nama_guru, nip 
      FROM tb_guru 
      WHERE status = 'Aktif' 
      ORDER BY nama_guru ASC
    `;
    
    const gurus = await executeQuery(query);

    sendSuccessResponse(res, 'Daftar guru berhasil diambil', gurus);

  } catch (error) {
    console.error('Get Guru List Error:', error);
    sendErrorResponse(res, 'Terjadi kesalahan saat mengambil daftar guru', 500);
  }
};