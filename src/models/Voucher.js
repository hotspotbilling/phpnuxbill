import { DataTypes } from 'sequelize';
import { sequelize } from '../config/database.js';

const Voucher = sequelize.define('Voucher', {
  id: {
    type: DataTypes.INTEGER,
    primaryKey: true,
    autoIncrement: true
  },
  code: {
    type: DataTypes.STRING(255),
    allowNull: false,
    unique: true
  },
  id_plan: {
    type: DataTypes.INTEGER,
    allowNull: false,
    references: {
      model: 'tbl_plans',
      key: 'id'
    }
  },
  user: {
    type: DataTypes.STRING(255),
    allowNull: true
  },
  routers: {
    type: DataTypes.STRING(255),
    allowNull: false
  },
  status: {
    type: DataTypes.ENUM('0', '1'),
    allowNull: false,
    defaultValue: '0'
  },
  generated_date: {
    type: DataTypes.DATE,
    allowNull: false,
    defaultValue: DataTypes.NOW
  },
  used_date: {
    type: DataTypes.DATE,
    allowNull: true
  },
  expired_date: {
    type: DataTypes.DATE,
    allowNull: true
  },
  batch: {
    type: DataTypes.STRING(255),
    allowNull: true
  }
}, {
  tableName: 'tbl_voucher',
  timestamps: false
});

export default Voucher;
