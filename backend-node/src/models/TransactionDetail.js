import { DataTypes, Model } from 'sequelize';
import sequelize from '../config/database.js';

class TransactionDetail extends Model {}

TransactionDetail.init(
  {
    id: {
      type: DataTypes.BIGINT.UNSIGNED,
      primaryKey: true,
      autoIncrement: true,
    },
    transactionId: {
      type: DataTypes.BIGINT.UNSIGNED,
      field: 'transaction_id',
      allowNull: false,
    },
    productId: {
      type: DataTypes.BIGINT.UNSIGNED,
      field: 'product_id',
      allowNull: false,
    },
    qty: {
      type: DataTypes.INTEGER,
      allowNull: false,
    },
    price: {
      type: DataTypes.DECIMAL(15, 2),
      allowNull: false,
    },
    subtotal: {
      type: DataTypes.DECIMAL(15, 2),
      allowNull: false,
    },
  },
  {
    sequelize,
    modelName: 'TransactionDetail',
    tableName: 'transaction_details',
    underscored: true,
    timestamps: true,
  }
);

export default TransactionDetail;
