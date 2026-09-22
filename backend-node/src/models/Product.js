import { DataTypes, Model } from 'sequelize';
import sequelize from '../config/database.js';

class Product extends Model {}

Product.init(
  {
    id: {
      type: DataTypes.BIGINT.UNSIGNED,
      primaryKey: true,
      autoIncrement: true,
    },
    categoryId: {
      type: DataTypes.BIGINT.UNSIGNED,
      field: 'category_id',
      allowNull: false,
    },
    name: {
      type: DataTypes.STRING,
      allowNull: false,
    },
    description: {
      type: DataTypes.TEXT,
      allowNull: true,
    },
    price: {
      type: DataTypes.DECIMAL(15, 2),
      allowNull: false,
    },
    costPrice: {
      type: DataTypes.DECIMAL(15, 2),
      field: 'cost_price',
      defaultValue: 0.0,
    },
    image: {
      type: DataTypes.STRING,
      allowNull: true,
    },
    status: {
      type: DataTypes.BOOLEAN,
      defaultValue: true,
    },
    parentId: {
      type: DataTypes.BIGINT.UNSIGNED,
      field: 'parent_id',
      allowNull: true,
    },
    bundleQty: {
      type: DataTypes.INTEGER,
      field: 'bundle_qty',
      defaultValue: 0,
    },
    minStock: {
      type: DataTypes.INTEGER,
      field: 'min_stock',
      defaultValue: 0,
    },
  },
  {
    sequelize,
    modelName: 'Product',
    tableName: 'products',
    underscored: true,
    timestamps: true,
    paranoid: true,
    deletedAt: 'deleted_at',
  }
);

export default Product;
