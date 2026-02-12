import express from 'express';
import { 
  loginAdmin, 
  loginGuru, 
  getCurrentUser, 
  logout,
  getGuruList 
} from '../controllers/authController.js';
import { protect } from '../middleware/auth.js';
import { validate, loginAdminSchema, loginGuruSchema } from '../middleware/validator.js';
import { loginLimiter } from '../middleware/rateLimiter.js';

const router = express.Router();

// Public Routes
router.post('/login/admin', loginLimiter, validate(loginAdminSchema), loginAdmin);
router.post('/login/guru', loginLimiter, validate(loginGuruSchema), loginGuru);
router.get('/guru-list', getGuruList);

// Protected Routes
router.get('/me', protect, getCurrentUser);
router.post('/logout', protect, logout);

export default router;