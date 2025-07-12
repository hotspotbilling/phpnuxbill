# Robdius - Modern Hotspot Billing System

Robdius is a modern, full-featured hotspot billing system built with Node.js, Express, and PostgreSQL. It's the complete JavaScript conversion of PHPNuxBill with enhanced features and modern architecture.

## 🚀 Features

### Core Features
- **User Management**: Admin and customer roles with comprehensive permissions
- **Hotspot Management**: Router configuration and monitoring
- **Bandwidth Management**: Flexible bandwidth pools and limits
- **Plan Management**: Customizable internet plans with various pricing models
- **Voucher System**: Generate and manage vouchers for customers
- **Order Processing**: Complete order management with multiple payment methods
- **Real-time Monitoring**: Live bandwidth usage and connection monitoring
- **Reporting**: Comprehensive analytics and reporting dashboard

### Modern Enhancements
- **WhatsApp Integration**: Automated notifications and customer support
- **Payment Gateway**: Multiple payment methods support
- **API-First Architecture**: RESTful API for third-party integrations
- **Redis Caching**: Enhanced performance with Redis
- **PostgreSQL Database**: Robust database with full ACID compliance
- **Real-time Updates**: WebSocket support for live updates
- **Mobile Responsive**: Bootstrap 5 responsive design
- **Security**: JWT authentication, rate limiting, input validation

## 🛠 Technology Stack

- **Backend**: Node.js 18+, Express.js 4.18+
- **Database**: PostgreSQL 14+
- **Cache**: Redis 7+
- **Authentication**: JWT + Session-based
- **Frontend**: Bootstrap 5, EJS templating
- **Deployment**: Vercel-ready configuration
- **Monitoring**: Built-in analytics and logging

## 📦 Installation

### Prerequisites
- Node.js 18+ 
- PostgreSQL 14+
- Redis 7+
- Git

### Local Development Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/robdius.git
   cd robdius
   ```

2. **Install dependencies**
   ```bash
   npm install
   ```

3. **Environment Configuration**
   ```bash
   cp .env.example .env
   # Edit .env with your configuration
   ```

4. **Database Setup**
   ```bash
   # Create PostgreSQL database
   createdb robdius
   
   # Run migrations
   npm run db:migrate
   
   # Seed initial data
   npm run db:seed
   ```

5. **Start Redis**
   ```bash
   redis-server
   ```

6. **Start the application**
   ```bash
   # Development
   npm run dev
   
   # Production
   npm start
   ```

## 🚀 Deployment

### Vercel Deployment

1. **Install Vercel CLI**
   ```bash
   npm i -g vercel
   ```

2. **Deploy to Vercel**
   ```bash
   vercel --prod
   ```

3. **Environment Variables**
   Configure the following in Vercel dashboard:
   - `DATABASE_URL` - PostgreSQL connection string
   - `REDIS_URL` - Redis connection string
   - `JWT_SECRET` - JWT secret key
   - `SESSION_SECRET` - Session secret key
   - `WHATSAPP_ENABLED` - Enable WhatsApp integration

## 🔧 Configuration

### Database Configuration
```javascript
// config/database.js
export const config = {
  development: {
    dialect: 'postgres',
    host: process.env.DB_HOST,
    port: process.env.DB_PORT,
    database: process.env.DB_NAME,
    username: process.env.DB_USER,
    password: process.env.DB_PASSWORD
  }
};
```

### Redis Configuration
```javascript
// config/redis.js
export const redisConfig = {
  host: process.env.REDIS_HOST,
  port: process.env.REDIS_PORT,
  password: process.env.REDIS_PASSWORD,
  db: process.env.REDIS_DB
};
```

## 📱 WhatsApp Integration

Robdius includes a comprehensive WhatsApp gateway for:
- Customer notifications
- Automated support
- Voucher delivery
- Payment confirmations
- System alerts

### Setup WhatsApp Gateway
1. Enable WhatsApp in environment variables
2. Configure webhook URL
3. Scan QR code for authentication
4. Configure message templates

## 🎯 API Documentation

### Authentication
```bash
POST /api/auth/login
POST /api/auth/logout
POST /api/auth/register
```

### Customers
```bash
GET /api/customers
POST /api/customers
PUT /api/customers/:id
DELETE /api/customers/:id
```

### Plans
```bash
GET /api/plans
POST /api/plans
PUT /api/plans/:id
DELETE /api/plans/:id
```

### Orders
```bash
GET /api/orders
POST /api/orders
PUT /api/orders/:id
GET /api/orders/:id/status
```

### Reports
```bash
GET /api/reports/dashboard
GET /api/reports/bandwidth
GET /api/reports/revenue
GET /api/reports/customers
```

## 🔐 Security Features

- JWT-based authentication
- Rate limiting on all endpoints
- Input validation and sanitization
- CORS protection
- Helmet.js security headers
- Session management with Redis
- Password hashing with bcrypt
- SQL injection prevention

## 📊 Monitoring & Analytics

- Real-time bandwidth monitoring
- Customer usage analytics
- Revenue reporting
- System health monitoring
- Error tracking and logging
- Performance metrics

## 🛡 Features Migration Status

All features from the original PHPNuxBill have been successfully migrated:

✅ **Completed Features:**
- User authentication and authorization
- Customer management
- Router management
- Bandwidth management
- Plan management
- Voucher system
- Order processing
- Payment integration
- Reporting system
- WhatsApp integration
- API endpoints
- Admin dashboard
- Customer portal

## 📝 Scripts

```bash
# Development
npm run dev          # Start development server
npm run test         # Run tests
npm run lint         # Run ESLint

# Production
npm run build        # Build for production
npm start           # Start production server

# Database
npm run db:migrate   # Run database migrations
npm run db:seed      # Seed database
npm run db:reset     # Reset database

# Deployment
npm run deploy       # Deploy to Vercel
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support and questions:
- Create an issue on GitHub
- Email: support@robdius.com
- Documentation: https://docs.robdius.com

## 🔄 Migration from PHPNuxBill

If you're migrating from PHPNuxBill:

1. **Database Migration**: Use the provided migration scripts
2. **Configuration**: Update environment variables
3. **Files**: Migrate uploads and templates
4. **Testing**: Verify all features work correctly

## 🌟 Acknowledgments

- Original PHPNuxBill project
- Node.js and Express.js communities
- All contributors and supporters

---

**Robdius** - Modern Hotspot Billing System
Made with ❤️ by the development team
