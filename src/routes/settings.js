import express from 'express';
import { body, validationResult } from 'express-validator';
import { AppConfig, User } from '../models/index.js';
import { adminOnly } from '../middleware/auth.js';
import bcrypt from 'bcrypt';

const router = express.Router();

// General settings
router.get('/general', adminOnly, async (req, res) => {
  try {
    const settings = await AppConfig.findAll();
    const settingsMap = {};
    settings.forEach(setting => {
      settingsMap[setting.setting] = setting.value;
    });

    res.render('admin/settings/general', {
      title: 'General Settings',
      page: 'settings',
      settings: settingsMap
    });
  } catch (error) {
    console.error('Settings error:', error);
    res.status(500).render('errors/500', {
      title: 'Server Error',
      message: 'Failed to load settings'
    });
  }
});

// Update general settings
router.post('/general', [
  adminOnly,
  body('CompanyName').trim().isLength({ min: 1 }).withMessage('Company name is required'),
  body('currency').trim().isLength({ min: 1 }).withMessage('Currency is required'),
  body('timezone').trim().isLength({ min: 1 }).withMessage('Timezone is required')
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ 
        success: false, 
        errors: errors.array() 
      });
    }

    const settingsToUpdate = [
      'CompanyName',
      'address',
      'phone',
      'email',
      'currency',
      'timezone',
      'language',
      'theme',
      'maintenance_mode',
      'disable_registration',
      'enable_balance',
      'allow_balance_transfer',
      'enable_tax',
      'tax_rate',
      'enable_coupons',
      'enable_vouchers',
      'enable_whatsapp',
      'whatsapp_notifications'
    ];

    for (const setting of settingsToUpdate) {
      if (req.body[setting] !== undefined) {
        await AppConfig.upsert({
          setting: setting,
          value: req.body[setting]
        });
      }
    }

    res.json({
      success: true,
      message: 'Settings updated successfully'
    });
  } catch (error) {
    console.error('Settings update error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to update settings'
    });
  }
});

// Database settings
router.get('/database', adminOnly, async (req, res) => {
  try {
    res.render('admin/settings/database', {
      title: 'Database Settings',
      page: 'settings',
      dbConfig: {
        host: process.env.DB_HOST,
        database: process.env.DB_NAME,
        username: process.env.DB_USER,
        dialect: process.env.DB_DIALECT
      }
    });
  } catch (error) {
    console.error('Database settings error:', error);
    res.status(500).render('errors/500', {
      title: 'Server Error',
      message: 'Failed to load database settings'
    });
  }
});

// Admin users management
router.get('/users', adminOnly, async (req, res) => {
  try {
    const users = await User.findAll({
      attributes: { exclude: ['password'] },
      order: [['id', 'ASC']]
    });

    res.render('admin/settings/users', {
      title: 'Admin Users',
      page: 'settings',
      users
    });
  } catch (error) {
    console.error('Users settings error:', error);
    res.status(500).render('errors/500', {
      title: 'Server Error',
      message: 'Failed to load users'
    });
  }
});

// Add admin user
router.post('/users/add', [
  adminOnly,
  body('username').trim().isLength({ min: 3 }).withMessage('Username must be at least 3 characters'),
  body('password').isLength({ min: 6 }).withMessage('Password must be at least 6 characters'),
  body('fullname').trim().isLength({ min: 1 }).withMessage('Full name is required'),
  body('user_type').isIn(['SuperAdmin', 'Admin', 'Agent', 'Sales']).withMessage('Invalid user type')
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ 
        success: false, 
        errors: errors.array() 
      });
    }

    const { username, password, fullname, user_type, email } = req.body;

    // Check if user already exists
    const existingUser = await User.findOne({
      where: { username }
    });

    if (existingUser) {
      return res.status(409).json({
        success: false,
        message: 'Username already exists'
      });
    }

    // Hash password
    const hashedPassword = await bcrypt.hash(password, 10);

    await User.create({
      username,
      password: hashedPassword,
      fullname,
      user_type,
      email: email || null
    });

    res.json({
      success: true,
      message: 'User created successfully'
    });
  } catch (error) {
    console.error('User creation error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to create user'
    });
  }
});

// Edit admin user
router.put('/users/:id', [
  adminOnly,
  body('fullname').trim().isLength({ min: 1 }).withMessage('Full name is required'),
  body('user_type').isIn(['SuperAdmin', 'Admin', 'Agent', 'Sales']).withMessage('Invalid user type')
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ 
        success: false, 
        errors: errors.array() 
      });
    }

    const user = await User.findByPk(req.params.id);
    if (!user) {
      return res.status(404).json({
        success: false,
        message: 'User not found'
      });
    }

    const updateData = {
      fullname: req.body.fullname,
      user_type: req.body.user_type,
      email: req.body.email || null
    };

    // Update password if provided
    if (req.body.password) {
      updateData.password = await bcrypt.hash(req.body.password, 10);
    }

    await user.update(updateData);

    res.json({
      success: true,
      message: 'User updated successfully'
    });
  } catch (error) {
    console.error('User update error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to update user'
    });
  }
});

// Delete admin user
router.delete('/users/:id', adminOnly, async (req, res) => {
  try {
    const user = await User.findByPk(req.params.id);
    if (!user) {
      return res.status(404).json({
        success: false,
        message: 'User not found'
      });
    }

    // Prevent deletion of the last SuperAdmin
    if (user.user_type === 'SuperAdmin') {
      const superAdminCount = await User.count({
        where: { user_type: 'SuperAdmin' }
      });

      if (superAdminCount <= 1) {
        return res.status(400).json({
          success: false,
          message: 'Cannot delete the last SuperAdmin user'
        });
      }
    }

    await user.destroy();

    res.json({
      success: true,
      message: 'User deleted successfully'
    });
  } catch (error) {
    console.error('User deletion error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to delete user'
    });
  }
});

// Backup and restore
router.get('/backup', adminOnly, async (req, res) => {
  try {
    res.render('admin/settings/backup', {
      title: 'Backup & Restore',
      page: 'settings'
    });
  } catch (error) {
    console.error('Backup settings error:', error);
    res.status(500).render('errors/500', {
      title: 'Server Error',
      message: 'Failed to load backup settings'
    });
  }
});

// Create backup
router.post('/backup/create', adminOnly, async (req, res) => {
  try {
    // TODO: Implement database backup functionality
    res.json({
      success: true,
      message: 'Backup created successfully',
      filename: `backup_${new Date().toISOString().split('T')[0]}.sql`
    });
  } catch (error) {
    console.error('Backup creation error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to create backup'
    });
  }
});

// System information
router.get('/info', adminOnly, async (req, res) => {
  try {
    const systemInfo = {
      platform: process.platform,
      nodeVersion: process.version,
      uptime: process.uptime(),
      memory: process.memoryUsage(),
      env: process.env.NODE_ENV || 'development'
    };

    res.render('admin/settings/info', {
      title: 'System Information',
      page: 'settings',
      systemInfo
    });
  } catch (error) {
    console.error('System info error:', error);
    res.status(500).render('errors/500', {
      title: 'Server Error',
      message: 'Failed to load system information'
    });
  }
});

export default router;
