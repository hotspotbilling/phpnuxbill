import { DataTypes } from 'sequelize';
import { sequelize } from '../config/database.js';

const PaymentGateway = sequelize.define('PaymentGateway', {
  id: {
    type: DataTypes.INTEGER,
    primaryKey: true,
    autoIncrement: true
  },
  username: {
    type: DataTypes.STRING(255),
    allowNull: false,
    references: {
      model: 'tbl_customers',
      key: 'username'
    }
  },
  user_id: {
    type: DataTypes.INTEGER,
    allowNull: false,
    references: {
      model: 'tbl_customers',
      key: 'id'
    }
  },
  gateway: {
    type: DataTypes.STRING(255),
    allowNull: false
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
  routers_id: {
    type: DataTypes.INTEGER,
    allowNull: false,
    references: {
      model: 'tbl_routers',
      key: 'id'
    }
  },
  routers: {
    type: DataTypes.STRING(255),
    allowNull: false
  },
  price: {
    type: DataTypes.DECIMAL(15, 2),
    allowNull: false
  },
  payment_method: {
    type: DataTypes.STRING(255),
    allowNull: false
  },
  payment_channel: {
    type: DataTypes.STRING(255),
    allowNull: false
  },
  created_date: {
    type: DataTypes.DATE,
    allowNull: false,
    defaultValue: DataTypes.NOW
  },
  paid_date: {
    type: DataTypes.DATE,
    allowNull: true
  },
  expired_date: {
    type: DataTypes.DATE,
    allowNull: true
  },
  pg_url_payment: {
    type: DataTypes.TEXT,
    allowNull: true
  },
  pg_request: {
    type: DataTypes.TEXT,
    allowNull: true
  },
  pg_response: {
    type: DataTypes.TEXT,
    allowNull: true
  },
  status: {
    type: DataTypes.ENUM('0', '1', '2', '3'),
    allowNull: false,
    defaultValue: '1'
  }
}, {
  tableName: 'tbl_payment_gateway',
  timestamps: false
});

export default PaymentGateway;
