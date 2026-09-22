import sequelize from '../config/database.js';
import User from './User.js';
import Shop from './Shop.js';
import Category from './Category.js';
import Product from './Product.js';
import ProductStock from './ProductStock.js';
import Transaction from './Transaction.js';
import TransactionDetail from './TransactionDetail.js';
import Attendance from './Attendance.js';
import Finance from './Finance.js';
import Message from './Message.js';
import CashierShift from './CashierShift.js';
import PersonalAccessToken from './PersonalAccessToken.js';

// User & Shop
User.belongsTo(Shop, { foreignKey: 'shop_id', as: 'shop' });
Shop.hasMany(User, { foreignKey: 'shop_id', as: 'users' });

// Product & Category
Product.belongsTo(Category, { foreignKey: 'category_id', as: 'category' });
Category.hasMany(Product, { foreignKey: 'category_id', as: 'products' });

// ProductStock & Product & Shop
Product.hasMany(ProductStock, { foreignKey: 'product_id', as: 'stocks' });
ProductStock.belongsTo(Product, { foreignKey: 'product_id', as: 'product' });

Shop.hasMany(ProductStock, { foreignKey: 'shop_id', as: 'productStocks' });
ProductStock.belongsTo(Shop, { foreignKey: 'shop_id', as: 'shop' });

// Transaction & Shop & User
Transaction.belongsTo(Shop, { foreignKey: 'shop_id', as: 'shop' });
Shop.hasMany(Transaction, { foreignKey: 'shop_id', as: 'transactions' });

Transaction.belongsTo(User, { foreignKey: 'user_id', as: 'user' });
User.hasMany(Transaction, { foreignKey: 'user_id', as: 'transactions' });

// Transaction & TransactionDetail (as items)
Transaction.hasMany(TransactionDetail, { foreignKey: 'transaction_id', as: 'items' });
TransactionDetail.belongsTo(Transaction, { foreignKey: 'transaction_id', as: 'transaction' });

TransactionDetail.belongsTo(Product, { foreignKey: 'product_id', as: 'product' });
Product.hasMany(TransactionDetail, { foreignKey: 'product_id', as: 'orderDetails' });

// Attendance & User & Shop
Attendance.belongsTo(User, { foreignKey: 'user_id', as: 'user' });
User.hasMany(Attendance, { foreignKey: 'user_id', as: 'attendances' });

Attendance.belongsTo(Shop, { foreignKey: 'shop_id', as: 'shop' });
Shop.hasMany(Attendance, { foreignKey: 'shop_id', as: 'attendances' });

// Finance & Shop & User
Finance.belongsTo(Shop, { foreignKey: 'shop_id', as: 'shop' });
Shop.hasMany(Finance, { foreignKey: 'shop_id', as: 'finances' });

Finance.belongsTo(User, { foreignKey: 'user_id', as: 'user' });
User.hasMany(Finance, { foreignKey: 'user_id', as: 'finances' });

// Message & Shop & User
Message.belongsTo(Shop, { foreignKey: 'shop_id', as: 'shop' });
Shop.hasMany(Message, { foreignKey: 'shop_id', as: 'messages' });

Message.belongsTo(User, { foreignKey: 'user_id', as: 'user' });
User.hasMany(Message, { foreignKey: 'user_id', as: 'messages' });

// CashierShift & User & Shop
CashierShift.belongsTo(User, { foreignKey: 'user_id', as: 'user' });
User.hasMany(CashierShift, { foreignKey: 'user_id', as: 'shifts' });

CashierShift.belongsTo(Shop, { foreignKey: 'shop_id', as: 'shop' });
Shop.hasMany(CashierShift, { foreignKey: 'shop_id', as: 'shifts' });

export {
  sequelize,
  User,
  Shop,
  Category,
  Product,
  ProductStock,
  Transaction,
  TransactionDetail,
  Attendance,
  Finance,
  Message,
  CashierShift,
  PersonalAccessToken
};
