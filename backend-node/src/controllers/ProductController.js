import fs from 'fs';
import path from 'path';
import { Op } from 'sequelize';
import { Product, ProductStock } from '../models/index.js';

const STORAGE_PATH = process.env.STORAGE_PATH || '/Users/ryanrizqimaulana/Documents/Aplikasi/dimsum/storage/app/public';

export const index = async (req, res) => {
  try {
    const user = req.user;
    const { category_id, search } = req.query;

    const where = {};
    if (category_id) {
      where.categoryId = category_id;
    }
    if (search) {
      where.name = { [Op.like]: `%${search}%` };
    }

    const products = await Product.findAll({
      where,
      include: [
        { association: 'category' },
        { association: 'stocks' },
      ],
      order: [['id', 'DESC']],
    });

    const transformed = products.map((product) => {
      const p = product.get({ plain: true });

      if (user.shopId) {
        const branchStock = p.stocks.find((s) => s.shopId === user.shopId);
        p.stock = branchStock ? branchStock.stock : 0;
      }

      // If user is not admin, we don't necessarily have to return the full stocks array
      if (user.role !== 'admin') {
        delete p.stocks;
      }

      return p;
    });

    return res.status(200).json(transformed);
  } catch (error) {
    console.error('Get Products Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};

export const store = async (req, res) => {
  try {
    const {
      category_id,
      name,
      price,
      cost_price,
      parent_id,
      bundle_qty,
      min_stock,
      stock,
      status,
      branch_stocks,
    } = req.body;

    if (!category_id || !name || !price) {
      return res.status(422).json({ message: 'Data wajib diisi (category_id, name, price).' });
    }

    let image = null;
    if (req.file) {
      image = req.file.filename; // Save just the filename
    }

    const product = await Product.create({
      categoryId: category_id,
      name,
      price: parseFloat(price),
      costPrice: cost_price ? parseFloat(cost_price) : 0.0,
      image,
      parentId: parent_id || null,
      bundleQty: bundle_qty ? parseInt(bundle_qty) : 0,
      minStock: min_stock ? parseInt(min_stock) : 0,
      status: status !== undefined ? status === '1' || status === true : true,
    });

    // Add branch stocks
    if (branch_stocks) {
      // branch_stocks might be sent as JSON string or object
      let parsedBranchStocks = branch_stocks;
      if (typeof branch_stocks === 'string') {
        try {
          parsedBranchStocks = JSON.parse(branch_stocks);
        } catch (e) {
          console.warn('Failed parsing branch_stocks JSON string:', e);
        }
      }

      if (parsedBranchStocks && typeof parsedBranchStocks === 'object') {
        for (const [shopId, stockQty] of Object.entries(parsedBranchStocks)) {
          const sId = parseInt(shopId);
          const qty = parseInt(stockQty || 0);

          await ProductStock.findOrCreate({
            where: { shopId: sId, productId: product.id },
            defaults: { stock: qty, plannedStock: 0, requestedStock: 0 },
          });
        }
      }
    } else if (req.user.shopId) {
      const initialStock = stock ? parseInt(stock) : 0;
      await ProductStock.create({
        shopId: req.user.shopId,
        productId: product.id,
        stock: initialStock,
        plannedStock: 0,
        requestedStock: 0,
      });
    }

    return res.status(201).json({
      message: 'Produk berhasil ditambahkan',
      data: product,
    });
  } catch (error) {
    console.error('Store Product Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};

export const show = async (req, res) => {
  try {
    const { id } = req.params;
    const product = await Product.findByPk(id, {
      include: [{ association: 'category' }, { association: 'stocks' }],
    });

    if (!product) {
      return res.status(404).json({ message: 'Produk tidak ditemukan.' });
    }

    return res.status(200).json(product);
  } catch (error) {
    console.error('Show Product Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};

export const update = async (req, res) => {
  try {
    const { id } = req.params;
    const {
      category_id,
      name,
      price,
      cost_price,
      parent_id,
      bundle_qty,
      min_stock,
      stock,
      status,
      branch_stocks,
    } = req.body;

    const product = await Product.findByPk(id);
    if (!product) {
      return res.status(404).json({ message: 'Produk tidak ditemukan.' });
    }

    let image = product.image;
    if (req.file) {
      // Delete old photo
      if (product.image) {
        const oldImagePath = path.join(STORAGE_PATH, 'products', product.image);
        if (fs.existsSync(oldImagePath)) {
          fs.unlinkSync(oldImagePath);
        }
      }
      image = req.file.filename;
    }

    await product.update({
      categoryId: category_id || product.categoryId,
      name: name || product.name,
      price: price ? parseFloat(price) : product.price,
      costPrice: cost_price ? parseFloat(cost_price) : product.costPrice,
      image,
      parentId: parent_id !== undefined ? parent_id : product.parentId,
      bundleQty: bundle_qty !== undefined ? parseInt(bundle_qty) : product.bundleQty,
      minStock: min_stock !== undefined ? parseInt(min_stock) : product.minStock,
      status: status !== undefined ? status === '1' || status === true : product.status,
    });

    // Update branch stocks
    if (branch_stocks) {
      let parsedBranchStocks = branch_stocks;
      if (typeof branch_stocks === 'string') {
        try {
          parsedBranchStocks = JSON.parse(branch_stocks);
        } catch (e) {
          console.warn('Failed parsing branch_stocks JSON string:', e);
        }
      }

      if (parsedBranchStocks && typeof parsedBranchStocks === 'object') {
        for (const [shopId, stockQty] of Object.entries(parsedBranchStocks)) {
          const sId = parseInt(shopId);
          const qty = parseInt(stockQty || 0);

          const [psRecord, psCreated] = await ProductStock.findOrCreate({
            where: { shopId: sId, productId: product.id },
            defaults: { stock: qty },
          });
          if (!psCreated) {
            await psRecord.update({ stock: qty });
          }
        }
      }
    } else if (req.user.shopId && stock !== undefined) {
      const targetStock = parseInt(stock);
      const [psRecord, psCreated] = await ProductStock.findOrCreate({
        where: { shopId: req.user.shopId, productId: product.id },
        defaults: { stock: targetStock },
      });
      if (!psCreated) {
        await psRecord.update({ stock: targetStock });
      }
    }

    return res.status(200).json({
      message: 'Produk berhasil diperbarui',
      data: product,
    });
  } catch (error) {
    console.error('Update Product Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};

export const destroy = async (req, res) => {
  try {
    const { id } = req.params;
    const product = await Product.findByPk(id);
    if (!product) {
      return res.status(404).json({ message: 'Produk tidak ditemukan.' });
    }

    // Delete photo
    if (product.image) {
      const imagePath = path.join(STORAGE_PATH, 'products', product.image);
      if (fs.existsSync(imagePath)) {
        fs.unlinkSync(imagePath);
      }
    }

    await product.destroy();

    return res.status(200).json({ message: 'Produk berhasil dihapus' });
  } catch (error) {
    console.error('Destroy Product Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};

export const updateStocks = async (req, res) => {
  try {
    const { id } = req.params;
    const { branch_stocks } = req.body;

    if (!branch_stocks || typeof branch_stocks !== 'object') {
      return res.status(422).json({ message: 'Data branch_stocks wajib berupa object.' });
    }

    const product = await Product.findByPk(id);
    if (!product) {
      return res.status(404).json({ message: 'Produk tidak ditemukan.' });
    }

    for (const [shopId, stockQty] of Object.entries(branch_stocks)) {
      const sId = parseInt(shopId);
      const qty = parseInt(stockQty || 0);

      const [psRecord, psCreated] = await ProductStock.findOrCreate({
        where: { shopId: sId, productId: product.id },
        defaults: { stock: qty },
      });
      if (!psCreated) {
        await psRecord.update({ stock: qty });
      }
    }

    return res.status(200).json({ message: 'Stok produk di cabang berhasil diperbarui' });
  } catch (error) {
    console.error('Update Stocks Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};
