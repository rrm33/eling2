import { DataTypes, Model } from 'sequelize';
import sequelize from '../config/database.js';

class Attendance extends Model {}

Attendance.init(
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
    date: {
      type: DataTypes.DATEONLY,
      allowNull: false,
    },
    inTime: {
      type: DataTypes.TIME,
      field: 'in_time',
      allowNull: true,
    },
    inLatitude: {
      type: DataTypes.STRING,
      field: 'in_latitude',
      allowNull: true,
    },
    inLongitude: {
      type: DataTypes.STRING,
      field: 'in_longitude',
      allowNull: true,
    },
    inPhoto: {
      type: DataTypes.STRING,
      field: 'in_photo',
      allowNull: true,
    },
    inNote: {
      type: DataTypes.STRING,
      field: 'in_note',
      allowNull: true,
    },
    outTime: {
      type: DataTypes.TIME,
      field: 'out_time',
      allowNull: true,
    },
    outLatitude: {
      type: DataTypes.STRING,
      field: 'out_latitude',
      allowNull: true,
    },
    outLongitude: {
      type: DataTypes.STRING,
      field: 'out_longitude',
      allowNull: true,
    },
    outPhoto: {
      type: DataTypes.STRING,
      field: 'out_photo',
      allowNull: true,
    },
    outNote: {
      type: DataTypes.STRING,
      field: 'out_note',
      allowNull: true,
    },
  },
  {
    sequelize,
    modelName: 'Attendance',
    tableName: 'attendances',
    underscored: true,
    timestamps: true,
  }
);

export default Attendance;
