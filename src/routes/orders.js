import express from 'express';
import { body, param, validationResult } from 'express-validator';
import { Op } from 'sequelize';
import { Plan, Router, Customer, Transaction, PaymentGateway, UserRecharge } from '../models/index.js';
import { authenticateToken } from '../middleware/auth.js';

const router = express.Router();

// Customer order package selection
router.get('/package', authenticateToken, async (req, res) => {
  try {
    const user = await Customer.findByPk(req.user.id);
    if (!user) {
      return res.status(404).render('errors/404', {
        title: 'Not Found',
        message: 'Customer not found'
      });
    }

    const hotspotPlans = await Plan.findAll({
      where: { 
        enabled: '1',
        type: 'Hotspot',
        prepaid: 'yes'
      },
      order: [['price', 'ASC']]
    });

    const pppoePlans = await Plan.findAll({
      where: { 
        enabled: '1',
        type: 'PPPOE',
        prepaid: 'yes'
      },
      order: [['price', 'ASC']]
    });

    res.render('customer/order/package', {
      title: 'Select Package',
      page: 'order',
      user,
      hotspotPlans,
      pppoePlans
    });
  } catch (error) {
    console.error('Order package error:', error);
    res.status(500).render('errors/500', {
      title: 'Server Error',
      message: 'Failed to load packages'
    });
  }
});

// Order package confirmation
router.get('/confirm/:routerId/:planId', [
  authenticateToken,
  param('routerId').isInt({ min: 0 }).withMessage('Invalid router ID'),
  param('planId').isInt({ min: 1 }).withMessage('Invalid plan ID')
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).render('errors/400', {
        title: 'Bad Request',
        errors: errors.array()
      });
    }

    const { routerId, planId } = req.params;

    const user = await Customer.findByPk(req.user.id);
    if (!user) {
      return res.status(404).render('errors/404', {
        title: 'Not Found',
        message: 'Customer not found'
      });
    }

    const plan = await Plan.findByPk(planId);
    if (!plan || plan.enabled !== '1') {
      return res.status(404).render('errors/404', {
        title: 'Not Found',
        message: 'Plan not found or disabled'
      });
    }

    let router = null;
    if (routerId > 0) {
      router = await Router.findByPk(routerId);
      if (!router || router.enabled !== '1') {
        return res.status(404).render('errors/404', {
          title: 'Not Found',
          message: 'Router not found or disabled'
        });
      }
    }

    // Calculate total price with tax if applicable
    let totalPrice = parseFloat(plan.price);
    let tax = 0;
    
    // TODO: Implement tax calculation based on configuration

    res.render('customer/order/confirm', {
      title: 'Confirm Order',
      page: 'order',
      user,
      plan,
      router,
      totalPrice,
      tax
    });
  } catch (error) {
    console.error('Order confirm error:', error);
    res.status(500).render('errors/500', {
      title: 'Server Error',
      message: 'Failed to load order confirmation'
    });
  }
});

// Process order
router.post('/process', [
  authenticateToken,
  body('plan_id').isInt({ min: 1 }).withMessage('Plan ID is required'),
  body('router_id').isInt({ min: 0 }).withMessage('Router ID is required'),
  body('payment_method').isIn(['balance', 'gateway']).withMessage('Invalid payment method')
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ 
        success: false, 
        errors: errors.array() 
      });
    }

    const { plan_id, router_id, payment_method } = req.body;

    const user = await Customer.findByPk(req.user.id);
    if (!user) {
      return res.status(404).json({
        success: false,
        message: 'Customer not found'
      });
    }

    const plan = await Plan.findByPk(plan_id);
    if (!plan || plan.enabled !== '1') {
      return res.status(404).json({
        success: false,
        message: 'Plan not found or disabled'
      });
    }

    let router = null;
    let routerName = 'balance';
    
    if (router_id > 0) {
      router = await Router.findByPk(router_id);
      if (!router || router.enabled !== '1') {
        return res.status(404).json({
          success: false,
          message: 'Router not found or disabled'
        });
      }
      routerName = router.name;
    }

    const totalPrice = parseFloat(plan.price);

    if (payment_method === 'balance') {
      // Check if user has sufficient balance
      if (user.balance < totalPrice) {
        return res.status(400).json({
          success: false,
          message: 'Insufficient balance'
        });
      }

      // Process immediate payment with balance
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
        customer_id: user.id,
        username: user.username,
        plan_id: plan_id,
        namebp: plan.name_plan,
        recharged_on: new Date(),
        recharged_time: new Date().toTimeString().slice(0, 8),
        expiration: expirationDate,
        time: '23:59:59',
        status: 'on',
        method: 'Balance',
        routers: routerName,
        type: plan.type
      });

      // Create transaction record
      const transaction = await Transaction.create({
        invoice: `INV-${Date.now()}`,
        username: user.username,
        plan_id: plan_id,
        plan_name: plan.name_plan,
        recharged_on: new Date(),
        recharged_time: new Date().toTimeString().slice(0, 8),
        expiration: expirationDate,
        time: '23:59:59',
        method: 'Balance',
        routers: routerName,
        type: plan.type,
        price: totalPrice,
        status: 'paid'
      });

      // Deduct balance
      await user.update({
        balance: user.balance - totalPrice
      });

      res.json({
        success: true,
        message: 'Order processed successfully',
        transaction: transaction.id,
        invoice: transaction.invoice
      });

    } else if (payment_method === 'gateway') {
      // Create payment gateway record for external payment
      const paymentGateway = await PaymentGateway.create({
        username: user.username,
        user_id: user.id,
        gateway: 'pending',
        plan_id: plan_id,
        plan_name: plan.name_plan,
        routers_id: router_id,
        routers: routerName,
        price: totalPrice,
        payment_method: 'Gateway',
        payment_channel: 'Online',
        created_date: new Date(),
        expired_date: new Date(Date.now() + 24 * 60 * 60 * 1000), // 24 hours
        status: '1' // Pending
      });

      res.json({
        success: true,
        message: 'Payment gateway created',
        payment_id: paymentGateway.id,
        redirect_url: `/order/payment/${paymentGateway.id}`
      });
    }
  } catch (error) {
    console.error('Order process error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to process order'
    });
  }
});

// Payment gateway page
router.get('/payment/:paymentId', [
  authenticateToken,
  param('paymentId').isInt({ min: 1 }).withMessage('Invalid payment ID')
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).render('errors/400', {
        title: 'Bad Request',
        errors: errors.array()
      });
    }

    const payment = await PaymentGateway.findOne({
      where: { 
        id: req.params.paymentId,
        user_id: req.user.id
      },
      include: [
        { model: Plan, attributes: ['name_plan', 'type'] }
      ]
    });

    if (!payment) {
      return res.status(404).render('errors/404', {
        title: 'Not Found',
        message: 'Payment not found'
      });
    }

    res.render('customer/order/payment', {
      title: 'Payment',
      page: 'order',
      payment
    });
  } catch (error) {
    console.error('Payment page error:', error);
    res.status(500).render('errors/500', {
      title: 'Server Error',
      message: 'Failed to load payment page'
    });
  }
});

// Order history
router.get('/history', authenticateToken, async (req, res) => {
  try {
    const { page = 1, limit = 10 } = req.query;
    const offset = (page - 1) * limit;

    const orders = await PaymentGateway.findAndCountAll({
      where: { user_id: req.user.id },
      include: [
        { model: Plan, attributes: ['name_plan', 'type'] }
      ],
      offset,
      limit: parseInt(limit),
      order: [['created_date', 'DESC']]
    });

    res.render('customer/order/history', {
      title: 'Order History',
      page: 'order',
      orders: orders.rows,
      pagination: {
        page: parseInt(page),
        limit: parseInt(limit),
        total: orders.count,
        totalPages: Math.ceil(orders.count / limit)
      }
    });
  } catch (error) {
    console.error('Order history error:', error);
    res.status(500).render('errors/500', {
      title: 'Server Error',
      message: 'Failed to load order history'
    });
  }
});

export default router;
