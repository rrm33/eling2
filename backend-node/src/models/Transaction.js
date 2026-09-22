import { DataTypes, Model } from 'sequelize';
import sequelize from '../config/database.js';

class Transaction extends Model {}

Transaction.init(
  {
    id: {
      type: DataTypes.BIGINT.UNSIGNED,
      primaryKey: true,
      autoIncrement: true,
    },
    shopId: {
      type: DataTypes.BIGINT.UNSIGNED,
      field: 'shop_id',
      allowNull: false,
    },
    userId: {
      type: DataTypes.BIGINT.UNSIGNED,
      field: 'user_id',
      allowNull: false,
    },
    invoiceNumber: {
      type: DataTypes.STRING,
      field: 'invoice_number',
      allowNull: true,
      unique: true,
    },
    subtotal: {
      type: DataTypes.DECIMAL(15, 2),
      allowNull: false,
    },
    discount: {
      type: DataTypes.DECIMAL(15, 2),
      defaultValue: 0.00,
    },
    tax: {
      type: DataTypes.DECIMAL(15, 2),
      defaultValue: 0.00,
    },
    totalPrice: {
      type: DataTypes.DECIMAL(15, 2),
      field: 'total_price',
      allowNull: false,
    },
    payAmount: {
      type: DataTypes.DECIMAL(15, 2),
      field: 'pay_amount',
      allowNull: false,
    },
    changeAmount: {
      type: DataTypes.DECIMAL(15, 2),
      field: 'change_amount',
      defaultValue: 0.00,
    },
    paymentMethod: {
      type: DataTypes.STRING,
      field: 'payment_method',
      allowNull: false,
    },
    status: {
      type: DataTypes.STRING,
      defaultValue: 'completed',
    },
    note: {
      type: DataTypes.TEXT,
      allowNull: true,
    },
    voidBy: {
      type: DataTypes.BIGINT.UNSIGNED,
      field: 'void_by',
      allowNull: true,
    },
  },
  {
    sequelize,
    modelName: 'Transaction',
    tableName: 'transactions',
    underscored: true,
    timestamps: true,
    paranoid: true,
    deletedAt: 'deleted_at',
  }
);

export default Transaction;
