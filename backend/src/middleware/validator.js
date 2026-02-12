import Joi from 'joi';
import { sendErrorResponse } from '../utils/responseHandler.js';

/**
 * VALIDATION SCHEMAS
 * Definisi aturan validasi untuk setiap endpoint
 */

// Schema Login Admin
export const loginAdminSchema = Joi.object({
  username: Joi.string().min(3).max(50).required().messages({
    'string.empty': 'Username harus diisi',
    'string.min': 'Username minimal 3 karakter',
    'any.required': 'Username wajib diisi'
  }),
  password: Joi.string().min(6).required().messages({
    'string.empty': 'Password harus diisi',
    'string.min': 'Password minimal 6 karakter',
    'any.required': 'Password wajib diisi'
  })
});

// Schema Login Guru
export const loginGuruSchema = Joi.object({
  id_guru: Joi.number().integer().positive().required().messages({
    'number.base': 'ID Guru harus berupa angka',
    'any.required': 'ID Guru wajib dipilih'
  }),
  pin_validasi: Joi.string().length(6).pattern(/^[0-9]+$/).required().messages({
    'string.empty': 'PIN harus diisi',
    'string.length': 'PIN harus 6 digit',
    'string.pattern.base': 'PIN hanya boleh berisi angka',
    'any.required': 'PIN wajib diisi'
  }),
  remember_me: Joi.boolean().default(false)
});

// Schema Input Pelanggaran
export const inputPelanggaranSchema = Joi.object({
  id_anggota: Joi.number().integer().positive().required().messages({
    'any.required': 'Siswa harus dipilih'
  }),
  tipe_form: Joi.string().valid('Piket', 'Kelas').required().messages({
    'any.only': 'Tipe form harus Piket atau Kelas',
    'any.required': 'Tipe form wajib diisi'
  }),
  pelanggaran: Joi.array().items(
    Joi.object({
      id_jenis: Joi.number().integer().positive().required(),
      poin: Joi.number().integer().positive().required()
    })
  ).min(1).required().messages({
    'array.min': 'Minimal pilih 1 pelanggaran',
    'any.required': 'Data pelanggaran wajib diisi'
  }),
  sanksi: Joi.array().items(Joi.number().integer().positive()).default([])
});

/**
 * MIDDLEWARE VALIDATOR
 * Validasi request body berdasarkan schema
 */
export const validate = (schema) => {
  return (req, res, next) => {
    const { error, value } = schema.validate(req.body, {
      abortEarly: false, // Tampilkan semua error sekaligus
      stripUnknown: true // Hapus field yang tidak ada di schema (security)
    });

    if (error) {
      const errors = error.details.map(detail => detail.message);
      return sendErrorResponse(res, errors.join(', '), 400);
    }

    // Replace req.body dengan data yang sudah tervalidasi
    req.body = value;
    next();
  };
};