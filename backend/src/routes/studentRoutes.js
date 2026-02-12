import express from 'express';
import {
  getStudents,
  importStudents,
  getClasses,
  promoteStudents,
  addStudent
} from '../controllers/studentController.js';
import { protect, authorize } from '../middleware/auth.js';
import { uploadExcel } from '../utils/fileUpload.js';

const router = express.Router();

// All routes require authentication
router.use(protect);

// Get Routes (Guru & Admin)
router.get('/', getStudents);
router.get('/classes', getClasses);

// Admin Only Routes
router.post('/', authorize('admin'), addStudent);
router.post('/import', authorize('admin'), uploadExcel.single('file'), importStudents);
router.post('/promote', authorize('admin'), promoteStudents);

export default router;