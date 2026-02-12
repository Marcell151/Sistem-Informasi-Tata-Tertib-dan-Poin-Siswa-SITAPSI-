/**
 * STANDARD SUCCESS RESPONSE
 * @param {Object} res - Express response object
 * @param {string} message - Success message
 * @param {*} data - Response data
 * @param {number} statusCode - HTTP status code (default: 200)
 */
export const sendSuccessResponse = (res, message, data = null, statusCode = 200) => {
  return res.status(statusCode).json({
    success: true,
    message: message,
    data: data
  });
};

/**
 * STANDARD ERROR RESPONSE
 * @param {Object} res - Express response object
 * @param {string} message - Error message
 * @param {number} statusCode - HTTP status code (default: 400)
 */
export const sendErrorResponse = (res, message, statusCode = 400) => {
  return res.status(statusCode).json({
    success: false,
    message: message
  });
};