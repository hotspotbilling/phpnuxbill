import { DataTypes } from 'sequelize';
import sequelize from '../config/database.js';

const AppConfig = sequelize.define('AppConfig', {
  id: {
    type: DataTypes.INTEGER,
    primaryKey: true,
    autoIncrement: true
  },
  setting: {
    type: DataTypes.STRING(100),
    allowNull: false,
    unique: true
  },
  value: {
    type: DataTypes.TEXT,
    allowNull: true
  }
}, {
  tableName: 'tbl_appconfig',
  indexes: [
    {
      fields: ['setting']
    }
  ]
});

export default AppConfig;
