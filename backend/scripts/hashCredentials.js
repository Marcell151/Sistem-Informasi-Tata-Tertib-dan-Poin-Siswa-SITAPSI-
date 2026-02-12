import bcrypt from 'bcryptjs';

const hashPassword = async (password) => {
  const salt = await bcrypt.genSalt(10);
  return await bcrypt.hash(password, salt);
};

// Generate hashed credentials
(async () => {
  console.log('\n🔐 Hashed Credentials for Testing:\n');
  
  const adminPassword = await hashPassword('admin123');
  console.log('Admin Password (admin123):', adminPassword);
  
  const guruPIN = await hashPassword('123456');
  console.log('Guru PIN (123456):', guruPIN);
  
  console.log('\n✅ Copy nilai di atas ke database Anda!\n');
})();