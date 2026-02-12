import express from 'express';
import {
  inputPelanggaran,
  getRiwayatPelanggaran,
  getDetailSiswa,
  getRekapKelas,
  deletePelanggaran,
  getJenisPelanggaran,
  getSanksiRef
} from '../controllers/violationController.js';
import { protect, authorize } from '../middleware/auth.js';
import { validate, inputPelanggaranSchema } from '../middleware/validator.js';
import { inputLimiter } from '../middleware/rateLimiter.js';

const router = express.Router();

// All routes require authentication
router.use(protect);

// Input Pelanggaran (Guru & Admin)
router.post('/input', inputLimiter, validate(inputPelanggaranSchema), inputPelanggaran);

// Get Data (Guru & Admin)
router.get('/types/:id_kategori', getJenisPelanggaran);
router.get('/sanctions', getSanksiRef);
router.get('/student/:id_anggota', getDetailSiswa);
router.get('/recap/class/:id_kelas', getRekapKelas);

// Admin Only
router.get('/history', authorize('admin'), getRiwayatPelanggaran);
router.delete('/:id_transaksi', authorize('admin'), deletePelanggaran);

export default router;