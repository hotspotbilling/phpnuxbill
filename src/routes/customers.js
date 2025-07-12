import express from 'express';
import { Op } from 'sequelize';
import { Customer, UserRecharge, Plan } from '../models/index.js';
import { adminOnly, checkPermission } from '../middleware/auth.js';
import { body, validationResult } from 'express-validator';
import { hashPassword } from '../utils/auth.js';

const router = express.Router();

// List customers
router.get('/', adminOnly, async (req, res) => {
  try {
    const { page = 1, limit = 20, search = '' } = req.query;
    const offset = (page - 1) * limit;

    const whereClause = search ? {
      [Op.or]: [
        { username: { [Op.like]: `%${search}%` } },
        { fullname: { [Op.like]: `%${search}%` } },
        { email: { [Op.like]: `%${search}%` } }
      ]
    } : {};

    const { count, rows: customers } = await Customer.findAndCountAll({
      where: whereClause,
      limit: parseInt(limit),
      offset: parseInt(offset),
      order: [['created_at', 'DESC']]
    });

    res.render('customers/list', {
      title: 'Customers',
      customers,
      pagination: {
        currentPage: parseInt(page),
        totalPages: Math.ceil(count / limit),
        totalItems: count,
        limit: parseInt(limit)
      },
      search
    });

  } catch (error) {
    console.error('Customer list error:', error);
    res.status(500).render('errors/500', {
      title: 'Server Error',
      message: 'Failed to load customers'
    });
  }
});

// Add customer form
router.get('/add', checkPermission(['SuperAdmin', 'Admin', 'Agent', 'Sales']), (req, res) => {
  res.render('customers/add', {
    title: 'Add Customer'
  });
});

// Create customer
router.post('/', [
  body('username').notEmpty().trim().escape(),
  body('fullname').notEmpty().trim().escape(),
  body('password').isLength({ min: 6 }),
  body('email').optional().isEmail().normalizeEmail()
], checkPermission(['SuperAdmin', 'Admin', 'Agent', 'Sales']), async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({
        success: false,
        errors: errors.array()
      });
    }

    const { username, fullname, password, email, phone_number, address, service_type, account_type } = req.body;

    // Check if username already exists
    const existingCustomer = await Customer.findOne({ where: { username } });
    if (existingCustomer) {
      return res.status(400).json({
        success: false,
        message: 'Username already exists'
      });
    }

    // Hash password
    const hashedPassword = await hashPassword(password);

    // Create customer
    const customer = await Customer.create({
      username,
      fullname,
      password: hashedPassword,
      email,
      phone_number,
      address,
      service_type: service_type || 'Hotspot',
      account_type: account_type || 'Personal'
    });

    res.json({
      success: true,
      message: 'Customer created successfully',
      customer: {
        id: customer.id,
        username: customer.username,
        fullname: customer.fullname
      }
    });

  } catch (error) {
    console.error('Create customer error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to create customer'
    });
  }
});

// View customer details
router.get('/:id', adminOnly, async (req, res) => {
  try {
    const { id } = req.params;

    const customer = await Customer.findByPk(id, {
      include: [
        {
          model: UserRecharge,
          include: [{ model: Plan }],
          order: [['created_at', 'DESC']]
        }
      ]
    });

    if (!customer) {
      return res.status(404).render('errors/404', {
        title: 'Customer Not Found'
      });
    }

    res.render('customers/view', {
      title: `Customer - ${customer.fullname}`,
      customer
    });

  } catch (error) {
    console.error('View customer error:', error);
    res.status(500).render('errors/500', {
      title: 'Server Error',
      message: 'Failed to load customer details'
    });
  }
});

// Edit customer
router.put('/:id', [
  body('fullname').notEmpty().trim().escape(),
  body('email').optional().isEmail().normalizeEmail()
], checkPermission(['SuperAdmin', 'Admin']), async (req, res) => {
  try {
    const { id } = req.params;
    const errors = validationResult(req);
    
    if (!errors.isEmpty()) {
      return res.status(400).json({
        success: false,
        errors: errors.array()
      });
    }

    const customer = await Customer.findByPk(id);
    if (!customer) {
      return res.status(404).json({
        success: false,
        message: 'Customer not found'
      });
    }

    await customer.update(req.body);

    res.json({
      success: true,
      message: 'Customer updated successfully'
    });

  } catch (error) {
    console.error('Update customer error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to update customer'
    });
  }
});

// Delete customer
router.delete('/:id', checkPermission(['SuperAdmin', 'Admin']), async (req, res) => {
  try {
    const { id } = req.params;

    const customer = await Customer.findByPk(id);
    if (!customer) {
      return res.status(404).json({
        success: false,
        message: 'Customer not found'
      });
    }

    await customer.destroy();

    res.json({
      success: true,
      message: 'Customer deleted successfully'
    });

  } catch (error) {
    console.error('Delete customer error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to delete customer'
    });
  }
});

export default router;
