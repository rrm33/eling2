import { DataTypes, Model } from 'sequelize';
import sequelize from '../config/database.js';

class PersonalAccessToken extends Model {}

PersonalAccessToken.init(
  {
    id: {
      type: DataTypes.BIGINT.UNSIGNED,
      primaryKey: true,
      autoIncrement: true,
    },
    tokenableType: {
      type: DataTypes.STRING,
      field: 'tokenable_type',
      allowNull: false,
    },
    tokenableId: {
      type: DataTypes.BIGINT.UNSIGNED,
      field: 'tokenable_id',
      allowNull: false,
    },
    name: {
      type: DataTypes.STRING,
      allowNull: false,
    },
    token: {
      type: DataTypes.STRING(64),
      allowNull: false,
      unique: true,
    },
    abilities: {
      type: DataTypes.TEXT,
      allowNull: true,
      get() {
        const rawValue = this.getDataValue('abilities');
        try {
          return rawValue ? JSON.parse(rawValue) : null;
        } catch (e) {
          return rawValue;
        }
      },
      set(value) {
        this.setDataValue('abilities', value ? JSON.stringify(value) : null);
      }
    },
    lastUsedAt: {
      type: DataTypes.DATE,
      field: 'last_used_at',
      allowNull: true,
    },
    expiresAt: {
      type: DataTypes.DATE,
      field: 'expires_at',
      allowNull: true,
    },
  },
  {
    sequelize,
    modelName: 'PersonalAccessToken',
    tableName: 'personal_access_tokens',
    underscored: true,
    timestamps: true,
  }
);

export default PersonalAccessToken;
