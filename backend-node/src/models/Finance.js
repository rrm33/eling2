import { DataTypes, Model } from 'sequelize';
import sequelize from '../config/database.js';

class Finance extends Model {}

Finance.init(
  {
    id: {
      type: DataTypes.BIGINT.UNSIGNED,
      primaryKey: true,
      autoIncrement: true,
    },
    shopId: {
      type: DataTypes.BIGINT.UNSIGNED,
      field: 'shop_id',
      allowNull: true,
    },
    userId: {
      type: DataTypes.BIGINT.UNSIGNED,
      field: 'user_id',
      allowNull: false,
    },
    type: {
      type: DataTypes.ENUM('income', 'expense'),
      allowNull: false,
    },
    amount: {
      type: DataTypes.DECIMAL(15, 2),
      allowNull: false,
    },
    category: {
      type: DataTypes.STRING,
      allowNull: false,
    },
    note: {
      type: DataTypes.TEXT,
      allowNull: true,
    },
    date: {
      type: DataTypes.DATEONLY,
      allowNull: false,
    },
    status: {
      type: DataTypes.STRING,
      defaultValue: 'active',
    },
    voidBy: {
      type: DataTypes.BIGINT.UNSIGNED,
      field: 'void_by',
      allowNull: true,
    },
  },
  {
    sequelize,
    modelName: 'Finance',
    tableName: 'finances',
    underscored: true,
    timestamps: true,
  }
);

export default Finance;
