import jwt from 'jsonwebtoken';
import config from '../config/env.js';
import { sendErrorResponse } from '../utils/responseHandler.js';

/**
 * Verify JWT Token dari HttpOnly Cookie
 * Middleware ini melindungi route yang memerlukan autentikasi
 */
export const protect = async (req, res, next) => {
  try {
    let token;

    // 1. Ambil token dari cookie (lebih aman dari localStorage)
    if (req.cookies && req.cookies.token) {
      token = req.cookies.token;
    }
    // Fallback: Ambil dari Authorization header (untuk testing dengan Postman)
    else if (req.headers.authorization && req.headers.authorization.startsWith('Bearer')) {
      token = req.headers.authorization.split(' ')[1];
    }

    // 2. Cek apakah token ada
    if (!token) {
      return sendErrorResponse(res, 'Sesi tidak valid. Silakan login kembali.', 401);
    }

    // 3. Verify token
    const decoded = jwt.verify(token, config.jwt.secret);

    // 4. Simpan user info ke request object (untuk digunakan di controller)
    req.user = {
      id: decoded.id,
      role: decoded.role, // 'admin' atau 'guru'
      nama: decoded.nama
    };

    next();
  } catch (error) {
    console.error('Auth Error:', error.message);
    
    if (error.name === 'JsonWebTokenError') {
      return sendErrorResponse(res, 'Token tidak valid', 401);
    }
    if (error.name === 'TokenExpiredError') {
      return sendErrorResponse(res, 'Sesi telah berakhir. Silakan login kembali.', 401);
    }
    
    return sendErrorResponse(res, 'Autentikasi gagal', 401);
  }
};

/**
 * Role-Based Access Control
 * Membatasi akses berdasarkan role (Admin/Guru)
 * Usage: protect, authorize('admin')
 */
export const authorize = (...roles) => {
  return (req, res, next) => {
    if (!roles.includes(req.user.role)) {
      return sendErrorResponse(
        res, 
        'Anda tidak memiliki hak akses untuk mengakses fitur ini', 
        403
      );
    }
    next();
  };
};

/**
 * Generate JWT Token
 * @param {Object} payload - Data yang akan di-encode dalam token
 * @returns {string} JWT token
 */
export const generateToken = (payload) => {
  return jwt.sign(payload, config.jwt.secret, {
    expiresIn: config.jwt.expiresIn
  });
};

/**
 * Set JWT sebagai HttpOnly Cookie (XSS Protection)
 * @param {Object} res - Express response object
 * @param {string} token - JWT token
 */
export const setTokenCookie = (res, token) => {
  const options = {
    expires: new Date(Date.now() + config.jwt.cookieExpire * 24 * 60 * 60 * 1000),
    httpOnly: true, // Tidak bisa diakses via JavaScript (XSS Protection)
    secure: config.server.env === 'production', // HTTPS only di production
    sameSite: 'strict' // CSRF Protection
  };

  res.cookie('token', token, options);
};