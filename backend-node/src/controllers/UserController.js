import bcrypt from 'bcryptjs';
import { User, Shop } from '../models/index.js';

export const index = async (req, res) => {
  try {
    const user = req.user;
    let users;

    if (user.role === 'admin') {
      users = await User.findAll({
        include: ['shop'],
        order: [['id', 'DESC']],
      });
    } else {
      users = await User.findAll({
        where: { shopId: user.shopId },
        include: ['shop'],
        order: [['id', 'DESC']],
      });
    }

    return res.status(200).json(users);
  } catch (error) {
    console.error('Get Users Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};

export const store = async (req, res) => {
  try {
    const authUser = req.user;
    const { name, email, password, role, shop_id } = req.body;

    if (!name || !email || !password || !role) {
      return res.status(422).json({ message: 'Data wajib diisi (name, email, password, role).' });
    }

    if (role === 'cashier' && !shop_id) {
      return res.status(422).json({ message: 'Shop ID wajib diisi untuk kasir.' });
    }

    // Role check: Only admin can create admin
    if (authUser.role !== 'admin' && role === 'admin') {
      return res.status(403).json({ message: 'Anda tidak memiliki akses membuat Admin.' });
    }

    // Check unique email
    const existing = await User.findOne({ where: { email } });
    if (existing) {
      return res.status(422).json({ message: 'Email sudah terdaftar.' });
    }

    // Determine shopId
    let finalShopId = null;
    if (['admin', 'kurir'].includes(role)) {
      finalShopId = null;
    } else {
      finalShopId = shop_id || authUser.shopId;
    }

    // Hash password
    const hashedPassword = await bcrypt.hash(password, 10);

    const newUser = await User.create({
      name,
      email,
      password: hashedPassword,
      role,
      shopId: finalShopId,
    });

    return res.status(201).json({
      message: 'User berhasil dibuat.',
      data: newUser,
    });
  } catch (error) {
    console.error('Store User Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};

export const update = async (req, res) => {
  try {
    const authUser = req.user;
    const { id } = req.params;
    const { name, email, password, role, shop_id } = req.body;

    const user = await User.findByPk(id);
    if (!user) {
      return res.status(404).json({ message: 'User tidak ditemukan.' });
    }

    // Authorization check
    if (authUser.role !== 'admin' && authUser.shopId !== user.shopId) {
      return res.status(403).json({ message: 'Anda tidak memiliki akses ke user ini.' });
    }

    // Unique email check if email is modified
    if (email && email !== user.email) {
      const existing = await User.findOne({ where: { email } });
      if (existing) {
        return res.status(422).json({ message: 'Email sudah terdaftar.' });
      }
      user.email = email;
    }

    if (name) user.name = name;
    if (password) {
      user.password = await bcrypt.hash(password, 10);
    }

    // Only admin can change role/shop
    if (authUser.role === 'admin') {
      if (role) user.role = role;
      if (shop_id !== undefined) {
        user.shopId = ['admin', 'kurir'].includes(user.role) ? null : shop_id;
      }
    }

    await user.save();

    return res.status(200).json({
      message: 'User berhasil diperbarui.',
      data: user,
    });
  } catch (error) {
    console.error('Update User Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};

export const destroy = async (req, res) => {
  try {
    const authUser = req.user;
    const { id } = req.params;

    const user = await User.findByPk(id);
    if (!user) {
      return res.status(404).json({ message: 'User tidak ditemukan.' });
    }

    if (authUser.id === parseInt(id)) {
      return res.status(422).json({ message: 'Anda tidak bisa menghapus akun Anda sendiri.' });
    }

    if (authUser.role !== 'admin' && (authUser.shopId !== user.shopId || user.role === 'admin')) {
      return res.status(403).json({ message: 'Anda tidak memiliki izin menghapus user ini.' });
    }

    await user.destroy();

    return res.status(200).json({ message: 'User berhasil dihapus.' });
  } catch (error) {
    console.error('Destroy User Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};

export const updateFcmToken = async (req, res) => {
  try {
    const { fcm_token } = req.body;
    const user = req.user;

    user.fcmToken = fcm_token || null;
    await user.save();

    return res.status(200).json({ message: 'FCM Token updated' });
  } catch (error) {
    console.error('Update FCM Token Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};
