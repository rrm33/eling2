import { Op } from 'sequelize';
import { Finance, Transaction, CashierShift, sequelize } from '../models/index.js';
import { paginate } from '../utils/pagination.js';

export const summary = async (req, res) => {
  try {
    const user = req.user;
    const shopId = user.role === 'admin' ? req.query.shop_id : user.shopId;

    let startingCash = 0;
    let startTime = null;

    if (shopId) {
      const lastShift = await CashierShift.findOne({
        where: { shopId, status: 'open' },
        order: [['id', 'DESC']],
      });
      if (lastShift) {
        startingCash = parseFloat(lastShift.startingCash || 0);
        startTime = lastShift.startTime || lastShift.createdAt;
      }
    }

    const { start_date, end_date } = req.query;

    // Build Transaction filter
    const txWhere = { status: { [Op.ne]: 'void' } };
    if (shopId) txWhere.shopId = shopId;

    if (start_date) {
      txWhere.createdAt = { [Op.gte]: new Date(start_date + 'T00:00:00') };
    } else if (startTime) {
      txWhere.createdAt = { [Op.gte]: new Date(startTime) };
    }

    if (end_date) {
      txWhere.createdAt = txWhere.createdAt || {};
      txWhere.createdAt[Op.lte] = new Date(end_date + 'T23:59:59');
    }

    const totalSales = await Transaction.sum('totalPrice', { where: txWhere }) || 0;

    // Build Finance filters
    const financeWhere = { status: 'active' };
    if (shopId) financeWhere.shopId = shopId;

    if (start_date) {
      financeWhere.date = { [Op.gte]: start_date };
    } else if (startTime) {
      const dateOnly = new Date(startTime).toISOString().substring(0, 10);
      financeWhere.date = { [Op.gte]: dateOnly };
    }

    if (end_date) {
      financeWhere.date = financeWhere.date || {};
      financeWhere.date[Op.lte] = end_date;
    }

    // Income
    const incomeWhere = { ...financeWhere, type: 'income' };
    const totalIncome = await Finance.sum('amount', { where: incomeWhere }) || 0;

    // Expense
    const expenseWhere = { ...financeWhere, type: 'expense' };
    const totalExpense = await Finance.sum('amount', { where: expenseWhere }) || 0;

    const balance = (startingCash + parseFloat(totalSales) + parseFloat(totalIncome)) - parseFloat(totalExpense);

    return res.status(200).json({
      starting_cash: startingCash,
      total_sales: parseFloat(totalSales),
      total_income: parseFloat(totalIncome),
      total_expense: parseFloat(totalExpense),
      balance,
    });
  } catch (error) {
    console.error('Finance Summary Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};

export const index = async (req, res) => {
  try {
    const user = req.user;
    const { type } = req.query;
    const page = parseInt(req.query.page || 1);
    const limit = 20;
    const offset = (page - 1) * limit;

    const where = {};
    if (user.role !== 'admin') {
      where.shopId = user.shopId;
    }
    if (type) {
      where.type = type;
    }

    const { rows: finances, count } = await Finance.findAndCountAll({
      where,
      include: ['user', 'shop'],
      limit,
      offset,
      order: [['id', 'DESC']],
    });

    const fullUrl = `${req.protocol}://${req.get('host')}${req.baseUrl}${req.path}`;
    const paginatedResponse = paginate(finances, count, page, limit, fullUrl);

    return res.status(200).json(paginatedResponse);
  } catch (error) {
    console.error('Get Finance List Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};

export const store = async (req, res) => {
  try {
    const { type, amount, category, note, date, status, void_by } = req.body;

    if (!type || !amount || !category || !note) {
      return res.status(422).json({ message: 'Data wajib diisi (type, amount, category, note).' });
    }

    const todayStr = date || new Date().toISOString().substring(0, 10);

    const finance = await Finance.create({
      shopId: req.user.shopId,
      userId: req.user.id,
      type,
      amount: parseFloat(amount),
      category,
      note,
      date: todayStr,
      status: status || 'active',
      voidBy: void_by || null,
    });

    return res.status(201).json({
      message: 'Data keuangan berhasil disimpan.',
      data: finance,
    });
  } catch (error) {
    console.error('Store Finance Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};

export const update = async (req, res) => {
  try {
    const { id } = req.params;
    const { status, void_by } = req.body;

    const finance = await Finance.findByPk(id);
    if (!finance) {
      return res.status(404).json({ message: 'Data keuangan tidak ditemukan.' });
    }

    if (status) finance.status = status;
    if (void_by) finance.voidBy = void_by;

    await finance.save();

    return res.status(200).json({
      message: 'Data keuangan berhasil diperbarui.',
      data: finance,
    });
  } catch (error) {
    console.error('Update Finance Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};

export const chart = async (req, res) => {
  try {
    const user = req.user;
    const shopId = user.role === 'admin' ? req.query.shop_id : user.shopId;
    const { start_date, end_date } = req.query;

    if (!start_date || !end_date) {
      return res.status(422).json({ message: 'Tanggal start_date dan end_date wajib diisi.' });
    }

    const txWhere = {
      status: { [Op.ne]: 'void' },
      createdAt: {
        [Op.between]: [new Date(start_date + 'T00:00:00'), new Date(end_date + 'T23:59:59')],
      },
    };

    if (shopId) {
      txWhere.shopId = shopId;
    }

    // Group by Date using SQL DATE function
    const dailySales = await Transaction.findAll({
      attributes: [
        [sequelize.fn('DATE', sequelize.col('created_at')), 'dateStr'],
        [sequelize.fn('SUM', sequelize.col('total_price')), 'totalSales'],
      ],
      where: txWhere,
      group: [sequelize.fn('DATE', sequelize.col('created_at'))],
      order: [[sequelize.fn('DATE', sequelize.col('created_at')), 'ASC']],
      raw: true,
    });

    const salesMap = {};
    dailySales.forEach((s) => {
      // Sequelize raw result might return DATE format differently, format it to YYYY-MM-DD
      const dateKey = new Date(s.dateStr).toISOString().substring(0, 10);
      salesMap[dateKey] = parseFloat(s.totalSales || 0);
    });

    const start = new Date(start_date);
    const end = new Date(end_date);
    const labels = [];
    const data = [];

    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
      const dateKey = d.toISOString().substring(0, 10);
      const day = String(d.getDate()).padStart(2, '0');
      const month = monthNames[d.getMonth()];
      labels.push(`${day} ${month}`); // e.g. "01 Aug"
      data.push(salesMap[dateKey] || 0.0);
    }

    return res.status(200).json({
      status: 'success',
      chart: {
        labels,
        data,
      },
    });
  } catch (error) {
    console.error('Finance Chart Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};
