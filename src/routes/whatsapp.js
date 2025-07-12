import express from 'express';
import { body, validationResult } from 'express-validator';
import { Customer, AppConfig } from '../models/index.js';
import { adminOnly } from '../middleware/auth.js';
import WhatsAppGateway from '../utils/whatsapp.js';

const router = express.Router();
const whatsappGateway = new WhatsAppGateway();

// Initialize WhatsApp gateway
whatsappGateway.initialize();

// WhatsApp settings page
router.get('/settings', adminOnly, async (req, res) => {
  try {
    const whatsappSettings = await AppConfig.findAll({
      where: {
        setting: {
          [Op.or]: [
            'whatsapp_enabled',
            'whatsapp_notifications',
            'whatsapp_welcome_message',
            'whatsapp_recharge_message',
            'whatsapp_expiry_reminder'
          ]
        }
      }
    });

    const settings = {};
    whatsappSettings.forEach(setting => {
      settings[setting.setting] = setting.value;
    });

    const status = await whatsappGateway.getStatus();

    res.render('admin/whatsapp/settings', {
      title: 'WhatsApp Settings',
      page: 'whatsapp',
      settings,
      status
    });
  } catch (error) {
    console.error('WhatsApp settings error:', error);
    res.status(500).render('errors/500', {
      title: 'Server Error',
      message: 'Failed to load WhatsApp settings'
    });
  }
});

// Update WhatsApp settings
router.post('/settings', [
  adminOnly,
  body('whatsapp_enabled').isIn(['yes', 'no']).withMessage('Invalid WhatsApp enabled value'),
  body('whatsapp_notifications').isIn(['yes', 'no']).withMessage('Invalid notifications value')
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
      'whatsapp_enabled',
      'whatsapp_notifications',
      'whatsapp_welcome_message',
      'whatsapp_recharge_message',
      'whatsapp_expiry_reminder'
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
      message: 'WhatsApp settings updated successfully'
    });
  } catch (error) {
    console.error('WhatsApp settings update error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to update WhatsApp settings'
    });
  }
});

// Get WhatsApp QR code
router.get('/qr', adminOnly, async (req, res) => {
  try {
    const qrCode = await whatsappGateway.getQRCode();
    
    if (!qrCode) {
      return res.status(404).json({
        success: false,
        message: 'QR code not available'
      });
    }

    res.json({
      success: true,
      qrCode
    });
  } catch (error) {
    console.error('WhatsApp QR error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to get QR code'
    });
  }
});

// Get WhatsApp status
router.get('/status', adminOnly, async (req, res) => {
  try {
    const status = await whatsappGateway.getStatus();
    
    res.json({
      success: true,
      status
    });
  } catch (error) {
    console.error('WhatsApp status error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to get WhatsApp status'
    });
  }
});

// Send test message
router.post('/test', [
  adminOnly,
  body('phone').isMobilePhone().withMessage('Invalid phone number'),
  body('message').trim().isLength({ min: 1 }).withMessage('Message is required')
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ 
        success: false, 
        errors: errors.array() 
      });
    }

    const { phone, message } = req.body;
    
    const success = await whatsappGateway.sendMessage(phone, message);
    
    if (success) {
      res.json({
        success: true,
        message: 'Test message sent successfully'
      });
    } else {
      res.status(500).json({
        success: false,
        message: 'Failed to send test message'
      });
    }
  } catch (error) {
    console.error('WhatsApp test message error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to send test message'
    });
  }
});

// Send bulk message
router.post('/bulk', [
  adminOnly,
  body('message').trim().isLength({ min: 1 }).withMessage('Message is required'),
  body('target').isIn(['all', 'active', 'inactive']).withMessage('Invalid target')
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ 
        success: false, 
        errors: errors.array() 
      });
    }

    const { message, target } = req.body;
    
    let whereClause = {};
    if (target === 'active') {
      whereClause.status = 'Active';
    } else if (target === 'inactive') {
      whereClause.status = 'Inactive';
    }

    const customers = await Customer.findAll({
      where: whereClause,
      attributes: ['phonenumber'],
      raw: true
    });

    const phoneNumbers = customers
      .map(customer => customer.phonenumber)
      .filter(phone => phone && phone.trim() !== '');

    if (phoneNumbers.length === 0) {
      return res.status(400).json({
        success: false,
        message: 'No customers found with phone numbers'
      });
    }

    const results = await whatsappGateway.sendBulkMessage(phoneNumbers, message);
    
    const successCount = results.filter(result => result.success).length;
    const failedCount = results.length - successCount;

    res.json({
      success: true,
      message: `Bulk message sent. Success: ${successCount}, Failed: ${failedCount}`,
      results
    });
  } catch (error) {
    console.error('WhatsApp bulk message error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to send bulk message'
    });
  }
});

// Disconnect WhatsApp
router.post('/disconnect', adminOnly, async (req, res) => {
  try {
    await whatsappGateway.disconnect();
    
    res.json({
      success: true,
      message: 'WhatsApp disconnected successfully'
    });
  } catch (error) {
    console.error('WhatsApp disconnect error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to disconnect WhatsApp'
    });
  }
});

// Export WhatsApp gateway for use in other modules
export { whatsappGateway };
export default router;
