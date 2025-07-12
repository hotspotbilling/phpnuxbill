import express from 'express';
import bcrypt from 'bcrypt';
import jwt from 'jsonwebtoken';
import { body, validationResult } from 'express-validator';
import { User, Customer, Log } from '../models/index.js';
import { generateToken, getClientIp } from '../utils/auth.js';

const router = express.Router();

// Admin Login Page
router.get('/admin/login', (req, res) => {
  res.render('auth/admin-login', { 
    title: 'Admin Login',
    error: req.query.error 
  });
});

// Admin Login
router.post('/admin/login', [
  body('username').notEmpty().trim().escape(),
  body('password').notEmpty()
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ 
        success: false, 
        message: 'Invalid input data',
        errors: errors.array()
      });
    }

    const { username, password } = req.body;

    // Find admin user
    const admin = await User.findOne({ where: { username } });
    if (!admin) {
      await Log.create({
        type: 'admin_login',
        description: `Failed login attempt - username: ${username}`,
        userid: 0,
        ip: getClientIp(req)
      });
      return res.status(401).json({ 
        success: false, 
        message: 'Invalid username or password' 
      });
    }

    // Check password
    const isValidPassword = await bcrypt.compare(password, admin.password);
    if (!isValidPassword) {
      await Log.create({
        type: 'admin_login',
        description: `Failed login attempt - username: ${username}`,
        userid: admin.id,
        ip: getClientIp(req)
      });
      return res.status(401).json({ 
        success: false, 
        message: 'Invalid username or password' 
      });
    }

    // Update last login
    await admin.update({ last_login: new Date() });

    // Generate JWT token
    const token = generateToken(admin.id, 'admin');

    // Log successful login
    await Log.create({
      type: 'admin_login',
      description: `Login successful - username: ${username}`,
      userid: admin.id,
      ip: getClientIp(req)
    });

    // Set cookie
    res.cookie('token', token, {
      httpOnly: true,
      secure: process.env.NODE_ENV === 'production',
      maxAge: 24 * 60 * 60 * 1000 // 24 hours
    });

    res.json({
      success: true,
      message: 'Login successful',
      user: {
        id: admin.id,
        username: admin.username,
        fullname: admin.fullname,
        user_type: admin.user_type,
        email: admin.email
      },
      token
    });

  } catch (error) {
    console.error('Admin login error:', error);
    res.status(500).json({ 
      success: false, 
      message: 'Server error' 
    });
  }
});

// Customer Login
router.post('/customer/login', [
  body('username').notEmpty().trim().escape(),
  body('password').notEmpty()
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ 
        success: false, 
        message: 'Invalid input data',
        errors: errors.array()
      });
    }

    const { username, password } = req.body;

    // Find customer
    const customer = await Customer.findOne({ where: { username } });
    if (!customer) {
      await Log.create({
        type: 'customer_login',
        description: `Failed login attempt - username: ${username}`,
        userid: 0,
        ip: getClientIp(req)
      });
      return res.status(401).json({ 
        success: false, 
        message: 'Invalid username or password' 
      });
    }

    // Check password
    const isValidPassword = await bcrypt.compare(password, customer.password);
    if (!isValidPassword) {
      await Log.create({
        type: 'customer_login',
        description: `Failed login attempt - username: ${username}`,
        userid: customer.id,
        ip: getClientIp(req)
      });
      return res.status(401).json({ 
        success: false, 
        message: 'Invalid username or password' 
      });
    }

    // Check if customer is active
    if (customer.status !== 'Active') {
      return res.status(401).json({ 
        success: false, 
        message: `Account status: ${customer.status}` 
      });
    }

    // Update last login
    await customer.update({ last_login: new Date() });

    // Generate JWT token
    const token = generateToken(customer.id, 'customer');

    // Log successful login
    await Log.create({
      type: 'customer_login',
      description: `Login successful - username: ${username}`,
      userid: customer.id,
      ip: getClientIp(req)
    });

    // Set cookie
    res.cookie('token', token, {
      httpOnly: true,
      secure: process.env.NODE_ENV === 'production',
      maxAge: 24 * 60 * 60 * 1000 // 24 hours
    });

    res.json({
      success: true,
      message: 'Login successful',
      user: {
        id: customer.id,
        username: customer.username,
        fullname: customer.fullname,
        email: customer.email,
        status: customer.status,
        balance: customer.balance
      },
      token
    });

  } catch (error) {
    console.error('Customer login error:', error);
    res.status(500).json({ 
      success: false, 
      message: 'Server error' 
    });
  }
});

// Logout
router.post('/logout', (req, res) => {
  res.clearCookie('token');
  res.json({ 
    success: true, 
    message: 'Logout successful' 
  });
});

// Get current user
router.get('/me', async (req, res) => {
  try {
    const token = req.header('Authorization')?.replace('Bearer ', '') || req.cookies.token;
    
    if (!token) {
      return res.status(401).json({ 
        success: false, 
        message: 'No token provided' 
      });
    }

    const decoded = jwt.verify(token, process.env.JWT_SECRET);
    
    let user;
    if (decoded.userType === 'admin') {
      user = await User.findByPk(decoded.userId, {
        attributes: ['id', 'username', 'fullname', 'email', 'user_type', 'status']
      });
    } else {
      user = await Customer.findByPk(decoded.userId, {
        attributes: ['id', 'username', 'fullname', 'email', 'status', 'balance']
      });
    }

    if (!user) {
      return res.status(401).json({ 
        success: false, 
        message: 'User not found' 
      });
    }

    res.json({
      success: true,
      user: {
        ...user.toJSON(),
        userType: decoded.userType
      }
    });

  } catch (error) {
    console.error('Get current user error:', error);
    res.status(401).json({ 
      success: false, 
      message: 'Invalid token' 
    });
  }
});

// Login page
router.get('/login', (req, res) => {
  res.render('auth/login', {
    title: 'Login - PHPNuxBill'
  });
});

// Admin login page
router.get('/admin', (req, res) => {
  res.render('auth/admin-login', {
    title: 'Admin Login - PHPNuxBill'
  });
});

export default router;
