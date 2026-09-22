import { DataTypes, Model } from 'sequelize';
import sequelize from '../config/database.js';

class Shop extends Model {}

Shop.init(
  {
    id: {
      type: DataTypes.BIGINT.UNSIGNED,
      primaryKey: true,
      autoIncrement: true,
    },
    name: {
      type: DataTypes.STRING,
      allowNull: false,
    },
    address: {
      type: DataTypes.TEXT,
      allowNull: true,
    },
    phone: {
      type: DataTypes.STRING,
      allowNull: true,
    },
    isActive: {
      type: DataTypes.BOOLEAN,
      field: 'is_active',
      defaultValue: true,
    },
    logo: {
      type: DataTypes.STRING,
      allowNull: true,
    },
    stock: {
      type: DataTypes.INTEGER,
      defaultValue: 0,
    },
    minStock: {
      type: DataTypes.INTEGER,
      field: 'min_stock',
      defaultValue: 100,
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
    latitude: {
      type: DataTypes.STRING,
      allowNull: true,
    },
    longitude: {
      type: DataTypes.STRING,
      allowNull: true,
    },
    isMain: {
      type: DataTypes.BOOLEAN,
      field: 'is_main',
      defaultValue: false,
    },
  },
  {
    sequelize,
    modelName: 'Shop',
    tableName: 'shops',
    underscored: true,
    timestamps: true,
    paranoid: true,
    deletedAt: 'deleted_at',
  }
);

export default Shop;
