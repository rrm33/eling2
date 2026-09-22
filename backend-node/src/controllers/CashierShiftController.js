import { Op } from 'sequelize';
import { CashierShift, Transaction, Finance } from '../models/index.js';
import { paginate } from '../utils/pagination.js';

const calculateShiftData = async (shift) => {
  const shopId = shift.shopId;
  const userId = shift.userId;
  const startTime = shift.startTime;

  // Total sales
  const sales = await Transaction.sum('totalPrice', {
    where: {
      shopId,
      userId,
      status: { [Op.ne]: 'void' },
      createdAt: { [Op.gte]: startTime },
    },
  }) || 0;
  shift.setDataValue('total_sales', parseFloat(sales));

  // Total income
  const income = await Finance.sum('amount', {
    where: {
      shopId,
      userId,
      type: 'income',
      status: 'active',
      createdAt: { [Op.gte]: startTime },
    },
  }) || 0;
  shift.setDataValue('total_income', parseFloat(income));

  // Total expense
  const expense = await Finance.sum('amount', {
    where: {
      shopId,
      userId,
      type: 'expense',
      status: 'active',
      createdAt: { [Op.gte]: startTime },
    },
  }) || 0;
  shift.setDataValue('total_expense', parseFloat(expense));

  const startingCash = parseFloat(shift.startingCash || 0);
  shift.setDataValue(
    'expected_balance',
    (startingCash + parseFloat(sales) + parseFloat(income)) - parseFloat(expense)
  );
};

export const index = async (req, res) => {
  try {
    const page = parseInt(req.query.page || 1);
    const limit = 20;
    const offset = (page - 1) * limit;

    const { rows: shifts, count } = await CashierShift.findAndCountAll({
      include: ['user', 'shop'],
      limit,
      offset,
      order: [['id', 'DESC']],
    });

    for (const shift of shifts) {
      if (shift.status === 'open') {
        await calculateShiftData(shift);
      }
    }

    const fullUrl = `${req.protocol}://${req.get('host')}${req.baseUrl}${req.path}`;
    const paginatedResponse = paginate(shifts, count, page, limit, fullUrl);

    return res.status(200).json(paginatedResponse);
  } catch (error) {
    console.error('Get Shifts Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};

export const current = async (req, res) => {
  try {
    const shift = await CashierShift.findOne({
      where: { userId: req.user.id, status: 'open' },
    });

    if (shift) {
      await calculateShiftData(shift);
    }

    return res.status(200).json(shift);
  } catch (error) {
    console.error('Get Current Shift Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};

export const open = async (req, res) => {
  try {
    const { starting_cash, created_at } = req.body;
    if (starting_cash === undefined) {
      return res.status(422).json({ message: 'starting_cash wajib diisi.' });
    }

    const existing = await CashierShift.findOne({
      where: { userId: req.user.id, status: 'open' },
    });
    if (existing) {
      return res.status(422).json({ message: 'Kasir sudah terbuka' });
    }

    const shift = await CashierShift.create({
      userId: req.user.id,
      shopId: req.user.shopId,
      startTime: created_at ? new Date(created_at) : new Date(),
      startingCash: parseFloat(starting_cash),
      status: 'open',
    });

    return res.status(201).json(shift);
  } catch (error) {
    console.error('Open Cashier Shift Error:', error);
    return res.status(500).json({ message: 'Gagal membuka kasir: ' + error.message });
  }
};

export const close = async (req, res) => {
  try {
    const { actual_cash, closed_at, note } = req.body;
    if (actual_cash === undefined) {
      return res.status(422).json({ message: 'actual_cash wajib diisi.' });
    }

    const shift = await CashierShift.findOne({
      where: { userId: req.user.id, status: 'open' },
    });
    if (!shift) {
      return res.status(422).json({ message: 'Tidak ada kasir yang terbuka' });
    }

    await calculateShiftData(shift);

    const actualCashVal = parseFloat(actual_cash);
    const expectedBalanceVal = parseFloat(shift.getDataValue('expected_balance') || 0.00);
    const differenceVal = actualCashVal - expectedBalanceVal;

    await shift.update({
      endTime: closed_at ? new Date(closed_at) : new Date(),
      totalSales: shift.getDataValue('total_sales'),
      totalIncome: shift.getDataValue('total_income'),
      totalExpense: shift.getDataValue('total_expense'),
      expectedBalance: expectedBalanceVal,
      actualCash: actualCashVal,
      difference: differenceVal,
      status: 'closed',
      note: note || 'Tutup via Mobile',
    });

    return res.status(200).json({
      message: 'Kasir berhasil ditutup',
      data: shift,
    });
  } catch (error) {
    console.error('Close Cashier Shift Error:', error);
    return res.status(500).json({ message: 'Gagal menutup kasir: ' + error.message });
  }
};

export const destroy = async (req, res) => {
  try {
    if (req.user.role !== 'admin') {
      return res.status(403).json({ message: 'Hanya Admin yang boleh menghapus laporan shift.' });
    }

    const { id } = req.params;
    const shift = await CashierShift.findByPk(id);
    if (!shift) {
      return res.status(404).json({ message: 'Laporan shift tidak ditemukan.' });
    }

    await shift.destroy();

    return res.status(200).json({ message: 'Laporan shift berhasil dihapus.' });
  } catch (error) {
    console.error('Destroy Cashier Shift Error:', error);
    return res.status(500).json({ message: 'Gagal menghapus: ' + error.message });
  }
};
