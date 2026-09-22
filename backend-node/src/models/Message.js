import { DataTypes, Model } from 'sequelize';
import sequelize from '../config/database.js';

class Message extends Model {}

Message.init(
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
    message: {
      type: DataTypes.TEXT,
      allowNull: false,
    },
    isRead: {
      type: DataTypes.BOOLEAN,
      field: 'is_read',
      defaultValue: false,
    },
  },
  {
    sequelize,
    modelName: 'Message',
    tableName: 'messages',
    underscored: true,
    timestamps: true,
  }
);

export default Message;
