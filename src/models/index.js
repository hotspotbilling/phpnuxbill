import Customer from './Customer.js';
import User from './User.js';
import Router from './Router.js';
import Plan from './Plan.js';
import UserRecharge from './UserRecharge.js';
import AppConfig from './AppConfig.js';
import Log from './Log.js';
import Bandwidth from './Bandwidth.js';
import Pool from './Pool.js';
import Voucher from './Voucher.js';
import Transaction from './Transaction.js';
import PaymentGateway from './PaymentGateway.js';

// Define associations
Customer.hasMany(UserRecharge, { foreignKey: 'customer_id' });
UserRecharge.belongsTo(Customer, { foreignKey: 'customer_id' });

Plan.hasMany(UserRecharge, { foreignKey: 'plan_id' });
UserRecharge.belongsTo(Plan, { foreignKey: 'plan_id' });

User.hasMany(User, { as: 'subordinates', foreignKey: 'root' });
User.belongsTo(User, { as: 'supervisor', foreignKey: 'root' });

// Plan associations
Plan.belongsTo(Bandwidth, { foreignKey: 'id_bw' });
Bandwidth.hasMany(Plan, { foreignKey: 'id_bw' });

// Voucher associations
Voucher.belongsTo(Plan, { foreignKey: 'id_plan' });
Plan.hasMany(Voucher, { foreignKey: 'id_plan' });

// Transaction associations
Transaction.belongsTo(Customer, { foreignKey: 'username', targetKey: 'username' });
Customer.hasMany(Transaction, { foreignKey: 'username', sourceKey: 'username' });

Transaction.belongsTo(Plan, { foreignKey: 'plan_id' });
Plan.hasMany(Transaction, { foreignKey: 'plan_id' });

Transaction.belongsTo(User, { foreignKey: 'admin_id' });
User.hasMany(Transaction, { foreignKey: 'admin_id' });

// Payment Gateway associations
PaymentGateway.belongsTo(Customer, { foreignKey: 'user_id' });
Customer.hasMany(PaymentGateway, { foreignKey: 'user_id' });

PaymentGateway.belongsTo(Plan, { foreignKey: 'plan_id' });
Plan.hasMany(PaymentGateway, { foreignKey: 'plan_id' });

PaymentGateway.belongsTo(Router, { foreignKey: 'routers_id' });
Router.hasMany(PaymentGateway, { foreignKey: 'routers_id' });

export {
  Customer,
  User,
  Router,
  Plan,
  UserRecharge,
  AppConfig,
  Log,
  Bandwidth,
  Pool,
  Voucher,
  Transaction,
  PaymentGateway
};
