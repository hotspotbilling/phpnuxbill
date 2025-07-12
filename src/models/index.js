import Customer from './Customer.js';
import User from './User.js';
import Router from './Router.js';
import Plan from './Plan.js';
import UserRecharge from './UserRecharge.js';
import AppConfig from './AppConfig.js';
import Log from './Log.js';

// Define associations
Customer.hasMany(UserRecharge, { foreignKey: 'customer_id' });
UserRecharge.belongsTo(Customer, { foreignKey: 'customer_id' });

Plan.hasMany(UserRecharge, { foreignKey: 'plan_id' });
UserRecharge.belongsTo(Plan, { foreignKey: 'plan_id' });

User.hasMany(User, { as: 'subordinates', foreignKey: 'root' });
User.belongsTo(User, { as: 'supervisor', foreignKey: 'root' });

export {
  Customer,
  User,
  Router,
  Plan,
  UserRecharge,
  AppConfig,
  Log
};
