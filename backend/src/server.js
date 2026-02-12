import express from 'express';
import cookieParser from 'cookie-parser';
import cors from 'cors';
import helmet from 'helmet';
import xss from 'xss-clean';
import config from './config/env.js';
import { testConnection } from './config/database.js';
import { generalLimiter } from './middleware/rateLimiter.js';
import { errorHandler, notFoundHandler } from './middleware/errorHandler.js';

// ✅ IMPORT ROUTES
import authRoutes from './routes/authRoutes.js';
import violationRoutes from './routes/violationRoutes.js';
import studentRoutes from './routes/studentRoutes.js';

const app = express();

/**
 * ========================================
 * SECURITY MIDDLEWARE
 * ========================================
 */
app.use(helmet());
app.use(xss());
app.use(cors({
  origin: config.server.env === 'production' 
    ? 'https://yourdomain.com'
    : 'http://localhost:5173',
  credentials: true
}));
app.use('/api/', generalLimiter);

/**
 * ========================================
 * BODY PARSER MIDDLEWARE
 * ========================================
 */
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true, limit: '10mb' }));
app.use(cookieParser());

/**
 * ========================================
 * STATIC FILES
 * ========================================
 */
app.use('/uploads', express.static('uploads'));

/**
 * ========================================
 * API ROUTES
 * ========================================
 */
app.get('/', (req, res) => {
  res.json({
    success: true,
    message: 'SITAPSI API Server Running',
    version: '1.0.0',
    environment: config.server.env
  });
});

// ✅ REGISTER ROUTES
app.use('/api/auth', authRoutes);
app.use('/api/violations', violationRoutes);
app.use('/api/students', studentRoutes);

/**
 * ========================================
 * ERROR HANDLING
 * ========================================
 */
app.use(notFoundHandler);
app.use(errorHandler);

/**
 * ========================================
 * START SERVER
 * ========================================
 */
const PORT = config.server.port;

const startServer = async () => {
  try {
    await testConnection();
    
    app.listen(PORT, () => {
      console.log('\n🚀 ========================================');
      console.log(`   SITAPSI Server Running`);
      console.log(`   Environment: ${config.server.env}`);
      console.log(`   Port: ${PORT}`);
      console.log(`   URL: ${config.server.baseUrl}`);
      console.log('========================================\n');
    });
  } catch (error) {
    console.error('Failed to start server:', error);
    process.exit(1);
  }
};

startServer();

export default app;