import jwt from 'jsonwebtoken';

export const generateToken = (userId, userType) => {
  return jwt.sign(
    { userId, userType },
    process.env.JWT_SECRET,
    { expiresIn: '24h' }
  );
};

export const getClientIp = (req) => {
  return req.headers['cf-connecting-ip'] || 
         req.headers['x-forwarded-for'] || 
         req.headers['x-real-ip'] || 
         req.connection.remoteAddress || 
         req.socket.remoteAddress ||
         (req.connection.socket ? req.connection.socket.remoteAddress : null);
};

export const hashPassword = async (password) => {
  const bcrypt = await import('bcrypt');
  return bcrypt.hash(password, parseInt(process.env.BCRYPT_ROUNDS) || 10);
};

export const verifyPassword = async (password, hashedPassword) => {
  const bcrypt = await import('bcrypt');
  return bcrypt.compare(password, hashedPassword);
};
