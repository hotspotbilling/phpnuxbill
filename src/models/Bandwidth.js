import { DataTypes } from 'sequelize';
import { sequelize } from '../config/database.js';

const Bandwidth = sequelize.define('Bandwidth', {
  id: {
    type: DataTypes.INTEGER,
    primaryKey: true,
    autoIncrement: true
  },
  name_bw: {
    type: DataTypes.STRING(64),
    allowNull: false,
    unique: true
  },
  rate_down: {
    type: DataTypes.INTEGER,
    allowNull: false,
    defaultValue: 0
  },
  rate_down_unit: {
    type: DataTypes.ENUM('Kbps', 'Mbps'),
    allowNull: false,
    defaultValue: 'Kbps'
  },
  rate_up: {
    type: DataTypes.INTEGER,
    allowNull: false,
    defaultValue: 0
  },
  rate_up_unit: {
    type: DataTypes.ENUM('Kbps', 'Mbps'),
    allowNull: false,
    defaultValue: 'Kbps'
  },
  burst: {
    type: DataTypes.STRING(255),
    allowNull: true
  },
  burst_threshold: {
    type: DataTypes.STRING(255),
    allowNull: true
  },
  burst_time: {
    type: DataTypes.STRING(255),
    allowNull: true
  },
  priority: {
    type: DataTypes.INTEGER,
    allowNull: false,
    defaultValue: 8
  }
}, {
  tableName: 'tbl_bandwidth',
  timestamps: false
});

export default Bandwidth;
