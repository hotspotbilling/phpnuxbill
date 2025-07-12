import express from 'express';
import { body, param, validationResult } from 'express-validator';
import { Op } from 'sequelize';
import { Customer, Plan, Router, UserRecharge, Transaction, PaymentGateway } from '../models/index.js';
import { authenticateToken, adminOnly, apiKeyAuth } from '../middleware/auth.js';

const router = express.Router();

// Customer API endpoints
router.get('/customers', [apiKeyAuth, adminOnly], async (req, res) => {
  try {
    const { page = 1, limit = 10, search, status } = req.query;
    const offset = (page - 1) * limit;
    
    let whereClause = {};
    if (search) {
      whereClause[Op.or] = [
        { username: { [Op.like]: `%${search}%` } },
        { fullname: { [Op.like]: `%${search}%` } },
        { email: { [Op.like]: `%${search}%` } },
        { phonenumber: { [Op.like]: `%${search}%` } }
      ];
    }
    if (status) {
      whereClause.status = status;
    }

    const customers = await Customer.findAndCountAll({
      where: whereClause,
      attributes: { exclude: ['password'] },
      offset,
      limit: parseInt(limit),
      order: [['id', 'DESC']]
    });

    res.json({
      success: true,
      data: customers.rows,
      pagination: {
        page: parseInt(page),
        limit: parseInt(limit),
        total: customers.count,
        totalPages: Math.ceil(customers.count / limit)
      }
    });
  } catch (error) {
    console.error('API customers error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to fetch customers'
    });
  }
});

router.get('/customers/:id', [apiKeyAuth, adminOnly], async (req, res) => {
  try {
    const customer = await Customer.findByPk(req.params.id, {
      attributes: { exclude: ['password'] },
      include: [
        {
          model: UserRecharge,
          where: { status: 'on' },
          required: false,
          include: [{ model: Plan, attributes: ['name_plan', 'type'] }]
        }
      ]
    });

    if (!customer) {
      return res.status(404).json({
        success: false,
        message: 'Customer not found'
      });
    }

    res.json({
      success: true,
      data: customer
    });
  } catch (error) {
    console.error('API customer detail error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to fetch customer'
    });
  }
});

router.post('/customers', [
  apiKeyAuth,
  adminOnly,
  body('username').trim().isLength({ min: 3 }).withMessage('Username must be at least 3 characters'),
  body('password').isLength({ min: 6 }).withMessage('Password must be at least 6 characters'),
  body('fullname').trim().isLength({ min: 1 }).withMessage('Full name is required'),
  body('email').isEmail().withMessage('Valid email is required'),
  body('phonenumber').isMobilePhone().withMessage('Valid phone number is required')
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ 
        success: false, 
        errors: errors.array() 
      });
    }

    const existingCustomer = await Customer.findOne({
      where: {
        [Op.or]: [
          { username: req.body.username },
          { email: req.body.email }
        ]
      }
    });

    if (existingCustomer) {
      return res.status(409).json({
        success: false,
        message: 'Customer with this username or email already exists'
      });
    }

    const customer = await Customer.create(req.body);

    res.status(201).json({
      success: true,
      message: 'Customer created successfully',
      data: {
        id: customer.id,
        username: customer.username,
        fullname: customer.fullname,
        email: customer.email
      }
    });
  } catch (error) {
    console.error('API customer creation error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to create customer'
    });
  }
});

// Plans API endpoints
router.get('/plans', apiKeyAuth, async (req, res) => {
  try {
    const { type, enabled = '1' } = req.query;
    
    let whereClause = { enabled };
    if (type) {
      whereClause.type = type;
    }

    const plans = await Plan.findAll({
      where: whereClause,
      order: [['price', 'ASC']]
    });

    res.json({
      success: true,
      data: plans
    });
  } catch (error) {
    console.error('API plans error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to fetch plans'
    });
  }
});

// Routers API endpoints
router.get('/routers', [apiKeyAuth, adminOnly], async (req, res) => {
  try {
    const routers = await Router.findAll({
      where: { enabled: '1' },
      order: [['name', 'ASC']]
    });

    res.json({
      success: true,
      data: routers
    });
  } catch (error) {
    console.error('API routers error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to fetch routers'
    });
  }
});

// Recharge API endpoint
router.post('/recharge', [
  apiKeyAuth,
  adminOnly,
  body('username').trim().isLength({ min: 1 }).withMessage('Username is required'),
  body('plan_id').isInt({ min: 1 }).withMessage('Plan ID is required'),
  body('method').isIn(['balance', 'cash', 'voucher']).withMessage('Invalid payment method')
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ 
        success: false, 
        errors: errors.array() 
      });
    }

    const { username, plan_id, method, router_name = 'balance' } = req.body;

    const customer = await Customer.findOne({
      where: { username }
    });

    if (!customer) {
      return res.status(404).json({
        success: false,
        message: 'Customer not found'
      });
    }

    const plan = await Plan.findByPk(plan_id);
    if (!plan) {
      return res.status(404).json({
        success: false,
        message: 'Plan not found'
      });
    }

    // Check balance if using balance payment
    if (method === 'balance' && customer.balance < plan.price) {
      return res.status(400).json({
        success: false,
        message: 'Insufficient balance'
      });
    }

    // Calculate expiration date
    const expirationDate = new Date();
    if (plan.validity_unit === 'Hrs') {
      expirationDate.setHours(expirationDate.getHours() + plan.validity);
    } else if (plan.validity_unit === 'Days') {
      expirationDate.setDate(expirationDate.getDate() + plan.validity);
    } else if (plan.validity_unit === 'Months') {
      expirationDate.setMonth(expirationDate.getMonth() + plan.validity);
    }

    // Create user recharge record
    const userRecharge = await UserRecharge.create({
      customer_id: customer.id,
      username: customer.username,
      plan_id: plan_id,
      namebp: plan.name_plan,
      recharged_on: new Date(),
      recharged_time: new Date().toTimeString().slice(0, 8),
      expiration: expirationDate,
      time: '23:59:59',
      status: 'on',
      method: method.charAt(0).toUpperCase() + method.slice(1),
      routers: router_name,
      type: plan.type
    });

    // Create transaction record
    const transaction = await Transaction.create({
      invoice: `INV-${Date.now()}`,
      username: customer.username,
      plan_id: plan_id,
      plan_name: plan.name_plan,
      recharged_on: new Date(),
      recharged_time: new Date().toTimeString().slice(0, 8),
      expiration: expirationDate,
      time: '23:59:59',
      method: method.charAt(0).toUpperCase() + method.slice(1),
      routers: router_name,
      type: plan.type,
      price: plan.price,
      status: 'paid'
    });

    // Deduct balance if using balance payment
    if (method === 'balance') {
      await customer.update({
        balance: customer.balance - plan.price
      });
    }

    res.json({
      success: true,
      message: 'Customer recharged successfully',
      data: {
        transaction_id: transaction.id,
        invoice: transaction.invoice,
        expiration: expirationDate
      }
    });
  } catch (error) {
    console.error('API recharge error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to process recharge'
    });
  }
});

// Check customer status
router.get('/status/:username', apiKeyAuth, async (req, res) => {
  try {
    const customer = await Customer.findOne({
      where: { username: req.params.username },
      attributes: { exclude: ['password'] },
      include: [
        {
          model: UserRecharge,
          where: { status: 'on' },
          required: false,
          include: [{ model: Plan, attributes: ['name_plan', 'type'] }]
        }
      ]
    });

    if (!customer) {
      return res.status(404).json({
        success: false,
        message: 'Customer not found'
      });
    }

    res.json({
      success: true,
      data: {
        username: customer.username,
        fullname: customer.fullname,
        status: customer.status,
        balance: customer.balance,
        active_plans: customer.UserRecharges || []
      }
    });
  } catch (error) {
    console.error('API status check error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to check customer status'
    });
  }
});

// Payment webhook endpoint
router.post('/webhook/payment', [
  body('transaction_id').isLength({ min: 1 }).withMessage('Transaction ID is required'),
  body('status').isIn(['success', 'failed', 'pending']).withMessage('Invalid status'),
  body('signature').isLength({ min: 1 }).withMessage('Signature is required')
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ 
        success: false, 
        errors: errors.array() 
      });
    }

    const { transaction_id, status, signature, amount } = req.body;

    // TODO: Verify signature with payment gateway

    const payment = await PaymentGateway.findOne({
      where: { id: transaction_id }
    });

    if (!payment) {
      return res.status(404).json({
        success: false,
        message: 'Payment not found'
      });
    }

    if (status === 'success') {
      // Process successful payment
      await payment.update({
        status: '2', // Paid
        paid_date: new Date(),
        pg_response: JSON.stringify(req.body)
      });

      // Create user recharge and transaction records
      const plan = await Plan.findByPk(payment.plan_id);
      const customer = await Customer.findByPk(payment.user_id);

      if (plan && customer) {
        const expirationDate = new Date();
        if (plan.validity_unit === 'Hrs') {
          expirationDate.setHours(expirationDate.getHours() + plan.validity);
        } else if (plan.validity_unit === 'Days') {
          expirationDate.setDate(expirationDate.getDate() + plan.validity);
        } else if (plan.validity_unit === 'Months') {
          expirationDate.setMonth(expirationDate.getMonth() + plan.validity);
        }

        await UserRecharge.create({
          customer_id: customer.id,
          username: customer.username,
          plan_id: plan.id,
          namebp: plan.name_plan,
          recharged_on: new Date(),
          recharged_time: new Date().toTimeString().slice(0, 8),
          expiration: expirationDate,
          time: '23:59:59',
          status: 'on',
          method: 'Gateway',
          routers: payment.routers,
          type: plan.type
        });

        await Transaction.create({
          invoice: `INV-${payment.id}`,
          username: customer.username,
          plan_id: plan.id,
          plan_name: plan.name_plan,
          recharged_on: new Date(),
          recharged_time: new Date().toTimeString().slice(0, 8),
          expiration: expirationDate,
          time: '23:59:59',
          method: 'Gateway',
          routers: payment.routers,
          type: plan.type,
          price: payment.price,
          status: 'paid'
        });

        // Send WhatsApp notification if enabled
        // TODO: Implement WhatsApp notification
      }
    } else if (status === 'failed') {
      await payment.update({
        status: '3', // Failed
        pg_response: JSON.stringify(req.body)
      });
    }

    res.json({
      success: true,
      message: 'Webhook processed successfully'
    });
  } catch (error) {
    console.error('Payment webhook error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to process webhook'
    });
  }
});

export default router;
