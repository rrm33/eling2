import bcrypt from 'bcryptjs';
import crypto from 'crypto';
import { User, PersonalAccessToken } from '../models/index.js';

export const login = async (req, res) => {
  try {
    const { email, password } = req.body;
    if (!email || !password) {
      return res.status(422).json({ message: 'Email dan password wajib diisi.' });
    }

    const user = await User.findOne({
      where: { email },
      include: ['shop'],
    });

    if (!user) {
      return res.status(401).json({ message: 'Login gagal, email atau password salah.' });
    }

    // Compare Bcrypt password
    const isMatch = await bcrypt.compare(password, user.password);
    if (!isMatch) {
      return res.status(401).json({ message: 'Login gagal, email atau password salah.' });
    }

    // Generate token
    const tokenValue = crypto.randomBytes(20).toString('hex');
    const hashed = crypto.createHash('sha256').update(tokenValue).digest('hex');

    const tokenRecord = await PersonalAccessToken.create({
      tokenableType: 'App\\Models\\User',
      tokenableId: user.id,
      name: 'auth_token',
      token: hashed,
      abilities: ['*'],
    });

    const accessToken = `${tokenRecord.id}|${tokenValue}`;

    return res.status(200).json({
      access_token: accessToken,
      token_type: 'Bearer',
      user,
    });
  } catch (error) {
    console.error('Login Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};

export const loginGoogle = async (req, res) => {
  try {
    const { email } = req.body;
    if (!email) {
      return res.status(422).json({ message: 'Email wajib diisi.' });
    }

    const user = await User.findOne({
      where: { email },
      include: ['shop'],
    });

    if (!user) {
      return res.status(403).json({
        message: 'Email Google Anda belum didaftarkan di sistem. Hubungi Admin.',
      });
    }

    // Generate token
    const tokenValue = crypto.randomBytes(20).toString('hex');
    const hashed = crypto.createHash('sha256').update(tokenValue).digest('hex');

    const tokenRecord = await PersonalAccessToken.create({
      tokenableType: 'App\\Models\\User',
      tokenableId: user.id,
      name: 'auth_token',
      token: hashed,
      abilities: ['*'],
    });

    const accessToken = `${tokenRecord.id}|${tokenValue}`;

    return res.status(200).json({
      access_token: accessToken,
      token_type: 'Bearer',
      user,
    });
  } catch (error) {
    console.error('Google Login Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};

export const logout = async (req, res) => {
  try {
    if (req.token) {
      await req.token.destroy();
    }
    return res.status(200).json({ message: 'Berhasil logout.' });
  } catch (error) {
    console.error('Logout Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};

export const me = async (req, res) => {
  try {
    // Refresh user data with shop
    const user = await User.findOne({
      where: { id: req.user.id },
      include: ['shop'],
    });

    return res.status(200).json({
      status: 'success',
      data: user,
    });
  } catch (error) {
    console.error('Me Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};
