import rateLimit from 'express-rate-limit';
import config from '../config/env.js';

/**
 * GENERAL API RATE LIMITER
 * Membatasi request per IP untuk mencegah abuse
 */
export const generalLimiter = rateLimit({
  windowMs: config.rateLimit.windowMs,
  max: config.rateLimit.max,
  message: {
    success: false,
    message: 'Terlalu banyak permintaan dari IP ini. Silakan coba lagi nanti.'
  },
  standardHeaders: true,
  legacyHeaders: false
});

/**
 * LOGIN RATE LIMITER (Strict)
 * Mencegah Brute Force Attack pada endpoint login
 */
export const loginLimiter = rateLimit({
  windowMs: 15 * 60 * 1000, // 15 menit
  max: 5, // Maksimal 5 login attempts
  skipSuccessfulRequests: true, // Reset counter jika login berhasil
  message: {
    success: false,
    message: 'Terlalu banyak percobaan login gagal. Akun dikunci selama 15 menit.'
  }
});

/**
 * INPUT PELANGGARAN RATE LIMITER
 * Mencegah spam input data
 */
export const inputLimiter = rateLimit({
  windowMs: 1 * 60 * 1000, // 1 menit
  max: 30, // Maksimal 30 input per menit (untuk guru yang sedang piket ramai)
  message: {
    success: false,
    message: 'Input terlalu cepat. Harap tunggu sebentar.'
  }
});