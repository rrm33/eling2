import { DataTypes, Model } from 'sequelize';
import sequelize from '../config/database.js';

class CashierShift extends Model {}

CashierShift.init(
  {
    id: {
      type: DataTypes.BIGINT.UNSIGNED,
      primaryKey: true,
      autoIncrement: true,
    },
    userId: {
      type: DataTypes.BIGINT.UNSIGNED,
      field: 'user_id',
      allowNull: false,
    },
    shopId: {
      type: DataTypes.BIGINT.UNSIGNED,
      field: 'shop_id',
      allowNull: false,
    },
    startTime: {
      type: DataTypes.DATE,
      field: 'start_time',
      allowNull: false,
    },
    endTime: {
      type: DataTypes.DATE,
      field: 'end_time',
      allowNull: true,
    },
    startingCash: {
      type: DataTypes.DECIMAL(15, 2),
      field: 'starting_cash',
      allowNull: false,
    },
    totalSales: {
      type: DataTypes.DECIMAL(15, 2),
      field: 'total_sales',
      defaultValue: 0.00,
    },
    totalIncome: {
      type: DataTypes.DECIMAL(15, 2),
      field: 'total_income',
      defaultValue: 0.00,
    },
    totalExpense: {
      type: DataTypes.DECIMAL(15, 2),
      field: 'total_expense',
      defaultValue: 0.00,
    },
    expectedBalance: {
      type: DataTypes.DECIMAL(15, 2),
      field: 'expected_balance',
      defaultValue: 0.00,
    },
    actualCash: {
      type: DataTypes.DECIMAL(15, 2),
      field: 'actual_cash',
      allowNull: true,
    },
    difference: {
      type: DataTypes.DECIMAL(15, 2),
      defaultValue: 0.00,
    },
    status: {
      type: DataTypes.STRING,
      defaultValue: 'open',
    },
    note: {
      type: DataTypes.TEXT,
      allowNull: true,
    },
  },
  {
    sequelize,
    modelName: 'CashierShift',
    tableName: 'cashier_shifts',
    underscored: true,
    timestamps: true,
  }
);

export default CashierShift;
