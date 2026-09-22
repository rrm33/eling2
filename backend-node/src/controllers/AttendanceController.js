import fs from 'fs';
import path from 'path';
import crypto from 'crypto';
import { Attendance, User, Shop } from '../models/index.js';
import { paginate } from '../utils/pagination.js';

const STORAGE_PATH = process.env.STORAGE_PATH || '/Users/ryanrizqimaulana/Documents/Aplikasi/dimsum/storage/app/public';

const calculateDistance = (lat1, lon1, lat2, lon2) => {
  const earthRadius = 6371000;
  const latDelta = ((lat2 - lat1) * Math.PI) / 180;
  const lonDelta = ((lon2 - lon1) * Math.PI) / 180;
  const a =
    Math.sin(latDelta / 2) * Math.sin(latDelta / 2) +
    Math.cos((lat1 * Math.PI) / 180) *
      Math.cos((lat2 * Math.PI) / 180) *
      Math.sin(lonDelta / 2) *
      Math.sin(lonDelta / 2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  return earthRadius * c;
};

export const store = async (req, res) => {
  try {
    const { type, latitude, longitude, shop_id, created_at, note, force_update } = req.body;

    if (!type || !latitude || !longitude || !shop_id) {
      return res.status(422).json({ message: 'Data wajib diisi (type, latitude, longitude, shop_id).' });
    }

    const user = req.user;
    const dateStr = created_at ? created_at.substring(0, 10) : new Date().toISOString().substring(0, 10);

    const shop = await Shop.findByPk(shop_id);
    if (!shop) {
      return res.status(404).json({ message: 'Cabang tidak ditemukan.' });
    }

    // Distance check (for online synchronization only)
    if (!created_at && shop.latitude && shop.longitude) {
      const distance = calculateDistance(
        parseFloat(latitude),
        parseFloat(longitude),
        parseFloat(shop.latitude),
        parseFloat(shop.longitude)
      );
      if (distance > 100) {
        return res.status(422).json({ message: `Di luar jangkauan (${Math.round(distance)}m).` });
      }
    }

    // Handle photo file
    let fileName = null;
    if (req.file) {
      fileName = 'attendances/' + req.file.filename;
    } else if (req.body.photo) {
      // Base64 decoding fallback
      const base64Data = req.body.photo.replace(/^data:image\/\w+;base64,/, '');
      const buffer = Buffer.from(base64Data, 'base64');
      const uniqueName = crypto.randomBytes(20).toString('hex') + '.png';
      fileName = 'attendances/' + uniqueName;
      fs.writeFileSync(path.join(STORAGE_PATH, fileName), buffer);
    } else {
      return res.status(422).json({ message: 'Foto absensi wajib dikirim.' });
    }

    // Fetch current attendance for date
    let attendance = await Attendance.findOne({
      where: { userId: user.id, date: dateStr }
    });

    const forceUpdateBool = force_update === 'true' || force_update === true;

    if (type === 'in') {
      if (attendance && attendance.inTime && !forceUpdateBool) {
        return res.status(200).json({
          status: 'exists',
          message: 'Anda sudah absen MASUK hari ini. Update data?',
        });
      }

      // Delete old photo if update
      if (attendance && attendance.inPhoto) {
        const oldPhoto = path.join(STORAGE_PATH, attendance.inPhoto);
        if (fs.existsSync(oldPhoto)) fs.unlinkSync(oldPhoto);
      }

      const inTimeStr = created_at
        ? new Date(created_at).toTimeString().substring(0, 8)
        : new Date().toTimeString().substring(0, 8);

      if (attendance) {
        await attendance.update({
          shopId: shop_id,
          inTime: inTimeStr,
          inLatitude: latitude,
          inLongitude: longitude,
          inPhoto: fileName,
          inNote: note || 'Masuk',
        });
      } else {
        attendance = await Attendance.create({
          userId: user.id,
          date: dateStr,
          shopId: shop_id,
          inTime: inTimeStr,
          inLatitude: latitude,
          inLongitude: longitude,
          inPhoto: fileName,
          inNote: note || 'Masuk',
        });
      }

      if (created_at) {
        await attendance.update({ createdAt: new Date(created_at) });
      }

      return res.status(200).json({
        message: 'Berhasil absen MASUK.',
        data: attendance,
      });
    } else {
      // Type out
      if (!attendance) {
        return res.status(422).json({ message: 'Belum absen masuk hari ini.' });
      }

      if (attendance.outTime && !forceUpdateBool) {
        return res.status(200).json({
          status: 'exists',
          message: 'Anda sudah absen PULANG hari ini. Update?',
        });
      }

      // Delete old photo
      if (attendance.outPhoto) {
        const oldPhoto = path.join(STORAGE_PATH, attendance.outPhoto);
        if (fs.existsSync(oldPhoto)) fs.unlinkSync(oldPhoto);
      }

      const outTimeStr = created_at
        ? new Date(created_at).toTimeString().substring(0, 8)
        : new Date().toTimeString().substring(0, 8);

      await attendance.update({
        outTime: outTimeStr,
        outLatitude: latitude,
        outLongitude: longitude,
        outPhoto: fileName,
        outNote: note || 'Pulang',
      });

      if (created_at) {
        await attendance.update({ updatedAt: new Date(created_at) });
      }

      return res.status(200).json({
        message: 'Berhasil absen PULANG.',
        data: attendance,
      });
    }
  } catch (error) {
    console.error('Store Attendance Error:', error);
    return res.status(500).json({ message: 'Error Server: ' + error.message });
  }
};

export const history = async (req, res) => {
  try {
    const authUser = req.user;
    const dateQuery = req.query.date || new Date().toISOString().substring(0, 10);
    const page = parseInt(req.query.page || 1);
    const limit = 20;
    const offset = (page - 1) * limit;

    const userWhere = {};
    if (authUser.role !== 'admin') {
      userWhere.shopId = authUser.shopId;
    }

    const { rows: users, count } = await User.findAndCountAll({
      where: userWhere,
      include: [
        { association: 'shop' },
        {
          association: 'attendances',
          where: { date: dateQuery },
          required: false,
        },
      ],
      limit,
      offset,
      order: [['id', 'ASC']],
    });

    const transformedData = users.map((u) => {
      const att = u.attendances && u.attendances[0] ? u.attendances[0] : null;
      return {
        id: u.id,
        name: u.name,
        role: u.role,
        shop: u.shop,
        date: dateQuery,
        in_time: att ? att.inTime : null,
        in_photo: att ? att.inPhoto : null,
        out_time: att ? att.outTime : null,
        out_photo: att ? att.outPhoto : null,
        status: att ? 'Sudah Absen' : 'Belum Absen',
      };
    });

    const fullUrl = `${req.protocol}://${req.get('host')}${req.baseUrl}${req.path}`;
    const paginatedResponse = paginate(transformedData, count, page, limit, fullUrl);

    return res.status(200).json(paginatedResponse);
  } catch (error) {
    console.error('Get Attendance History Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};

export const destroy = async (req, res) => {
  try {
    if (req.user.role !== 'admin') {
      return res.status(403).json({ message: 'Hanya Admin yang boleh menghapus data.' });
    }

    const { id } = req.params;
    const attendance = await Attendance.findByPk(id);

    if (!attendance) {
      return res.status(404).json({ message: 'Data absensi tidak ditemukan.' });
    }

    // Delete photos
    if (attendance.inPhoto) {
      const p = path.join(STORAGE_PATH, attendance.inPhoto);
      if (fs.existsSync(p)) fs.unlinkSync(p);
    }
    if (attendance.outPhoto) {
      const p = path.join(STORAGE_PATH, attendance.outPhoto);
      if (fs.existsSync(p)) fs.unlinkSync(p);
    }

    await attendance.destroy();

    return res.status(200).json({ message: 'Data absensi berhasil dihapus.' });
  } catch (error) {
    console.error('Destroy Attendance Error:', error);
    return res.status(500).json({ message: 'Gagal menghapus: ' + error.message });
  }
};
