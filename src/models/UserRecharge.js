import { DataTypes } from 'sequelize';
import sequelize from '../config/database.js';

const UserRecharge = sequelize.define('UserRecharge', {
  id: {
    type: DataTypes.INTEGER,
    primaryKey: true,
    autoIncrement: true
  },
  customer_id: {
    type: DataTypes.INTEGER,
    allowNull: false,
    references: {
      model: 'tbl_customers',
      key: 'id'
    }
  },
  username: {
    type: DataTypes.STRING(50),
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
  routers: {
    type: DataTypes.STRING(50),
    allowNull: false
  },
  recharged_on: {
    type: DataTypes.DATE,
    allowNull: false,
    defaultValue: DataTypes.NOW
  },
  recharged_time: {
    type: DataTypes.STRING(20),
    allowNull: false
  },
  expiration: {
    type: DataTypes.DATE,
    allowNull: false
  },
  time_limit: {
    type: DataTypes.STRING(20),
    allowNull: true
  },
  data_limit: {
    type: DataTypes.STRING(20),
    allowNull: true
  },
  status: {
    type: DataTypes.ENUM('on', 'off'),
    defaultValue: 'on'
  },
  method: {
    type: DataTypes.STRING(50),
    allowNull: true
  },
  prepaid: {
    type: DataTypes.ENUM('yes', 'no'),
    defaultValue: 'yes'
  }
}, {
  tableName: 'tbl_user_recharges',
  indexes: [
    {
      fields: ['customer_id']
    },
    {
      fields: ['username']
    },
    {
      fields: ['plan_id']
    },
    {
      fields: ['status']
    },
    {
      fields: ['expiration']
    }
  ]
});

export default UserRecharge;
