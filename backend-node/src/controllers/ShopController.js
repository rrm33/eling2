import fs from 'fs';
import path from 'path';
import { Shop, Product, ProductStock } from '../models/index.js';

const STORAGE_PATH = process.env.STORAGE_PATH || '/Users/ryanrizqimaulana/Documents/Aplikasi/dimsum/storage/app/public';

export const index = async (req, res) => {
  try {
    const shops = await Shop.findAll({ order: [['id', 'ASC']] });
    return res.status(200).json(shops);
  } catch (error) {
    console.error('Get Shops Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};

export const store = async (req, res) => {
  try {
    const { name, address, phone, stock, min_stock, is_main } = req.body;

    if (!name || !address) {
      return res.status(422).json({ message: 'Nama dan alamat wajib diisi.' });
    }

    let logo = null;
    if (req.file) {
      // Store relative path like 'logos/filename.png'
      logo = 'logos/' + req.file.filename;
    }

    const isMainVal = is_main === 'true' || is_main === true || is_main === 1;

    // If main, unset others
    if (isMainVal) {
      await Shop.update({ isMain: false }, { where: { isMain: true } });
    }

    const shop = await Shop.create({
      name,
      address,
      phone,
      logo,
      stock: stock ? parseInt(stock) : 0,
      minStock: min_stock ? parseInt(min_stock) : 100,
      isMain: isMainVal,
    });

    // Automatically create product_stocks records for all products
    const products = await Product.findAll();
    for (const product of products) {
      await ProductStock.create({
        shopId: shop.id,
        productId: product.id,
        stock: 0,
        plannedStock: 0,
        requestedStock: 0,
      });
    }

    return res.status(201).json({
      message: 'Toko berhasil ditambahkan.',
      data: shop,
    });
  } catch (error) {
    console.error('Store Shop Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};

export const update = async (req, res) => {
  try {
    const { id } = req.params;
    const { name, address, phone, stock, min_stock, is_main } = req.body;

    const shop = await Shop.findByPk(id);
    if (!shop) {
      return res.status(404).json({ message: 'Toko tidak ditemukan.' });
    }

    if (!name || !address) {
      return res.status(422).json({ message: 'Nama dan alamat wajib diisi.' });
    }

    let logo = shop.logo;
    if (req.file) {
      // Delete old logo
      if (shop.logo) {
        const oldLogoPath = path.join(STORAGE_PATH, shop.logo);
        if (fs.existsSync(oldLogoPath)) {
          fs.unlinkSync(oldLogoPath);
        }
      }
      logo = 'logos/' + req.file.filename;
    }

    const isMainVal = is_main === 'true' || is_main === true || is_main === 1;

    if (isMainVal && !shop.isMain) {
      await Shop.update({ isMain: false }, { where: { isMain: true } });
    }

    await shop.update({
      name,
      address,
      phone,
      logo,
      stock: stock !== undefined ? parseInt(stock) : shop.stock,
      minStock: min_stock !== undefined ? parseInt(min_stock) : shop.minStock,
      isMain: isMainVal,
    });

    return res.status(200).json({
      message: 'Toko berhasil diperbarui.',
      data: shop,
    });
  } catch (error) {
    console.error('Update Shop Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};

export const destroy = async (req, res) => {
  try {
    const { id } = req.params;

    const shop = await Shop.findByPk(id);
    if (!shop) {
      return res.status(404).json({ message: 'Toko tidak ditemukan.' });
    }

    if (shop.isMain) {
      return res.status(422).json({ message: 'Toko Pusat tidak bisa dihapus.' });
    }

    // Check if shop has users (using association)
    const userCount = await shop.countUsers();
    if (userCount > 0) {
      return res.status(422).json({ message: 'Toko tidak bisa dihapus karena masih memiliki staff.' });
    }

    // Delete logo file
    if (shop.logo) {
      const logoPath = path.join(STORAGE_PATH, shop.logo);
      if (fs.existsSync(logoPath)) {
        fs.unlinkSync(logoPath);
      }
    }

    await shop.destroy();

    return res.status(200).json({ message: 'Toko berhasil dihapus.' });
  } catch (error) {
    console.error('Destroy Shop Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};

export const receiveStock = async (req, res) => {
  try {
    const user = req.user;
    const shopId = user.shopId;

    if (!shopId) {
      return res.status(422).json({ message: 'User tidak terikat dengan cabang mana pun.' });
    }

    const shop = await Shop.findOne({
      where: { id: shopId },
      include: ['productStocks'],
    });

    if (!shop) {
      return res.status(404).json({ message: 'Cabang tidak ditemukan.' });
    }

    const mainPlanned = parseInt(shop.plannedStock || 0);
    // Sum product stocks planned
    const productsPlanned = shop.productStocks.reduce((sum, ps) => sum + parseInt(ps.plannedStock || 0), 0);
    const totalPlanned = mainPlanned + productsPlanned;

    if (totalPlanned <= 0) {
      return res.status(422).json({ message: 'Tidak ada kiriman barang yang perlu diterima.' });
    }

    if (mainPlanned > 0) {
      shop.stock += mainPlanned;
      shop.plannedStock = 0;
      shop.requestedStock = 0;
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

    await shop.save();

    return res.status(200).json({
      message: `Berhasil menerima kiriman barang (${totalPlanned} unit).`,
      stock: shop.stock,
    });
  } catch (error) {
    console.error('Receive Stock Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};

export const updateBranchStocks = async (req, res) => {
  try {
    const { shop_id } = req.params;
    const { stocks } = req.body;

    if (!stocks || typeof stocks !== 'object') {
      return res.status(422).json({ message: 'Data stocks wajib berupa object.' });
    }

    const shop = await Shop.findByPk(shop_id);
    if (!shop) {
      return res.status(404).json({ message: 'Cabang tidak ditemukan.' });
    }

    for (const [productId, stockVal] of Object.entries(stocks)) {
      const pId = parseInt(productId);
      const stock = parseInt(stockVal || 0);

      if (pId === 0) {
        // Update main dimsum stock
        await shop.update({ stock });
      } else {
        const [psRecord, psCreated] = await ProductStock.findOrCreate({
          where: { shopId: shop_id, productId: pId },
          defaults: { stock }
        });
        if (!psCreated) {
          await psRecord.update({ stock });
        }
      }
    }

    return res.status(200).json({ message: 'Stok cabang berhasil diperbarui.' });
  } catch (error) {
    console.error('Update Branch Stocks Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};
