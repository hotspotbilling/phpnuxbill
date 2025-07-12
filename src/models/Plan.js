import { DataTypes } from 'sequelize';
import sequelize from '../config/database.js';

const Plan = sequelize.define('Plan', {
  id: {
    type: DataTypes.INTEGER,
    primaryKey: true,
    autoIncrement: true
  },
  name_plan: {
    type: DataTypes.STRING(100),
    allowNull: false
  },
  bandwidth_name: {
    type: DataTypes.STRING(50),
    allowNull: false
  },
  type: {
    type: DataTypes.ENUM('Hotspot', 'PPPOE'),
    defaultValue: 'Hotspot'
  },
  type_plan: {
    type: DataTypes.ENUM('Limited', 'Unlimited'),
    defaultValue: 'Limited'
  },
  prepaid: {
    type: DataTypes.ENUM('yes', 'no'),
    defaultValue: 'yes'
  },
  price: {
    type: DataTypes.DECIMAL(10, 2),
    allowNull: false
  },
  validity: {
    type: DataTypes.STRING(10),
    allowNull: false
  },
  validity_unit: {
    type: DataTypes.ENUM('Hrs', 'Days', 'Months'),
    defaultValue: 'Days'
  },
  data_limit: {
    type: DataTypes.STRING(20),
    allowNull: true
  },
  time_limit: {
    type: DataTypes.STRING(20),
    allowNull: true
  },
  routers: {
    type: DataTypes.STRING(50),
    allowNull: false
  },
  pool_name: {
    type: DataTypes.STRING(50),
    allowNull: true
  },
  enabled: {
    type: DataTypes.BOOLEAN,
    defaultValue: true
  },
  is_radius: {
    type: DataTypes.BOOLEAN,
    defaultValue: false
  },
  plan_type: {
    type: DataTypes.ENUM('Personal', 'Business'),
    defaultValue: 'Personal'
  },
  device: {
    type: DataTypes.STRING(50),
    allowNull: true
  }
}, {
  tableName: 'tbl_plans',
  indexes: [
    {
      fields: ['name_plan']
    },
    {
      fields: ['type']
    },
    {
      fields: ['enabled']
    },
    {
      fields: ['routers']
    }
  ]
});

export default Plan;
