import { Op, QueryTypes } from 'sequelize';
import { Transaction, TransactionDetail, Product, Shop, User, sequelize, ProductStock } from '../models/index.js';

const adjustStockForTransaction = async (transactionId, adjustmentType, dbTx) => {
  const transaction = await Transaction.findByPk(transactionId, {
    include: [{ association: 'items', include: ['product'] }, { association: 'shop' }],
    transaction: dbTx,
  });

  if (!transaction) return;

  const shop = transaction.shop;
  if (!shop) return;

  for (const item of transaction.items) {
    const product = item.product;
    if (!product) continue;

    const isDimsum = product.bundleQty > 0;
    const bundleQty = product.bundleQty || 1;
    const totalQty = item.qty * bundleQty;

    if (isDimsum) {
      if (adjustmentType === 'increment') {
        await shop.increment('stock', { by: totalQty, transaction: dbTx });
      } else {
        await shop.decrement('stock', { by: totalQty, transaction: dbTx });
      }
    } else {
      const [psRecord, psCreated] = await ProductStock.findOrCreate({
        where: { shopId: shop.id, productId: product.id },
        defaults: { stock: adjustmentType === 'increment' ? item.qty : -item.qty, plannedStock: 0, requestedStock: 0 },
        transaction: dbTx,
      });
      if (!psCreated) {
        if (adjustmentType === 'increment') {
          await psRecord.increment('stock', { by: item.qty, transaction: dbTx });
        } else {
          await psRecord.decrement('stock', { by: item.qty, transaction: dbTx });
        }
      }
    }
  }
};

export const index = async (req, res) => {
  try {
    const user = req.user;
    const { status, shop_id, start_date, end_date } = req.query;

    const where = {};
    if (status) {
      where.status = status;
    }

    // Branch filter
    if (user.role === 'admin') {
      if (shop_id) {
        where.shopId = shop_id;
      }
    } else {
      where.shopId = user.shopId;
    }

    // Date filters
    if (start_date || end_date) {
      where.createdAt = {};
      if (start_date) {
        where.createdAt[Op.gte] = new Date(start_date + 'T00:00:00');
      }
      if (end_date) {
        where.createdAt[Op.lte] = new Date(end_date + 'T23:59:59');
      }
    }

    // Fetch matching transaction IDs for aggregation
    const matchingTx = await Transaction.findAll({
      attributes: ['id'],
      where,
      raw: true,
    });
    const txIds = matchingTx.map((t) => t.id);

    let summary = {
      total_sales: 0.0,
      total_sales_cash: 0.0,
      total_sales_qris: 0.0,
      total_sales_lainnya: 0.0,
      total_transactions: 0,
      total_grains: 0,
      total_saus: 0,
      total_products: 0,
      product_details: [],
    };

    let shopStatsTransformed = [];

    if (txIds.length > 0) {
      // 1. Total Sales
      const salesQuery = {
        id: { [Op.in]: txIds },
        status: { [Op.ne]: 'void' },
      };
      const totalSales = await Transaction.sum('totalPrice', { where: salesQuery }) || 0;
      summary.total_sales = parseFloat(totalSales);

      // 2. Sales Cash
      const cashQuery = {
        ...salesQuery,
        paymentMethod: { [Op.in]: ['Tunai', 'cash', 'tunai'] },
      };
      const totalSalesCash = await Transaction.sum('totalPrice', { where: cashQuery }) || 0;
      summary.total_sales_cash = parseFloat(totalSalesCash);

      // 3. Sales QRIS
      const qrisQuery = {
        ...salesQuery,
        paymentMethod: { [Op.in]: ['QRIS', 'qris'] },
      };
      const totalSalesQris = await Transaction.sum('totalPrice', { where: qrisQuery }) || 0;
      summary.total_sales_qris = parseFloat(totalSalesQris);

      // 4. Sales Lainnya
      const lainnyaQuery = {
        ...salesQuery,
        paymentMethod: { [Op.notIn]: ['Tunai', 'cash', 'tunai', 'QRIS', 'qris'] },
      };
      const totalSalesLainnya = await Transaction.sum('totalPrice', { where: lainnyaQuery }) || 0;
      summary.total_sales_lainnya = parseFloat(totalSalesLainnya);

      // 5. Total Transactions (Count)
      summary.total_transactions = await Transaction.count({ where: salesQuery });

      // 6. Total Grains (Raw material bundle quantity)
      const grainsResult = await sequelize.query(
        `SELECT SUM(td.qty * IFNULL(p.bundle_qty, 1)) as total_grains
         FROM transactions t
         JOIN transaction_details td ON t.id = td.transaction_id
         JOIN products p ON td.product_id = p.id
         WHERE t.status != 'void' AND t.id IN (:txIds)`,
        {
          replacements: { txIds },
          type: QueryTypes.SELECT,
        }
      );
      summary.total_grains = parseInt(grainsResult[0].total_grains || 0);

      // 7. Total Saus Bangkok
      const sausResult = await sequelize.query(
        `SELECT SUM(td.qty) as total_saus
         FROM transactions t
         JOIN transaction_details td ON t.id = td.transaction_id
         JOIN products p ON td.product_id = p.id
         WHERE t.status != 'void' AND t.id IN (:txIds)
         AND (p.name LIKE '%saus bangkok%' OR p.name LIKE '%saos bangkok%' OR p.name LIKE '%sauce bangkok%' OR p.name LIKE '%bangkok%')`,
        {
          replacements: { txIds },
          type: QueryTypes.SELECT,
        }
      );
      summary.total_saus = parseInt(sausResult[0].total_saus || 0);

      // 8. Total Products Sold
      const prodQtyResult = await sequelize.query(
        `SELECT SUM(td.qty) as total_products
         FROM transactions t
         JOIN transaction_details td ON t.id = td.transaction_id
         WHERE t.status != 'void' AND t.id IN (:txIds)`,
        {
          replacements: { txIds },
          type: QueryTypes.SELECT,
        }
      );
      summary.total_products = parseInt(prodQtyResult[0].total_products || 0);

      // 9. Product Details
      const prodDetailsResult = await sequelize.query(
        `SELECT p.name, p.image, SUM(td.qty) as total_qty, SUM(td.subtotal) as total_omset
         FROM transactions t
         JOIN transaction_details td ON t.id = td.transaction_id
         JOIN products p ON td.product_id = p.id
         WHERE t.status != 'void' AND t.id IN (:txIds)
         GROUP BY p.id, p.name, p.image
         ORDER BY total_qty DESC`,
        {
          replacements: { txIds },
          type: QueryTypes.SELECT,
        }
      );
      summary.product_details = prodDetailsResult.map((p) => ({
        name: p.name,
        image: p.image,
        total_qty: parseInt(p.total_qty || 0),
        total_omset: parseFloat(p.total_omset || 0.0),
      }));

      // 10. Shop Stats
      const shopItemsResult = await sequelize.query(
        `SELECT s.id as shop_id, s.name as shop_name,
           SUM(CASE WHEN p.bundle_qty > 0 THEN td.qty * p.bundle_qty ELSE 0 END) as total_dimsum,
           SUM(CASE WHEN (p.name LIKE '%saus bangkok%' OR p.name LIKE '%saos bangkok%' OR p.name LIKE '%sauce bangkok%' OR p.name LIKE '%bangkok%') THEN td.qty ELSE 0 END) as total_saus
         FROM transactions t
         JOIN transaction_details td ON t.id = td.transaction_id
         JOIN products p ON td.product_id = p.id
         JOIN shops s ON t.shop_id = s.id
         WHERE t.status != 'void' AND t.id IN (:txIds)
         GROUP BY s.id, s.name`,
        {
          replacements: { txIds },
          type: QueryTypes.SELECT,
        }
      );

      const shopSalesResult = await sequelize.query(
        `SELECT t.shop_id, SUM(t.total_price) as total_sales
         FROM transactions t
         WHERE t.status != 'void' AND t.id IN (:txIds)
         GROUP BY t.shop_id`,
        {
          replacements: { txIds },
          type: QueryTypes.SELECT,
        }
      );
      const salesMap = {};
      shopSalesResult.forEach((s) => {
        salesMap[s.shop_id] = parseFloat(s.total_sales || 0.0);
      });

      const shopProductDetails = await sequelize.query(
        `SELECT t.shop_id, p.name as product_name, SUM(td.qty) as total_qty
         FROM transactions t
         JOIN transaction_details td ON t.id = td.transaction_id
         JOIN products p ON td.product_id = p.id
         WHERE t.status != 'void' AND t.id IN (:txIds)
         GROUP BY t.shop_id, p.name`,
        {
          replacements: { txIds },
          type: QueryTypes.SELECT,
        }
      );
      const prodDetailsMap = {};
      shopProductDetails.forEach((pd) => {
        prodDetailsMap[pd.shop_id] = prodDetailsMap[pd.shop_id] || [];
        prodDetailsMap[pd.shop_id].push({
          product_name: pd.product_name,
          total_qty: parseInt(pd.total_qty || 0),
        });
      });

      shopStatsTransformed = shopItemsResult.map((item) => {
        return {
          shop_id: item.shop_id,
          shop_name: item.shop_name,
          total_dimsum: parseInt(item.total_dimsum || 0),
          total_saus: parseInt(item.total_saus || 0),
          total_sales: salesMap[item.shop_id] || 0.0,
          products: prodDetailsMap[item.shop_id] || [],
        };
      }).sort((a, b) => b.total_sales - a.total_sales);
    }

    // Limit transactions query
    const limit = (start_date || end_date || shop_id) ? 500 : 50;
    const transactions = await Transaction.findAll({
      where,
      include: [
        { association: 'items', include: ['product'] },
        { association: 'shop' },
        { association: 'user' },
      ],
      limit,
      order: [['id', 'DESC']],
    });

    return res.status(200).json({
      status: 'success',
      summary,
      shop_stats: shopStatsTransformed,
      data: transactions,
    });
  } catch (error) {
    console.error('Get Transactions List Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};

export const store = async (req, res) => {
  const user = req.user;
  const { items, total_price, pay_amount, payment_method, shop_id, invoice_number, subtotal, discount, tax, status, note, void_by, created_at } = req.body;

  if (!items || !total_price || pay_amount === undefined || !payment_method) {
    return res.status(422).json({ message: 'Data wajib diisi (items, total_price, pay_amount, payment_method).' });
  }

  const targetShopId = shop_id || user.shopId;
  if (!targetShopId) {
    return res.status(422).json({
      status: 'error',
      message: 'Gagal: Transaksi tidak memiliki ID Toko. Harap login ulang di aplikasi.',
    });
  }

  // Idempotency check:
  if (invoice_number) {
    const existing = await Transaction.findOne({
      where: { invoiceNumber: invoice_number },
      include: [{ association: 'items', include: ['product'] }],
    });
    if (existing) {
      return res.status(200).json({
        status: 'success',
        message: 'Transaksi sudah tersimpan sebelumnya (Duplikat dicegah)',
        data: existing,
      });
    }
  }

  // Generate invoice number
  let finalInvoiceNumber = invoice_number;
  if (!finalInvoiceNumber) {
    const datePrefix = new Date().toISOString().substring(0, 10).replace(/-/g, '');
    const timePrefix = new Date().toTimeString().substring(0, 8).replace(/:/g, '');
    finalInvoiceNumber = `${datePrefix}/${targetShopId}/${timePrefix}`;
  }

  const tx = await sequelize.transaction();

  try {
    const shop = await Shop.findByPk(targetShopId, { transaction: tx });
    if (!shop) {
      throw new Error('Toko tidak ditemukan.');
    }

    const transaction = await Transaction.create({
      shopId: targetShopId,
      userId: user.id,
      invoiceNumber: finalInvoiceNumber,
      subtotal: subtotal || total_price,
      discount: discount || 0.00,
      tax: tax || 0.00,
      totalPrice: total_price,
      payAmount: pay_amount,
      changeAmount: (pay_amount - total_price) || 0.00,
      paymentMethod: payment_method,
      status: status || 'completed',
      note: note || null,
      voidBy: void_by || null,
      createdAt: created_at ? new Date(created_at) : new Date(),
    }, { transaction: tx });

    for (const item of items) {
      const product = await Product.findByPk(item.product_id, { transaction: tx });
      if (!product) continue;

      const qty = parseInt(item.qty || item.quantity || 1);

      await TransactionDetail.create({
        transactionId: transaction.id,
        productId: product.id,
        qty,
        price: product.price,
        subtotal: product.price * qty,
      }, { transaction: tx });

      // Stock Deduction
      if (transaction.status !== 'void') {
        const isDimsum = product.bundleQty > 0;
        const bundleQty = product.bundleQty || 1;
        const totalDeduct = qty * bundleQty;

        if (isDimsum) {
          await shop.decrement('stock', { by: totalDeduct, transaction: tx });
        } else {
          const [psRecord, psCreated] = await ProductStock.findOrCreate({
            where: { shopId: targetShopId, productId: product.id },
            defaults: { stock: -qty, plannedStock: 0, requestedStock: 0 },
            transaction: tx,
          });
          if (!psCreated) {
            await psRecord.decrement('stock', { by: qty, transaction: tx });
          }
        }
      }
    }

    await tx.commit();

    // Reload with items & products
    const reloaded = await Transaction.findByPk(transaction.id, {
      include: [{ association: 'items', include: ['product'] }],
    });

    return res.status(201).json({
      status: 'success',
      message: 'Transaksi berhasil disimpan',
      data: reloaded,
    });
  } catch (error) {
    await tx.rollback();
    console.error('Store Transaction Error:', error);
    return res.status(500).json({
      status: 'error',
      message: error.message,
    });
};

export const show = async (req, res) => {
  try {
    const { id } = req.params;
    const transaction = await Transaction.findByPk(id, {
      include: [
        { association: 'items', include: ['product'] },
        { association: 'shop' },
        { association: 'user' },
      ],
    });
    if (!transaction) {
      return res.status(404).json({ status: 'error', message: 'Transaksi tidak ditemukan.' });
    }
    return res.status(200).json({ status: 'success', data: transaction });
  } catch (error) {
    console.error('Show Transaction Error:', error);
    return res.status(500).json({ status: 'error', message: error.message });
  }
};

export const update = async (req, res) => {
  const { id } = req.params;
  const { items, total_price, pay_amount, payment_method, subtotal, discount, tax, status, note, created_at } = req.body;

  if (!items || !total_price || pay_amount === undefined) {
    return res.status(422).json({ message: 'Data wajib diisi (items, total_price, pay_amount).' });
  }

  const tx = await sequelize.transaction();

  try {
    const transaction = await Transaction.findByPk(id, {
      include: [{ association: 'items', include: ['product'] }],
      transaction: tx,
    });

    if (!transaction) {
      return res.status(404).json({ status: 'error', message: 'Transaksi tidak ditemukan.' });
    }

    const shop = await Shop.findByPk(transaction.shopId, { transaction: tx });
    if (!shop) {
      throw new Error('Toko tidak ditemukan.');
    }

    // 1. Revert Old Stock (if old status was not void)
    if (transaction.status !== 'void') {
      await adjustStockForTransaction(transaction.id, 'increment', tx);
    }

    // 2. Delete Old Details directly
    await TransactionDetail.destroy({
      where: { transactionId: transaction.id },
      transaction: tx,
    });

    // 3. Update main transaction
    const newStatus = status || transaction.status;
    await transaction.update({
      subtotal: subtotal || total_price,
      totalPrice: total_price,
      payAmount: pay_amount,
      changeAmount: (pay_amount - total_price) || 0.00,
      paymentMethod: payment_method || transaction.paymentMethod,
      status: newStatus,
      note: note || transaction.note,
      createdAt: created_at ? new Date(created_at) : transaction.createdAt,
    }, { transaction: tx });

    // 4. Create new details & deduct stock
    for (const item of items) {
      const product = await Product.findByPk(item.product_id, { transaction: tx });
      if (!product) continue;

      const qty = parseInt(item.qty || item.quantity || 1);

      await TransactionDetail.create({
        transactionId: transaction.id,
        productId: product.id,
        qty,
        price: product.price,
        subtotal: product.price * qty,
      }, { transaction: tx });

      // Deduct stock if new status is not void
      if (newStatus !== 'void') {
        const isDimsum = product.bundleQty > 0;
        const bundleQty = product.bundleQty || 1;
        const totalDeduct = qty * bundleQty;

        if (isDimsum) {
          await shop.decrement('stock', { by: totalDeduct, transaction: tx });
        } else {
          const [psRecord, psCreated] = await ProductStock.findOrCreate({
            where: { shopId: transaction.shopId, productId: product.id },
            defaults: { stock: -qty, plannedStock: 0, requestedStock: 0 },
            transaction: tx,
          });
          if (!psCreated) {
            await psRecord.decrement('stock', { by: qty, transaction: tx });
          }
        }
      }
    }

    await tx.commit();

    const reloaded = await Transaction.findByPk(transaction.id, {
      include: [{ association: 'items', include: ['product'] }],
    });

    return res.status(200).json({
      status: 'success',
      message: 'Transaksi berhasil diperbarui.',
      data: reloaded,
    });
  } catch (error) {
    await tx.rollback();
    console.error('Update Transaction Error:', error);
    return res.status(500).json({
      status: 'error',
      message: error.message,
    });
  }
};

export const destroy = async (req, res) => {
  const { id } = req.params;

  if (req.user.role !== 'admin') {
    return res.status(403).json({
      status: 'error',
      message: 'Hanya admin yang dapat menghapus transaksi.',
    });
  }

  const tx = await sequelize.transaction();

  try {
    const transaction = await Transaction.findByPk(id, { transaction: tx });
    if (!transaction) {
      return res.status(404).json({ status: 'error', message: 'Transaksi tidak ditemukan.' });
    }

    // Revert stock if not void
    if (transaction.status !== 'void') {
      await adjustStockForTransaction(transaction.id, 'increment', tx);
    }

    await transaction.destroy({ transaction: tx });

    await tx.commit();

    return res.status(200).json({
      status: 'success',
      message: 'Transaksi berhasil dihapus dan stok telah dikembalikan.',
    });
  } catch (error) {
    await tx.rollback();
    console.error('Destroy Transaction Error:', error);
    return res.status(500).json({
      status: 'error',
      message: error.message,
    });
  }
};

export const voidTransaction = async (req, res) => {
  const user = req.user;
  const { id } = req.params;

  const tx = await sequelize.transaction();

  try {
    const transaction = await Transaction.findByPk(id, { transaction: tx });
    if (!transaction) {
      return res.status(404).json({ status: 'error', message: 'Transaksi tidak ditemukan.' });
    }

    if (transaction.status === 'void') {
      return res.status(422).json({
        status: 'error',
        message: 'Transaksi ini sudah dibatalkan sebelumnya.',
      });
    }

    // Update status to void
    await transaction.update({
      status: 'void',
      voidBy: user.name,
    }, { transaction: tx });

    // Revert stock
    await adjustStockForTransaction(transaction.id, 'increment', tx);

    await tx.commit();

    return res.status(200).json({
      status: 'success',
      message: 'Transaksi berhasil dibatalkan dan stok telah dikembalikan.',
      data: transaction,
    });
  } catch (error) {
    await tx.rollback();
    console.error('Void Transaction Error:', error);
    return res.status(500).json({
      status: 'error',
      message: error.message,
    });
  }
};
