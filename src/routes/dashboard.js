import express from 'express';
import { Op } from 'sequelize';
import { Customer, User, UserRecharge, Plan, Router, Log } from '../models/index.js';
import { adminOnly, checkPermission } from '../middleware/auth.js';

const router = express.Router();

// Dashboard main page
router.get('/', adminOnly, async (req, res) => {
  try {
    const user = req.user;
    const startDate = new Date();
    startDate.setDate(1); // First day of current month
    
    const endDate = new Date();
    endDate.setMonth(endDate.getMonth() + 1, 0); // Last day of current month

    // Get statistics based on user type
    let stats = {};
    
    if (['SuperAdmin', 'Admin'].includes(user.user_type)) {
      // Get all statistics for SuperAdmin and Admin
      const [
        totalCustomers,
        activeCustomers,
        totalPlans,
        activeRecharges,
        monthlyRevenue,
        totalRouters,
        recentLogs
      ] = await Promise.all([
        Customer.count(),
        Customer.count({ where: { status: 'Active' } }),
        Plan.count(),
        UserRecharge.count({ where: { status: 'on' } }),
        UserRecharge.sum('price', {
          where: {
            recharged_on: {
              [Op.between]: [startDate, endDate]
            }
          }
        }),
        Router.count(),
        Log.findAll({
          limit: 10,
          order: [['date', 'DESC']]
        })
      ]);

      stats = {
        totalCustomers,
        activeCustomers,
        totalPlans,
        activeRecharges,
        monthlyRevenue: monthlyRevenue || 0,
        totalRouters,
        recentLogs
      };
    } else {
      // Limited statistics for other user types
      const [
        totalCustomers,
        activeCustomers,
        activeRecharges
      ] = await Promise.all([
        Customer.count(),
        Customer.count({ where: { status: 'Active' } }),
        UserRecharge.count({ where: { status: 'on' } })
      ]);

      stats = {
        totalCustomers,
        activeCustomers,
        activeRecharges
      };
    }

    // Get recent activities
    const recentActivities = await Log.findAll({
      limit: 5,
      order: [['date', 'DESC']],
      where: {
        type: {
          [Op.in]: ['customer_login', 'admin_login', 'recharge', 'payment']
        }
      }
    });

    // Get expiring plans (next 7 days)
    const expiringDate = new Date();
    expiringDate.setDate(expiringDate.getDate() + 7);
    
    const expiringPlans = await UserRecharge.findAll({
      where: {
        status: 'on',
        expiration: {
          [Op.between]: [new Date(), expiringDate]
        }
      },
      limit: 10,
      include: [
        {
          model: Customer,
          attributes: ['username', 'fullname', 'email']
        }
      ]
    });

    res.render('dashboard/index', {
      title: 'Dashboard',
      page: 'dashboard',
      user,
      stats,
      recentLogs: stats.recentLogs || recentActivities,
      expiringPlans
    });

  } catch (error) {
    console.error('Dashboard error:', error);
    res.status(500).render('errors/500', {
      title: 'Server Error',
      message: 'Failed to load dashboard'
    });
  }
});

export default router;
