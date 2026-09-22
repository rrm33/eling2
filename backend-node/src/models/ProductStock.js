import { DataTypes, Model } from 'sequelize';
import sequelize from '../config/database.js';

class ProductStock extends Model {}

ProductStock.init(
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
    productId: {
      type: DataTypes.BIGINT.UNSIGNED,
      field: 'product_id',
      allowNull: false,
    },
    stock: {
      type: DataTypes.INTEGER,
      defaultValue: 0,
    },
    plannedStock: {
      type: DataTypes.INTEGER,
      field: 'planned_stock',
      defaultValue: 0,
    },
    requestedStock: {
      type: DataTypes.INTEGER,
      field: 'requested_stock',
      defaultValue: 0,
    },
  },
  {
    sequelize,
    modelName: 'ProductStock',
    tableName: 'product_stocks',
    underscored: true,
    timestamps: true,
  }
);

export default ProductStock;
