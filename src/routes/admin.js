import express from 'express';
import { User } from '../models/index.js';
import { superAdminOnly } from '../middleware/auth.js';

const router = express.Router();

// Admin management placeholder
router.get('/', superAdminOnly, async (req, res) => {
  try {
    const users = await User.findAll({
      order: [['created_at', 'DESC']],
      attributes: ['id', 'username', 'fullname', 'email', 'user_type', 'status', 'last_login']
    });

    res.render('admin/users', {
      title: 'Admin Users',
      users
    });

  } catch (error) {
    console.error('Admin users error:', error);
    res.status(500).render('errors/500', {
      title: 'Server Error',
      message: 'Failed to load admin users'
    });
  }
});

export default router;
