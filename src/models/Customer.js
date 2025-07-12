import { DataTypes } from 'sequelize';
import sequelize from '../config/database.js';

const Customer = sequelize.define('Customer', {
  id: {
    type: DataTypes.INTEGER,
    primaryKey: true,
    autoIncrement: true
  },
  username: {
    type: DataTypes.STRING(50),
    allowNull: false,
    unique: true
  },
  password: {
    type: DataTypes.STRING(255),
    allowNull: false
  },
  fullname: {
    type: DataTypes.STRING(100),
    allowNull: false
  },
  email: {
    type: DataTypes.STRING(100),
    allowNull: true,
    validate: {
      isEmail: true
    }
  },
  phone_number: {
    type: DataTypes.STRING(20),
    allowNull: true
  },
  address: {
    type: DataTypes.TEXT,
    allowNull: true
  },
  city: {
    type: DataTypes.STRING(50),
    allowNull: true
  },
  district: {
    type: DataTypes.STRING(50),
    allowNull: true
  },
  state: {
    type: DataTypes.STRING(50),
    allowNull: true
  },
  zip: {
    type: DataTypes.STRING(10),
    allowNull: true
  },
  coordinates: {
    type: DataTypes.STRING(100),
    allowNull: true
  },
  service_type: {
    type: DataTypes.ENUM('Hotspot', 'PPPOE', 'Both'),
    defaultValue: 'Hotspot'
  },
  account_type: {
    type: DataTypes.ENUM('Personal', 'Business'),
    defaultValue: 'Personal'
  },
  status: {
    type: DataTypes.ENUM('Active', 'Inactive', 'Suspended'),
    defaultValue: 'Active'
  },
  balance: {
    type: DataTypes.DECIMAL(10, 2),
    defaultValue: 0.00
  },
  last_login: {
    type: DataTypes.DATE,
    allowNull: true
  },
  pppoe_username: {
    type: DataTypes.STRING(50),
    allowNull: true
  },
  pppoe_password: {
    type: DataTypes.STRING(50),
    allowNull: true
  },
  pppoe_ip: {
    type: DataTypes.STRING(15),
    allowNull: true
  }
}, {
  tableName: 'tbl_customers',
  indexes: [
    {
      fields: ['username']
    },
    {
      fields: ['email']
    },
    {
      fields: ['status']
    }
  ]
});

export default Customer;
