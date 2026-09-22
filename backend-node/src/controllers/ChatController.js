import { Message, Shop, User, sequelize } from '../models/index.js';
import { Op } from 'sequelize';

export const getConversations = async (req, res) => {
  try {
    if (req.user.role !== 'admin') {
      return res.status(403).json({ message: 'Unauthorized' });
    }

    const shops = await Shop.findAll();
    const result = [];

    for (const shop of shops) {
      const latestMessage = await Message.findOne({
        where: { shopId: shop.id },
        include: [{ association: 'user', attributes: ['role'] }],
        order: [['id', 'DESC']],
      });

      const unreadCount = await Message.count({
        where: { shopId: shop.id, isRead: false },
        include: [{ association: 'user', where: { role: { [Op.ne]: 'admin' } } }],
      });

      result.push({
        id: shop.id,
        name: shop.name,
        latest_message: latestMessage
          ? {
              message: latestMessage.message,
              created_at: latestMessage.createdAt,
              sender: latestMessage.user.role,
            }
          : null,
        unread_count: unreadCount,
      });
    }

    // Sort by latest message date descending
    result.sort((a, b) => {
      const dateA = a.latest_message ? new Date(a.latest_message.created_at) : new Date(0);
      const dateB = b.latest_message ? new Date(b.latest_message.created_at) : new Date(0);
      return dateB - dateA;
    });

    return res.status(200).json({ data: result });
  } catch (error) {
    console.error('Get Conversations Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};

export const getMessages = async (req, res) => {
  try {
    const user = req.user;
    const { shop_id } = req.params;
    const targetShopId = user.role === 'admin' ? shop_id : user.shopId;

    if (!targetShopId) {
      return res.status(400).json({ message: 'Shop ID required' });
    }

    const messages = await Message.findAll({
      where: { shopId: targetShopId },
      include: [{ association: 'user', attributes: ['id', 'name', 'role'] }],
      order: [['id', 'ASC']],
    });

    return res.status(200).json({ data: messages });
  } catch (error) {
    console.error('Get Messages Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};

export const sendMessage = async (req, res) => {
  try {
    const { message, shop_id } = req.body;
    if (!message) {
      return res.status(422).json({ message: 'Pesan wajib diisi.' });
    }

    const user = req.user;
    const targetShopId = user.role === 'admin' ? shop_id : user.shopId;

    if (!targetShopId) {
      return res.status(400).json({ message: 'Shop ID required' });
    }

    const newMessage = await Message.create({
      shopId: targetShopId,
      userId: user.id,
      message,
      isRead: false,
    });

    return res.status(201).json({
      message: 'Message sent',
      data: newMessage,
    });
  } catch (error) {
    console.error('Send Message Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};

export const markAsRead = async (req, res) => {
  try {
    const user = req.user;
    const { shop_id } = req.params;
    const targetShopId = user.role === 'admin' ? shop_id : user.shopId;

    if (!targetShopId) {
      return res.status(400).json({ message: 'Shop ID required' });
    }

    // Mark messages as read where sender is opposite role
    const userRoleWhere = user.role === 'admin' ? { role: { [Op.ne]: 'admin' } } : { role: 'admin' };

    const messages = await Message.findAll({
      where: { shopId: targetShopId, isRead: false },
      include: [{ association: 'user', where: userRoleWhere }],
    });

    for (const msg of messages) {
      await msg.update({ isRead: true });
    }

    return res.status(200).json({ message: 'Messages marked as read' });
  } catch (error) {
    console.error('Mark Messages As Read Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};

export const getUnreadCount = async (req, res) => {
  try {
    const user = req.user;
    let count = 0;

    if (user.role === 'admin') {
      count = await Message.count({
        where: { isRead: false },
        include: [{ association: 'user', where: { role: { [Op.ne]: 'admin' } } }],
      });
    } else {
      count = await Message.count({
        where: { shopId: user.shopId, isRead: false },
        include: [{ association: 'user', where: { role: 'admin' } }],
      });
    }

    return res.status(200).json({ unread_count: count });
  } catch (error) {
    console.error('Get Unread Count Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};
