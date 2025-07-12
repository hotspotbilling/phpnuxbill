import jwt from 'jsonwebtoken';
import { User, Customer } from '../models/index.js';

export const authMiddleware = async (req, res, next) => {
  try {
    const token = req.header('Authorization')?.replace('Bearer ', '') || req.cookies.token;
    
    if (!token) {
      return res.status(401).json({ message: 'Access denied. No token provided.' });
    }

    const decoded = jwt.verify(token, process.env.JWT_SECRET);
    
    let user;
    if (decoded.userType === 'admin') {
      user = await User.findByPk(decoded.userId);
    } else {
      user = await Customer.findByPk(decoded.userId);
    }

    if (!user) {
      return res.status(401).json({ message: 'Invalid token.' });
    }

    req.user = user;
    req.userType = decoded.userType;
    next();
  } catch (error) {
    res.status(401).json({ message: 'Invalid token.' });
  }
};

export const adminOnly = (req, res, next) => {
  if (req.userType !== 'admin') {
    return res.status(403).json({ message: 'Access denied. Admin only.' });
  }
  next();
};

export const superAdminOnly = (req, res, next) => {
  if (req.userType !== 'admin' || req.user.user_type !== 'SuperAdmin') {
    return res.status(403).json({ message: 'Access denied. SuperAdmin only.' });
  }
  next();
};

export const checkPermission = (allowedTypes) => {
  return (req, res, next) => {
    if (req.userType === 'admin' && allowedTypes.includes(req.user.user_type)) {
      return next();
    }
    
    if (req.userType === 'customer' && allowedTypes.includes('Customer')) {
      return next();
    }
    
    return res.status(403).json({ message: 'Access denied. Insufficient permissions.' });
  };
};
