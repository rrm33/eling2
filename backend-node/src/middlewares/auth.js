import crypto from 'crypto';
import { User, PersonalAccessToken } from '../models/index.js';

const auth = async (req, res, next) => {
  try {
    const authHeader = req.headers.authorization;
    if (!authHeader || !authHeader.startsWith('Bearer ')) {
      return res.status(401).json({ message: 'Unauthenticated.' });
    }

    const plainToken = authHeader.split(' ')[1];
    let tokenId = null;
    let tokenValue = plainToken;

    if (plainToken.includes('|')) {
      const parts = plainToken.split('|');
      tokenId = parts[0];
      tokenValue = parts[1];
    }

    // Sanctum hashes token with sha256 before storing
    const hashedToken = crypto.createHash('sha256').update(tokenValue).digest('hex');

    let tokenRecord;
    if (tokenId) {
      tokenRecord = await PersonalAccessToken.findOne({
        where: { id: tokenId, token: hashedToken }
      });
    } else {
      tokenRecord = await PersonalAccessToken.findOne({
        where: { token: hashedToken }
      });
    }

    if (!tokenRecord) {
      return res.status(401).json({ message: 'Unauthenticated.' });
    }

    // Check expiration if expires_at is set
    if (tokenRecord.expiresAt && new Date(tokenRecord.expiresAt) < new Date()) {
      return res.status(401).json({ message: 'Token expired.' });
    }

    // Fetch user and associate shop
    const user = await User.findOne({
      where: { id: tokenRecord.tokenableId },
      include: ['shop']
    });

    if (!user) {
      return res.status(401).json({ message: 'User not found.' });
    }

    // Attach user and current token to the request
    req.user = user;
    req.token = tokenRecord;

    // Update last_used_at timestamp
    await tokenRecord.update({ lastUsedAt: new Date() });

    next();
  } catch (error) {
    console.error('Auth Middleware Error:', error);
    return res.status(500).json({ message: 'Internal Server Error.' });
  }
};

export default auth;
