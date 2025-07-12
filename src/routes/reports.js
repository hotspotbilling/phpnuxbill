import express from 'express';
import { body, query, validationResult } from 'express-validator';
import { Op, fn, col, literal } from 'sequelize';
import { Transaction, Customer, Plan, UserRecharge, PaymentGateway, Log } from '../models/index.js';
import { adminOnly } from '../middleware/auth.js';

const router = express.Router();

// Reports dashboard
router.get('/dashboard', adminOnly, async (req, res) => {
  try {
    const today = new Date();
    const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    const endOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);

    // Get monthly statistics
    const monthlyStats = await Transaction.findAll({
      where: {
        recharged_on: {
          [Op.between]: [startOfMonth, endOfMonth]
        },
        status: 'paid'
      },
      attributes: [
        [fn('SUM', col('price')), 'total_revenue'],
        [fn('COUNT', col('id')), 'total_transactions'],
        [fn('DATE', col('recharged_on')), 'date']
      ],
      group: ['date'],
      order: [['date', 'ASC']]
    });

    // Get top plans
    const topPlans = await Transaction.findAll({
      where: {
        recharged_on: {
          [Op.between]: [startOfMonth, endOfMonth]
        },
        status: 'paid'
      },
      attributes: [
        'plan_name',
        [fn('COUNT', col('id')), 'count'],
        [fn('SUM', col('price')), 'revenue']
      ],
      group: ['plan_name'],
      order: [[fn('COUNT', col('id')), 'DESC']],
      limit: 10
    });

    // Get customer statistics
    const customerStats = {
      total: await Customer.count(),
      active: await Customer.count({ where: { status: 'Active' } }),
      inactive: await Customer.count({ where: { status: 'Inactive' } })
    };

    // Get recent transactions
    const recentTransactions = await Transaction.findAll({
      limit: 10,
      order: [['recharged_on', 'DESC']],
      include: [
        { model: Customer, attributes: ['username', 'fullname'] }
      ]
    });

    res.render('admin/reports/dashboard', {
      title: 'Reports Dashboard',
      page: 'reports',
      monthlyStats,
      topPlans,
      customerStats,
      recentTransactions
    });
  } catch (error) {
    console.error('Reports dashboard error:', error);
    res.status(500).render('errors/500', {
      title: 'Server Error',
      message: 'Failed to load reports dashboard'
    });
  }
});

// Revenue report
router.get('/revenue', adminOnly, async (req, res) => {
  try {
    const { start_date, end_date, period = 'daily' } = req.query;

    let startDate = start_date ? new Date(start_date) : new Date(new Date().getFullYear(), new Date().getMonth(), 1);
    let endDate = end_date ? new Date(end_date) : new Date();

    let groupBy = 'DATE(recharged_on)';
    if (period === 'monthly') {
      groupBy = 'DATE_FORMAT(recharged_on, "%Y-%m")';
    } else if (period === 'yearly') {
      groupBy = 'YEAR(recharged_on)';
    }

    const revenueData = await Transaction.findAll({
      where: {
        recharged_on: {
          [Op.between]: [startDate, endDate]
        },
        status: 'paid'
      },
      attributes: [
        [literal(groupBy), 'period'],
        [fn('SUM', col('price')), 'revenue'],
        [fn('COUNT', col('id')), 'transactions']
      ],
      group: [literal(groupBy)],
      order: [[literal(groupBy), 'ASC']]
    });

    const totalRevenue = revenueData.reduce((sum, item) => sum + parseFloat(item.dataValues.revenue), 0);
    const totalTransactions = revenueData.reduce((sum, item) => sum + parseInt(item.dataValues.transactions), 0);

    res.render('admin/reports/revenue', {
      title: 'Revenue Report',
      page: 'reports',
      revenueData,
      totalRevenue,
      totalTransactions,
      filters: { start_date, end_date, period }
    });
  } catch (error) {
    console.error('Revenue report error:', error);
    res.status(500).render('errors/500', {
      title: 'Server Error',
      message: 'Failed to load revenue report'
    });
  }
});

// Customer report
router.get('/customers', adminOnly, async (req, res) => {
  try {
    const { status, registration_date, page = 1, limit = 20 } = req.query;
    const offset = (page - 1) * limit;

    let whereClause = {};
    if (status) {
      whereClause.status = status;
    }
    if (registration_date) {
      const regDate = new Date(registration_date);
      whereClause.created_at = {
        [Op.gte]: regDate,
        [Op.lt]: new Date(regDate.getTime() + 24 * 60 * 60 * 1000)
      };
    }

    const customers = await Customer.findAndCountAll({
      where: whereClause,
      attributes: { exclude: ['password'] },
      include: [
        {
          model: UserRecharge,
          where: { status: 'on' },
          required: false,
          include: [{ model: Plan, attributes: ['name_plan'] }]
        }
      ],
      offset,
      limit: parseInt(limit),
      order: [['created_at', 'DESC']]
    });

    // Customer statistics
    const customerStats = {
      total: await Customer.count(),
      active: await Customer.count({ where: { status: 'Active' } }),
      inactive: await Customer.count({ where: { status: 'Inactive' } }),
      thisMonth: await Customer.count({
        where: {
          created_at: {
            [Op.gte]: new Date(new Date().getFullYear(), new Date().getMonth(), 1)
          }
        }
      })
    };

    res.render('admin/reports/customers', {
      title: 'Customer Report',
      page: 'reports',
      customers: customers.rows,
      customerStats,
      pagination: {
        page: parseInt(page),
        limit: parseInt(limit),
        total: customers.count,
        totalPages: Math.ceil(customers.count / limit)
      },
      filters: { status, registration_date }
    });
  } catch (error) {
    console.error('Customer report error:', error);
    res.status(500).render('errors/500', {
      title: 'Server Error',
      message: 'Failed to load customer report'
    });
  }
});

// Plans report
router.get('/plans', adminOnly, async (req, res) => {
  try {
    const { start_date, end_date } = req.query;

    let startDate = start_date ? new Date(start_date) : new Date(new Date().getFullYear(), new Date().getMonth(), 1);
    let endDate = end_date ? new Date(end_date) : new Date();

    const planStats = await Transaction.findAll({
      where: {
        recharged_on: {
          [Op.between]: [startDate, endDate]
        },
        status: 'paid'
      },
      attributes: [
        'plan_name',
        'type',
        [fn('COUNT', col('id')), 'total_sales'],
        [fn('SUM', col('price')), 'total_revenue']
      ],
      group: ['plan_name', 'type'],
      order: [[fn('COUNT', col('id')), 'DESC']]
    });

    const totalRevenue = planStats.reduce((sum, item) => sum + parseFloat(item.dataValues.total_revenue), 0);
    const totalSales = planStats.reduce((sum, item) => sum + parseInt(item.dataValues.total_sales), 0);

    res.render('admin/reports/plans', {
      title: 'Plans Report',
      page: 'reports',
      planStats,
      totalRevenue,
      totalSales,
      filters: { start_date, end_date }
    });
  } catch (error) {
    console.error('Plans report error:', error);
    res.status(500).render('errors/500', {
      title: 'Server Error',
      message: 'Failed to load plans report'
    });
  }
});

// Export reports
router.get('/export/:type', [
  adminOnly,
  query('format').isIn(['csv', 'json']).withMessage('Invalid export format')
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ 
        success: false, 
        errors: errors.array() 
      });
    }

    const { type } = req.params;
    const { format, start_date, end_date } = req.query;

    let startDate = start_date ? new Date(start_date) : new Date(new Date().getFullYear(), new Date().getMonth(), 1);
    let endDate = end_date ? new Date(end_date) : new Date();

    let data = [];
    let filename = '';

    switch (type) {
      case 'transactions':
        data = await Transaction.findAll({
          where: {
            recharged_on: {
              [Op.between]: [startDate, endDate]
            }
          },
          include: [
            { model: Customer, attributes: ['username', 'fullname'] }
          ],
          order: [['recharged_on', 'DESC']]
        });
        filename = `transactions_${startDate.toISOString().split('T')[0]}_${endDate.toISOString().split('T')[0]}`;
        break;

      case 'customers':
        data = await Customer.findAll({
          attributes: { exclude: ['password'] },
          include: [
            {
              model: UserRecharge,
              where: { status: 'on' },
              required: false,
              include: [{ model: Plan, attributes: ['name_plan'] }]
            }
          ],
          order: [['created_at', 'DESC']]
        });
        filename = `customers_${new Date().toISOString().split('T')[0]}`;
        break;

      case 'revenue':
        data = await Transaction.findAll({
          where: {
            recharged_on: {
              [Op.between]: [startDate, endDate]
            },
            status: 'paid'
          },
          attributes: [
            [fn('DATE', col('recharged_on')), 'date'],
            [fn('SUM', col('price')), 'revenue'],
            [fn('COUNT', col('id')), 'transactions']
          ],
          group: [fn('DATE', col('recharged_on'))],
          order: [[fn('DATE', col('recharged_on')), 'ASC']]
        });
        filename = `revenue_${startDate.toISOString().split('T')[0]}_${endDate.toISOString().split('T')[0]}`;
        break;

      default:
        return res.status(400).json({
          success: false,
          message: 'Invalid export type'
        });
    }

    if (format === 'csv') {
      res.setHeader('Content-Type', 'text/csv');
      res.setHeader('Content-Disposition', `attachment; filename="${filename}.csv"`);
      
      // Convert to CSV
      if (data.length > 0) {
        const headers = Object.keys(data[0].dataValues || data[0]);
        let csv = headers.join(',') + '\n';
        
        data.forEach(row => {
          const values = headers.map(header => {
            const value = row.dataValues ? row.dataValues[header] : row[header];
            return typeof value === 'string' ? `"${value}"` : value;
          });
          csv += values.join(',') + '\n';
        });
        
        res.send(csv);
      } else {
        res.send('No data available');
      }
    } else {
      res.setHeader('Content-Type', 'application/json');
      res.setHeader('Content-Disposition', `attachment; filename="${filename}.json"`);
      res.json(data);
    }
  } catch (error) {
    console.error('Export error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to export data'
    });
  }
});

// System logs
router.get('/logs', adminOnly, async (req, res) => {
  try {
    const { type, date, page = 1, limit = 50 } = req.query;
    const offset = (page - 1) * limit;

    let whereClause = {};
    if (type) {
      whereClause.type = type;
    }
    if (date) {
      const logDate = new Date(date);
      whereClause.date = {
        [Op.gte]: logDate,
        [Op.lt]: new Date(logDate.getTime() + 24 * 60 * 60 * 1000)
      };
    }

    const logs = await Log.findAndCountAll({
      where: whereClause,
      offset,
      limit: parseInt(limit),
      order: [['date', 'DESC']]
    });

    const logTypes = await Log.findAll({
      attributes: [[fn('DISTINCT', col('type')), 'type']],
      raw: true
    });

    res.render('admin/reports/logs', {
      title: 'System Logs',
      page: 'reports',
      logs: logs.rows,
      logTypes: logTypes.map(t => t.type),
      pagination: {
        page: parseInt(page),
        limit: parseInt(limit),
        total: logs.count,
        totalPages: Math.ceil(logs.count / limit)
      },
      filters: { type, date }
    });
  } catch (error) {
    console.error('Logs report error:', error);
    res.status(500).render('errors/500', {
      title: 'Server Error',
      message: 'Failed to load logs'
    });
  }
});

export default router;
