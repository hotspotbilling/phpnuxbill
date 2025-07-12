import { DataTypes } from 'sequelize';
import sequelize from '../config/database.js';

const User = sequelize.define('User', {
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
  phone: {
    type: DataTypes.STRING(20),
    allowNull: true
  },
  user_type: {
    type: DataTypes.ENUM('SuperAdmin', 'Admin', 'Agent', 'Sales', 'Report'),
    defaultValue: 'Agent'
  },
  status: {
    type: DataTypes.ENUM('Active', 'Inactive'),
    defaultValue: 'Active'
  },
  last_login: {
    type: DataTypes.DATE,
    allowNull: true
  },
  city: {
    type: DataTypes.STRING(50),
    allowNull: true
  },
  subdistrict: {
    type: DataTypes.STRING(50),
    allowNull: true
  },
  ward: {
    type: DataTypes.STRING(50),
    allowNull: true
  },
  root: {
    type: DataTypes.INTEGER,
    allowNull: true,
    references: {
      model: 'tbl_users',
      key: 'id'
    }
  }
}, {
  tableName: 'tbl_users',
  indexes: [
    {
      fields: ['username']
    },
    {
      fields: ['user_type']
    },
    {
      fields: ['status']
    }
  ]
});

export default User;
