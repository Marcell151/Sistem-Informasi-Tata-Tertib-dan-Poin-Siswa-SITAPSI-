import mysql from 'mysql2/promise';
import config from './env.js';

/**
 * CONNECTION POOL - Menangani Multiple Concurrent Connections
 * Menggunakan pool untuk performa tinggi dan mencegah bottleneck
 */
const pool = mysql.createPool({
  host: config.database.host,
  port: config.database.port,
  user: config.database.user,
  password: config.database.password,
  database: config.database.database,
  connectionLimit: config.database.connectionLimit,
  waitForConnections: config.database.waitForConnections,
  queueLimit: config.database.queueLimit,
  enableKeepAlive: true,
  keepAliveInitialDelay: 0
});

/**
 * Test Database Connection
 */
const testConnection = async () => {
  try {
    const connection = await pool.getConnection();
    console.log('✅ Database connected successfully');
    console.log(`📊 Database: ${config.database.database}`);
    console.log(`🔗 Connection Pool: ${config.database.connectionLimit} max connections`);
    connection.release();
    return true;
  } catch (error) {
    console.error('❌ Database connection failed:', error.message);
    throw error;
  }
};

/**
 * Execute Parameterized Query (SQL Injection Safe)
 * @param {string} query - SQL query dengan placeholder ?
 * @param {Array} params - Parameter values
 * @returns {Promise} Query result
 */
const executeQuery = async (query, params = []) => {
  try {
    const [rows] = await pool.execute(query, params);
    return rows;
  } catch (error) {
    console.error('Database Query Error:', error.message);
    throw error;
  }
};

/**
 * Execute Transaction (untuk operasi multiple query)
 * @param {Function} callback - Fungsi yang berisi query-query dalam transaksi
 * @returns {Promise} Transaction result
 */
const executeTransaction = async (callback) => {
  const connection = await pool.getConnection();
  
  try {
    await connection.beginTransaction();
    const result = await callback(connection);
    await connection.commit();
    return result;
  } catch (error) {
    await connection.rollback();
    console.error('Transaction Error:', error.message);
    throw error;
  } finally {
    connection.release();
  }
};

export { pool, testConnection, executeQuery, executeTransaction };
export default pool;