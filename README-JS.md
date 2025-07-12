# PHPNuxBill-JS

A modern JavaScript/Node.js port of PHPNuxBill - A powerful Hotspot Billing Software for managing internet access and customer billing.

## 🚀 Features

- **Modern Tech Stack**: Built with Express.js, Sequelize ORM, and MySQL
- **Authentication**: JWT-based authentication with role-based access control
- **Customer Management**: Complete customer registration and management system
- **Plan Management**: Flexible internet plan configuration
- **Router Integration**: Mikrotik router management and monitoring
- **Dashboard**: Real-time analytics and system monitoring
- **Responsive UI**: Bootstrap-based responsive interface
- **Security**: Input validation, rate limiting, and security headers
- **API**: RESTful API for external integrations

## 📋 Requirements

- Node.js >= 16.0.0
- MySQL/MariaDB >= 5.7
- Git

## 🛠️ Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/your-username/phpnuxbill-js.git
   cd phpnuxbill-js
   ```

2. **Install dependencies**
   ```bash
   npm install
   ```

3. **Environment Setup**
   ```bash
   cp .env.example .env
   ```
   Edit `.env` file with your database credentials and configuration.

4. **Database Setup**
   Create a MySQL database and run the migration:
   ```bash
   npm run migrate
   ```

5. **Start the server**
   ```bash
   # Development
   npm run dev
   
   # Production
   npm start
   ```

## 🔧 Configuration

### Environment Variables

```env
NODE_ENV=development
PORT=3000

# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=phpnuxbill
DB_USER=root
DB_PASSWORD=your_password

# Security
JWT_SECRET=your-super-secret-jwt-key
SESSION_SECRET=your-session-secret

# Application Settings
APP_NAME=PHPNuxBill-JS
APP_URL=http://localhost:3000
```

## 📱 Default Credentials

After running the migration, you can login with:

### Admin Login
- **URL**: `http://localhost:3000/auth/admin/login`
- **Username**: `admin`
- **Password**: `admin123`

### Sample Customer Login
- **Username**: `customer1`
- **Password**: `password123`

## 📊 API Endpoints

### Authentication
- `POST /auth/admin/login` - Admin login
- `POST /auth/customer/login` - Customer login
- `POST /auth/logout` - Logout

### Dashboard
- `GET /dashboard` - Admin dashboard
- `GET /dashboard/api/stats` - Dashboard statistics
- `GET /dashboard/api/activities` - Recent activities

### Customers
- `GET /customers` - List customers
- `POST /customers` - Create customer
- `GET /customers/:id` - Get customer details
- `PUT /customers/:id` - Update customer
- `DELETE /customers/:id` - Delete customer

### Plans
- `GET /plans` - List plans
- `POST /plans` - Create plan
- `GET /plans/:id` - Get plan details
- `PUT /plans/:id` - Update plan
- `DELETE /plans/:id` - Delete plan

### Routers
- `GET /routers` - List routers
- `POST /routers` - Create router
- `GET /routers/:id` - Get router details
- `PUT /routers/:id` - Update router
- `DELETE /routers/:id` - Delete router

## 🗂️ Project Structure

```
src/
├── config/
│   └── database.js       # Database configuration
├── middleware/
│   ├── auth.js           # Authentication middleware
│   ├── errorHandler.js   # Error handling
│   └── maintenance.js    # Maintenance mode
├── models/
│   ├── Customer.js       # Customer model
│   ├── User.js           # Admin user model
│   ├── Plan.js           # Plan model
│   ├── Router.js         # Router model
│   ├── UserRecharge.js   # Recharge model
│   ├── AppConfig.js      # Configuration model
│   ├── Log.js            # Activity log model
│   └── index.js          # Model associations
├── routes/
│   ├── auth.js           # Authentication routes
│   ├── dashboard.js      # Dashboard routes
│   ├── customers.js      # Customer management
│   ├── plans.js          # Plan management
│   ├── routers.js        # Router management
│   ├── admin.js          # Admin management
│   ├── orders.js         # Order management
│   ├── settings.js       # System settings
│   └── api.js            # API routes
├── utils/
│   ├── auth.js           # Authentication utilities
│   └── setup.js          # Setup utilities
└── views/
    ├── layouts/
    │   └── admin.ejs     # Admin layout
    ├── auth/
    │   └── admin-login.ejs
    ├── dashboard/
    │   └── index.ejs
    └── errors/
        └── 500.ejs
```

## 🔄 Migration from PHP Version

This project maintains compatibility with the original PHPNuxBill database structure. To migrate:

1. **Database**: Use the same MySQL database
2. **Data**: All existing data will be preserved
3. **Configuration**: Update settings in the new admin panel
4. **Templates**: Customize the new EJS templates as needed

## 🧪 Testing

```bash
# Run tests
npm test

# Run with coverage
npm run test:coverage
```

## 🚀 Deployment

### Using PM2 (Recommended)

```bash
# Install PM2 globally
npm install -g pm2

# Start application
pm2 start server.js --name phpnuxbill-js

# Save PM2 configuration
pm2 save

# Setup auto-restart on boot
pm2 startup
```

### Using Docker

```bash
# Build image
docker build -t phpnuxbill-js .

# Run container
docker run -d -p 3000:3000 --name phpnuxbill-js phpnuxbill-js
```

## 🔐 Security Features

- **JWT Authentication**: Secure token-based authentication
- **Password Hashing**: bcrypt for secure password storage
- **Input Validation**: express-validator for input sanitization
- **Rate Limiting**: Protection against brute force attacks
- **Security Headers**: Helmet.js for security headers
- **CORS**: Configurable cross-origin resource sharing
- **SQL Injection Prevention**: Sequelize ORM with parameterized queries

## 📈 Performance

- **Connection Pooling**: Database connection pooling
- **Caching**: In-memory caching for frequently accessed data
- **Compression**: Gzip compression for responses
- **Static File Serving**: Efficient static file serving
- **Process Management**: PM2 for production process management

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- Original PHPNuxBill project and contributors
- Express.js and Sequelize communities
- Bootstrap team for the UI components

## 📞 Support

For support, please open an issue on GitHub or contact the maintainers.

---

**Note**: This is a complete rewrite of PHPNuxBill in JavaScript/Node.js. While maintaining database compatibility, the codebase has been modernized with current best practices.
