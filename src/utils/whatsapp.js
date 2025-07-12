import { Client, LocalAuth } from 'whatsapp-web.js';
import { AppConfig } from '../models/index.js';

class WhatsAppGateway {
  constructor() {
    this.client = null;
    this.isReady = false;
  }

  async initialize() {
    try {
      // Check if WhatsApp is enabled
      const whatsappEnabled = await AppConfig.findOne({
        where: { setting: 'whatsapp_enabled' }
      });

      if (!whatsappEnabled || whatsappEnabled.value !== 'yes') {
        console.log('WhatsApp gateway is disabled');
        return;
      }

      this.client = new Client({
        authStrategy: new LocalAuth({
          clientId: 'robdius-whatsapp'
        }),
        puppeteer: {
          headless: true,
          args: ['--no-sandbox', '--disable-setuid-sandbox']
        }
      });

      this.client.on('qr', (qr) => {
        console.log('WhatsApp QR Code generated:', qr);
        // TODO: Store QR code for admin to scan
      });

      this.client.on('ready', () => {
        console.log('WhatsApp client is ready!');
        this.isReady = true;
      });

      this.client.on('authenticated', () => {
        console.log('WhatsApp client authenticated');
      });

      this.client.on('auth_failure', (msg) => {
        console.error('WhatsApp authentication failed:', msg);
      });

      this.client.on('disconnected', (reason) => {
        console.log('WhatsApp client disconnected:', reason);
        this.isReady = false;
      });

      this.client.on('message', async (message) => {
        await this.handleIncomingMessage(message);
      });

      await this.client.initialize();
    } catch (error) {
      console.error('WhatsApp initialization error:', error);
    }
  }

  async handleIncomingMessage(message) {
    try {
      const contact = await message.getContact();
      const phoneNumber = contact.number;
      const messageBody = message.body.toLowerCase().trim();

      // Handle different commands
      if (messageBody === 'info' || messageBody === 'status') {
        await this.sendCustomerInfo(phoneNumber);
      } else if (messageBody === 'balance') {
        await this.sendBalanceInfo(phoneNumber);
      } else if (messageBody === 'help') {
        await this.sendHelpMessage(phoneNumber);
      }
    } catch (error) {
      console.error('Error handling incoming message:', error);
    }
  }

  async sendCustomerInfo(phoneNumber) {
    try {
      const { Customer, UserRecharge } = await import('../models/index.js');
      
      const customer = await Customer.findOne({
        where: { phonenumber: phoneNumber }
      });

      if (!customer) {
        await this.sendMessage(phoneNumber, 'Customer not found. Please contact support.');
        return;
      }

      const activeRecharge = await UserRecharge.findOne({
        where: { 
          customer_id: customer.id,
          status: 'on'
        },
        order: [['expiration', 'DESC']]
      });

      let message = `Hello ${customer.fullname}!\n\n`;
      message += `Username: ${customer.username}\n`;
      message += `Status: ${customer.status}\n`;
      message += `Balance: $${customer.balance}\n`;

      if (activeRecharge) {
        message += `\nActive Plan: ${activeRecharge.namebp}\n`;
        message += `Expires: ${activeRecharge.expiration}\n`;
      } else {
        message += `\nNo active plan\n`;
      }

      await this.sendMessage(phoneNumber, message);
    } catch (error) {
      console.error('Error sending customer info:', error);
    }
  }

  async sendBalanceInfo(phoneNumber) {
    try {
      const { Customer } = await import('../models/index.js');
      
      const customer = await Customer.findOne({
        where: { phonenumber: phoneNumber }
      });

      if (!customer) {
        await this.sendMessage(phoneNumber, 'Customer not found. Please contact support.');
        return;
      }

      const message = `Your current balance: $${customer.balance}`;
      await this.sendMessage(phoneNumber, message);
    } catch (error) {
      console.error('Error sending balance info:', error);
    }
  }

  async sendHelpMessage(phoneNumber) {
    const helpMessage = `Available commands:\n\n`;
    helpMessage += `• *info* - Get your account information\n`;
    helpMessage += `• *balance* - Check your account balance\n`;
    helpMessage += `• *help* - Show this help message\n\n`;
    helpMessage += `For support, contact our admin.`;

    await this.sendMessage(phoneNumber, helpMessage);
  }

  async sendMessage(phoneNumber, message) {
    try {
      if (!this.isReady) {
        console.log('WhatsApp client not ready');
        return false;
      }

      const chatId = phoneNumber + '@c.us';
      await this.client.sendMessage(chatId, message);
      console.log(`Message sent to ${phoneNumber}`);
      return true;
    } catch (error) {
      console.error('Error sending message:', error);
      return false;
    }
  }

  async sendNotification(phoneNumber, type, data) {
    try {
      let message = '';

      switch (type) {
        case 'welcome':
          message = `Welcome to Robdius!\n\n`;
          message += `Your account has been created successfully.\n`;
          message += `Username: ${data.username}\n`;
          message += `Password: ${data.password}\n\n`;
          message += `Please keep your credentials safe.`;
          break;

        case 'recharge':
          message = `Account Recharged!\n\n`;
          message += `Plan: ${data.plan}\n`;
          message += `Amount: $${data.amount}\n`;
          message += `Expires: ${data.expiration}\n\n`;
          message += `Thank you for your purchase!`;
          break;

        case 'expiry_reminder':
          message = `Plan Expiry Reminder\n\n`;
          message += `Your plan "${data.plan}" will expire on ${data.expiration}.\n`;
          message += `Please recharge your account to continue using our services.`;
          break;

        case 'payment_success':
          message = `Payment Successful!\n\n`;
          message += `Invoice: ${data.invoice}\n`;
          message += `Amount: $${data.amount}\n`;
          message += `Plan: ${data.plan}\n\n`;
          message += `Your service is now active!`;
          break;

        case 'low_balance':
          message = `Low Balance Alert\n\n`;
          message += `Your account balance is running low: $${data.balance}\n`;
          message += `Please top up your account to continue using our services.`;
          break;

        default:
          message = data.message || 'Notification from Robdius';
      }

      return await this.sendMessage(phoneNumber, message);
    } catch (error) {
      console.error('Error sending notification:', error);
      return false;
    }
  }

  async sendBulkMessage(phoneNumbers, message) {
    try {
      const results = [];
      for (const phoneNumber of phoneNumbers) {
        const result = await this.sendMessage(phoneNumber, message);
        results.push({ phoneNumber, success: result });
        
        // Add delay to avoid rate limiting
        await new Promise(resolve => setTimeout(resolve, 2000));
      }
      return results;
    } catch (error) {
      console.error('Error sending bulk message:', error);
      return [];
    }
  }

  async getQRCode() {
    try {
      if (!this.client) {
        return null;
      }
      
      return new Promise((resolve) => {
        this.client.on('qr', (qr) => {
          resolve(qr);
        });
      });
    } catch (error) {
      console.error('Error getting QR code:', error);
      return null;
    }
  }

  async getStatus() {
    return {
      isReady: this.isReady,
      connected: this.client ? await this.client.getState() : 'DISCONNECTED'
    };
  }

  async disconnect() {
    try {
      if (this.client) {
        await this.client.destroy();
        this.isReady = false;
      }
    } catch (error) {
      console.error('Error disconnecting WhatsApp:', error);
    }
  }
}

export default WhatsAppGateway;
