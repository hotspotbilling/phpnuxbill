import { DataTypes } from 'sequelize';
import { sequelize } from '../config/database.js';

const Transaction = sequelize.define('Transaction', {
  id: {
    type: DataTypes.INTEGER,
    primaryKey: true,
    autoIncrement: true
  },
  invoice: {
    type: DataTypes.STRING(255),
    allowNull: false,
    unique: true
  },
  username: {
    type: DataTypes.STRING(255),
    allowNull: false,
    references: {
      model: 'tbl_customers',
      key: 'username'
    }
  },
  plan_id: {
    type: DataTypes.INTEGER,
    allowNull: false,
    references: {
      model: 'tbl_plans',
      key: 'id'
    }
  },
  plan_name: {
    type: DataTypes.STRING(255),
    allowNull: false
  },
  recharged_on: {
    type: DataTypes.DATE,
    allowNull: false
  },
  recharged_time: {
    type: DataTypes.TIME,
    allowNull: false
  },
  expiration: {
    type: DataTypes.DATE,
    allowNull: false
  },
  time: {
    type: DataTypes.TIME,
    allowNull: false
  },
  method: {
    type: DataTypes.STRING(255),
    allowNull: false
  },
  routers: {
    type: DataTypes.STRING(255),
    allowNull: false
  },
  type: {
    type: DataTypes.ENUM('Hotspot', 'PPPOE', 'Balance'),
    allowNull: false
  },
  price: {
    type: DataTypes.DECIMAL(15, 2),
    allowNull: false
  },
  admin_id: {
    type: DataTypes.INTEGER,
    allowNull: true,
    references: {
      model: 'tbl_users',
      key: 'id'
    }
  },
  discount: {
    type: DataTypes.DECIMAL(15, 2),
    allowNull: false,
    defaultValue: 0
  },
  tax: {
    type: DataTypes.DECIMAL(15, 2),
    allowNull: false,
    defaultValue: 0
  },
  status: {
    type: DataTypes.ENUM('paid', 'unpaid', 'cancelled'),
    allowNull: false,
    defaultValue: 'paid'
  }
}, {
  tableName: 'tbl_transactions',
  timestamps: false
});

export default Transaction;
