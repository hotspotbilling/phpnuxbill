import express from 'express';
import { body, param, validationResult } from 'express-validator';
import { Op } from 'sequelize';
import { Plan, Bandwidth, Customer, UserRecharge, Router, User, Transaction, Voucher } from '../models/index.js';
import { adminOnly, checkPermission } from '../middleware/auth.js';

const router = express.Router();

// List all plans
router.get('/list', adminOnly, async (req, res) => {
  try {
    const { search, page = 1, limit = 10 } = req.query;
    const offset = (page - 1) * limit;
    
    const whereClause = search ? {
      [Op.or]: [
        { name_plan: { [Op.like]: `%${search}%` } },
        { typebp: { [Op.like]: `%${search}%` } }
      ]
    } : {};

    const plans = await Plan.findAndCountAll({
      where: whereClause,
      include: [
        { model: Bandwidth, attributes: ['name_bw', 'rate_down', 'rate_up'] }
      ],
      offset,
      limit: parseInt(limit),
      order: [['id', 'DESC']]
    });

    res.render('admin/plans/list', {
      title: 'Plans',
      page: 'plans',
      plans: plans.rows,
      pagination: {
        page: parseInt(page),
        limit: parseInt(limit),
        total: plans.count,
        totalPages: Math.ceil(plans.count / limit)
      }
    });
  } catch (error) {
    console.error('Plans list error:', error);
    res.status(500).render('errors/500', {
      title: 'Server Error',
      message: 'Failed to load plans'
    });
  }
});

// Add plan form
router.get('/add', adminOnly, async (req, res) => {
  try {
    const bandwidths = await Bandwidth.findAll({
      order: [['name_bw', 'ASC']]
    });
    
    const routers = await Router.findAll({
      where: { enabled: '1' },
      order: [['name', 'ASC']]
    });

    res.render('admin/plans/add', {
      title: 'Add Plan',
      page: 'plans',
      bandwidths,
      routers
    });
  } catch (error) {
    console.error('Plans add form error:', error);
    res.status(500).render('errors/500', {
      title: 'Server Error',
      message: 'Failed to load form'
    });
  }
});

// Create new plan
router.post('/add', [
  adminOnly,
  body('name_plan').trim().isLength({ min: 1 }).withMessage('Plan name is required'),
  body('typebp').isIn(['Limited', 'Unlimited']).withMessage('Invalid plan type'),
  body('price').isFloat({ min: 0 }).withMessage('Price must be a positive number'),
  body('validity').isInt({ min: 1 }).withMessage('Validity must be a positive integer'),
  body('validity_unit').isIn(['Hrs', 'Days', 'Months']).withMessage('Invalid validity unit'),
  body('shared_users').isInt({ min: 1 }).withMessage('Shared users must be at least 1'),
  body('type').isIn(['Hotspot', 'PPPOE']).withMessage('Invalid plan type'),
  body('routers').trim().isLength({ min: 1 }).withMessage('Router is required'),
  body('id_bw').isInt({ min: 1 }).withMessage('Bandwidth is required')
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ 
        success: false, 
        errors: errors.array() 
      });
    }

    const {
      name_plan,
      typebp,
      price,
      validity,
      validity_unit,
      shared_users,
      type,
      routers,
      id_bw,
      time_limit,
      time_unit,
      data_limit,
      data_unit,
      limit_type,
      pool,
      enabled = '1',
      prepaid = 'yes',
      is_radius = '0',
      plan_type = 'Personal'
    } = req.body;

    const plan = await Plan.create({
      name_plan,
      typebp,
      price,
      validity,
      validity_unit,
      shared_users,
      type,
      routers,
      id_bw,
      time_limit: time_limit || null,
      time_unit: time_unit || null,
      data_limit: data_limit || null,
      data_unit: data_unit || null,
      limit_type: limit_type || null,
      pool: pool || null,
      enabled,
      prepaid,
      is_radius,
      plan_type
    });

    res.json({
      success: true,
      message: 'Plan created successfully',
      plan: plan.id
    });
  } catch (error) {
    console.error('Plans create error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to create plan'
    });
  }
});

// Edit plan form
router.get('/edit/:id', [
  adminOnly,
  param('id').isInt({ min: 1 }).withMessage('Invalid plan ID')
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).render('errors/400', {
        title: 'Bad Request',
        errors: errors.array()
      });
    }

    const plan = await Plan.findByPk(req.params.id, {
      include: [
        { model: Bandwidth, attributes: ['name_bw', 'rate_down', 'rate_up'] }
      ]
    });

    if (!plan) {
      return res.status(404).render('errors/404', {
        title: 'Not Found',
        message: 'Plan not found'
      });
    }

    const bandwidths = await Bandwidth.findAll({
      order: [['name_bw', 'ASC']]
    });
    
    const routers = await Router.findAll({
      where: { enabled: '1' },
      order: [['name', 'ASC']]
    });

    res.render('admin/plans/edit', {
      title: 'Edit Plan',
      page: 'plans',
      plan,
      bandwidths,
      routers
    });
  } catch (error) {
    console.error('Plans edit form error:', error);
    res.status(500).render('errors/500', {
      title: 'Server Error',
      message: 'Failed to load plan'
    });
  }
});

// Update plan
router.post('/edit/:id', [
  adminOnly,
  param('id').isInt({ min: 1 }).withMessage('Invalid plan ID'),
  body('name_plan').trim().isLength({ min: 1 }).withMessage('Plan name is required'),
  body('typebp').isIn(['Limited', 'Unlimited']).withMessage('Invalid plan type'),
  body('price').isFloat({ min: 0 }).withMessage('Price must be a positive number')
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ 
        success: false, 
        errors: errors.array() 
      });
    }

    const plan = await Plan.findByPk(req.params.id);
    if (!plan) {
      return res.status(404).json({
        success: false,
        message: 'Plan not found'
      });
    }

    await plan.update(req.body);

    res.json({
      success: true,
      message: 'Plan updated successfully'
    });
  } catch (error) {
    console.error('Plans update error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to update plan'
    });
  }
});

// Delete plan
router.delete('/delete/:id', [
  adminOnly,
  param('id').isInt({ min: 1 }).withMessage('Invalid plan ID')
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ 
        success: false, 
        errors: errors.array() 
      });
    }

    const plan = await Plan.findByPk(req.params.id);
    if (!plan) {
      return res.status(404).json({
        success: false,
        message: 'Plan not found'
      });
    }

    // Check if plan is being used
    const activeRecharges = await UserRecharge.count({
      where: { plan_id: req.params.id, status: 'on' }
    });

    if (activeRecharges > 0) {
      return res.status(400).json({
        success: false,
        message: 'Cannot delete plan with active recharges'
      });
    }

    await plan.destroy();

    res.json({
      success: true,
      message: 'Plan deleted successfully'
    });
  } catch (error) {
    console.error('Plans delete error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to delete plan'
    });
  }
});

// Recharge customer
router.get('/recharge/:customerId?', adminOnly, async (req, res) => {
  try {
    const { customerId } = req.params;
    let customer = null;
    
    if (customerId) {
      customer = await Customer.findByPk(customerId);
    }

    const plans = await Plan.findAll({
      where: { enabled: '1' },
      include: [
        { model: Bandwidth, attributes: ['name_bw', 'rate_down', 'rate_up'] }
      ],
      order: [['name_plan', 'ASC']]
    });

    res.render('admin/plans/recharge', {
      title: 'Recharge Customer',
      page: 'plans',
      customer,
      plans
    });
  } catch (error) {
    console.error('Plans recharge form error:', error);
    res.status(500).render('errors/500', {
      title: 'Server Error',
      message: 'Failed to load recharge form'
    });
  }
});

// Process recharge
router.post('/recharge', [
  adminOnly,
  body('id_customer').isInt({ min: 1 }).withMessage('Customer ID is required'),
  body('plan').isInt({ min: 1 }).withMessage('Plan ID is required'),
  body('server').trim().isLength({ min: 1 }).withMessage('Server is required'),
  body('using').isIn(['balance', 'cash', 'zero']).withMessage('Invalid payment method')
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ 
        success: false, 
        errors: errors.array() 
      });
    }

    const { id_customer, plan: planId, server, using } = req.body;

    const customer = await Customer.findByPk(id_customer);
    if (!customer) {
      return res.status(404).json({
        success: false,
        message: 'Customer not found'
      });
    }

    const plan = await Plan.findByPk(planId);
    if (!plan) {
      return res.status(404).json({
        success: false,
        message: 'Plan not found'
      });
    }

    // Check balance if using balance payment
    if (using === 'balance') {
      if (customer.balance < plan.price) {
        return res.status(400).json({
          success: false,
          message: 'Insufficient balance'
        });
      }
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
      customer_id: id_customer,
      username: customer.username,
      plan_id: planId,
      namebp: plan.name_plan,
      recharged_on: new Date(),
      recharged_time: new Date().toTimeString().slice(0, 8),
      expiration: expirationDate,
      time: '23:59:59',
      status: 'on',
      method: using === 'balance' ? 'Balance' : 'Cash',
      routers: server,
      type: plan.type
    });

    // Create transaction record
    const transaction = await Transaction.create({
      invoice: `INV-${Date.now()}`,
      username: customer.username,
      plan_id: planId,
      plan_name: plan.name_plan,
      recharged_on: new Date(),
      recharged_time: new Date().toTimeString().slice(0, 8),
      expiration: expirationDate,
      time: '23:59:59',
      method: using === 'balance' ? 'Balance' : 'Cash',
      routers: server,
      type: plan.type,
      price: plan.price,
      admin_id: req.user.id
    });

    // Deduct balance if using balance payment
    if (using === 'balance') {
      await customer.update({
        balance: customer.balance - plan.price
      });
    }

    res.json({
      success: true,
      message: 'Customer recharged successfully',
      transaction: transaction.id
    });
  } catch (error) {
    console.error('Plans recharge error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to process recharge'
    });
  }
});

// Generate vouchers
router.get('/voucher', adminOnly, async (req, res) => {
  try {
    const { search, page = 1, limit = 10 } = req.query;
    const offset = (page - 1) * limit;
    
    const whereClause = search ? {
      [Op.or]: [
        { code: { [Op.like]: `%${search}%` } },
        { user: { [Op.like]: `%${search}%` } }
      ]
    } : {};

    const vouchers = await Voucher.findAndCountAll({
      where: whereClause,
      include: [
        { model: Plan, attributes: ['name_plan', 'price'] }
      ],
      offset,
      limit: parseInt(limit),
      order: [['id', 'DESC']]
    });

    res.render('admin/plans/voucher', {
      title: 'Vouchers',
      page: 'plans',
      vouchers: vouchers.rows,
      pagination: {
        page: parseInt(page),
        limit: parseInt(limit),
        total: vouchers.count,
        totalPages: Math.ceil(vouchers.count / limit)
      }
    });
  } catch (error) {
    console.error('Vouchers list error:', error);
    res.status(500).render('errors/500', {
      title: 'Server Error',
      message: 'Failed to load vouchers'
    });
  }
});

// Generate vouchers
router.post('/voucher/generate', [
  adminOnly,
  body('plan_id').isInt({ min: 1 }).withMessage('Plan ID is required'),
  body('qty').isInt({ min: 1, max: 1000 }).withMessage('Quantity must be between 1 and 1000'),
  body('prefix').optional().trim().isLength({ max: 10 }).withMessage('Prefix too long')
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ 
        success: false, 
        errors: errors.array() 
      });
    }

    const { plan_id, qty, prefix = '' } = req.body;

    const plan = await Plan.findByPk(plan_id);
    if (!plan) {
      return res.status(404).json({
        success: false,
        message: 'Plan not found'
      });
    }

    const vouchers = [];
    const batch = `BATCH-${Date.now()}`;

    for (let i = 0; i < qty; i++) {
      const code = prefix + Math.random().toString(36).substring(2, 8).toUpperCase();
      vouchers.push({
        code,
        id_plan: plan_id,
        routers: plan.routers,
        batch,
        generated_date: new Date()
      });
    }

    await Voucher.bulkCreate(vouchers);

    res.json({
      success: true,
      message: `${qty} vouchers generated successfully`,
      batch
    });
  } catch (error) {
    console.error('Voucher generation error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to generate vouchers'
    });
  }
});

export default router;
