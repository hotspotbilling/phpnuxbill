import { DataTypes } from 'sequelize';
import sequelize from '../config/database.js';

const Router = sequelize.define('Router', {
  id: {
    type: DataTypes.INTEGER,
    primaryKey: true,
    autoIncrement: true
  },
  name: {
    type: DataTypes.STRING(50),
    allowNull: false,
    unique: true
  },
  ip_address: {
    type: DataTypes.STRING(15),
    allowNull: false,
    validate: {
      isIP: true
    }
  },
  username: {
    type: DataTypes.STRING(50),
    allowNull: false
  },
  password: {
    type: DataTypes.STRING(255),
    allowNull: false
  },
  description: {
    type: DataTypes.TEXT,
    allowNull: true
  },
  enabled: {
    type: DataTypes.BOOLEAN,
    defaultValue: true
  },
  coordinates: {
    type: DataTypes.STRING(100),
    allowNull: true
  },
  coverage: {
    type: DataTypes.INTEGER,
    defaultValue: 100
  }
}, {
  tableName: 'tbl_routers',
  indexes: [
    {
      fields: ['name']
    },
    {
      fields: ['ip_address']
    },
    {
      fields: ['enabled']
    }
  ]
});

export default Router;
