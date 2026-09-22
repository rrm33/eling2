import { Shop, Product, ProductStock } from '../models/index.js';

export const stockSummary = async (req, res) => {
  try {
    const user = req.user;
    const shopWhere = {};

    if (user.role === 'cashier' && user.shopId) {
      shopWhere.id = user.shopId;
    }

    const allShops = await Shop.findAll({ where: shopWhere });
    const nonVariantProducts = await Product.findAll({ where: { bundleQty: 0 } });

    const shopsDetail = [];
    let totalShopPlanned = 0;
    let totalProductsPlanned = 0;
    let lowStockShopsCount = 0;
    let totalStock = 0;

    for (const shop of allShops) {
      const existingStocks = await ProductStock.findAll({
        where: { shopId: shop.id },
      });
      const stockMap = {};
      existingStocks.forEach((s) => {
        stockMap[s.productId] = s;
      });

      const productsMapped = nonVariantProducts.map((product) => {
        const ps = stockMap[product.id];
        return {
          id: product.id,
          name: product.name,
          stock: ps ? parseInt(ps.stock || 0) : 0,
          planned_stock: ps ? parseInt(ps.plannedStock || 0) : 0,
          requested_stock: ps ? parseInt(ps.requestedStock || 0) : 0,
        };
      });

      // Accumulate totals
      totalStock += parseInt(shop.stock || 0);
      totalShopPlanned += parseInt(shop.plannedStock || 0);

      existingStocks.forEach((s) => {
        totalProductsPlanned += parseInt(s.plannedStock || 0);
      });

      const minStockThreshold = parseInt(shop.minStock !== null ? shop.minStock : 100);
      if (parseInt(shop.stock || 0) < minStockThreshold) {
        lowStockShopsCount++;
      }

      shopsDetail.push({
        id: shop.id,
        name: shop.name,
        main_stock_name: 'Produk Utama Dimsum',
        stock: parseInt(shop.stock || 0),
        planned_stock: parseInt(shop.plannedStock || 0),
        requested_stock: parseInt(shop.requestedStock || 0),
        min_stock: minStockThreshold,
        products: productsMapped,
      });
    }

    const totalPlanned = totalShopPlanned + totalProductsPlanned;

    return res.status(200).json({
      status: 'success',
      summary: {
        total_shops: allShops.length,
        total_items_to_prepare: totalPlanned,
        low_stock_shops_count: lowStockShopsCount,
        global_stock: totalStock,
      },
      shops_detail: shopsDetail,
      items_to_prepare: [
        {
          name: 'Produk Utama Dimsum',
          total_needed: totalShopPlanned,
          shops_low_stock: lowStockShopsCount,
        },
      ],
    });
  } catch (error) {
    console.error('Get Courier Stock Summary Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};

export const planDelivery = async (req, res) => {
  try {
    const { shop_id } = req.params;
    const { planned_stocks } = req.body;

    if (!planned_stocks || typeof planned_stocks !== 'object') {
      return res.status(422).json({ message: 'planned_stocks wajib berupa object.' });
    }

    const shop = await Shop.findByPk(shop_id);
    if (!shop) {
      return res.status(404).json({ message: 'Toko tidak ditemukan.' });
    }

    for (const [productId, qty] of Object.entries(planned_stocks)) {
      const pId = parseInt(productId);
      const qtyVal = parseInt(qty || 0);

      if (pId === 0) {
        await shop.update({ plannedStock: qtyVal });
      } else {
        const [psRecord, psCreated] = await ProductStock.findOrCreate({
          where: { shopId: shop.id, productId: pId },
          defaults: { plannedStock: qtyVal },
        });
        if (!psCreated) {
          await psRecord.update({ plannedStock: qtyVal });
        }
      }
    }

    return res.status(200).json({ message: 'Rencana pengiriman berhasil disimpan ke Toko.' });
  } catch (error) {
    console.error('Plan Delivery Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};

export const completeDelivery = async (req, res) => {
  try {
    const { shop_id } = req.params;
    const shop = await Shop.findOne({
      where: { id: shop_id },
      include: ['productStocks'],
    });

    if (!shop) {
      return res.status(404).json({ message: 'Toko tidak ditemukan.' });
    }

    if (shop.plannedStock > 0) {
      await shop.update({
        stock: shop.stock + shop.plannedStock,
        plannedStock: 0,
        requestedStock: 0,
      });
    }

    for (const ps of shop.productStocks) {
      if (ps.plannedStock > 0) {
        await ps.update({
          stock: ps.stock + ps.plannedStock,
          plannedStock: 0,
          requestedStock: 0,
        });
      }
    }

    return res.status(200).json({ message: 'Pengiriman selesai. Stok Toko telah bertambah.' });
  } catch (error) {
    console.error('Complete Delivery Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};

export const requestStock = async (req, res) => {
  try {
    const { requested_stocks } = req.body;
    if (!requested_stocks || typeof requested_stocks !== 'object') {
      return res.status(422).json({ message: 'requested_stocks wajib berupa object.' });
    }

    const user = req.user;
    const shopId = user.shopId;

    if (!shopId) {
      return res.status(422).json({ message: 'User tidak terikat dengan cabang mana pun.' });
    }

    const shop = await Shop.findByPk(shopId);
    if (!shop) {
      return res.status(404).json({ message: 'Toko tidak ditemukan.' });
    }

    for (const [productId, qty] of Object.entries(requested_stocks)) {
      const pId = parseInt(productId);
      const qtyVal = parseInt(qty || 0);

      if (pId === 0) {
        await shop.update({ requestedStock: qtyVal });
      } else {
        const [psRecord, psCreated] = await ProductStock.findOrCreate({
          where: { shopId: shopId, productId: pId },
          defaults: { requestedStock: qtyVal },
        });
        if (!psCreated) {
          await psRecord.update({ requestedStock: qtyVal });
        }
      }
    }

    return res.status(200).json({ message: 'Pengajuan stok berhasil dikirim.' });
  } catch (error) {
    console.error('Request Stock Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};

export const clearRequest = async (req, res) => {
  try {
    const { shop_id } = req.params;
    const shop = await Shop.findOne({
      where: { id: shop_id },
      include: ['productStocks'],
    });

    if (!shop) {
      return res.status(404).json({ message: 'Toko tidak ditemukan.' });
    }

    await shop.update({ requestedStock: 0 });

    for (const ps of shop.productStocks) {
      await ps.update({ requestedStock: 0 });
    }

    return res.status(200).json({ message: 'Pengajuan stok berhasil dihapus.' });
  } catch (error) {
    console.error('Clear Request Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};
