import { DataTypes } from 'sequelize';
import sequelize from '../config/database.js';

const Log = sequelize.define('Log', {
  id: {
    type: DataTypes.INTEGER,
    primaryKey: true,
    autoIncrement: true
  },
  date: {
    type: DataTypes.DATE,
    allowNull: false,
    defaultValue: DataTypes.NOW
  },
  type: {
    type: DataTypes.STRING(50),
    allowNull: true
  },
  description: {
    type: DataTypes.TEXT,
    allowNull: false
  },
  userid: {
    type: DataTypes.INTEGER,
    allowNull: false,
    defaultValue: 0
  },
  ip: {
    type: DataTypes.STRING(45),
    allowNull: true
  }
}, {
  tableName: 'tbl_logs',
  indexes: [
    {
      fields: ['date']
    },
    {
      fields: ['type']
    },
    {
      fields: ['userid']
    }
  ]
});

export default Log;
