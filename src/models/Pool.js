import { DataTypes } from 'sequelize';
import { sequelize } from '../config/database.js';

const Pool = sequelize.define('Pool', {
  id: {
    type: DataTypes.INTEGER,
    primaryKey: true,
    autoIncrement: true
  },
  pool_name: {
    type: DataTypes.STRING(255),
    allowNull: false,
    unique: true
  },
  range_ip: {
    type: DataTypes.STRING(255),
    allowNull: false
  },
  local_ip: {
    type: DataTypes.STRING(255),
    allowNull: true
  },
  routers: {
    type: DataTypes.STRING(255),
    allowNull: false
  }
}, {
  tableName: 'tbl_pool',
  timestamps: false
});

export default Pool;
