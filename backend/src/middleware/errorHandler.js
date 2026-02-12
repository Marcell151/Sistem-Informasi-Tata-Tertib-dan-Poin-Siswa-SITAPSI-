/**
 * GLOBAL ERROR HANDLER
 * Menangani semua error yang tidak tertangani di controller
 */
export const errorHandler = (err, req, res, next) => {
  console.error('❌ Error:', err);

  // Default error
  let statusCode = err.statusCode || 500;
  let message = err.message || 'Terjadi kesalahan pada server';

  // MySQL Error Handling
  if (err.code === 'ER_DUP_ENTRY') {
    statusCode = 400;
    message = 'Data sudah ada dalam database';
  }

  if (err.code === 'ER_NO_REFERENCED_ROW_2') {
    statusCode = 400;
    message = 'Data yang direferensikan tidak ditemukan';
  }

  res.status(statusCode).json({
    success: false,
    message: message,
    ...(process.env.NODE_ENV === 'development' && { stack: err.stack })
  });
};

/**
 * 404 NOT FOUND HANDLER
 */
export const notFoundHandler = (req, res) => {
  res.status(404).json({
    success: false,
    message: `Endpoint ${req.originalUrl} tidak ditemukan`
  });
};