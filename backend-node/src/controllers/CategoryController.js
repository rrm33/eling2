import { Category } from '../models/index.js';

export const index = async (req, res) => {
  try {
    const categories = await Category.findAll({ order: [['name', 'ASC']] });
    return res.status(200).json(categories);
  } catch (error) {
    console.error('Get Categories Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};

export const store = async (req, res) => {
  try {
    const { name, description } = req.body;
    if (!name) {
      return res.status(422).json({ message: 'Nama kategori wajib diisi.' });
    }

    const category = await Category.create({ name, description });
    return res.status(201).json(category);
  } catch (error) {
    console.error('Store Category Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};

export const show = async (req, res) => {
  try {
    const { id } = req.params;
    const category = await Category.findByPk(id);
    if (!category) {
      return res.status(404).json({ message: 'Kategori tidak ditemukan.' });
    }
    return res.status(200).json(category);
  } catch (error) {
    console.error('Show Category Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};

export const update = async (req, res) => {
  try {
    const { id } = req.params;
    const { name, description } = req.body;

    const category = await Category.findByPk(id);
    if (!category) {
      return res.status(404).json({ message: 'Kategori tidak ditemukan.' });
    }

    if (name) category.name = name;
    if (description !== undefined) category.description = description;

    await category.save();

    return res.status(200).json(category);
  } catch (error) {
    console.error('Update Category Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};

export const destroy = async (req, res) => {
  try {
    const { id } = req.params;
    const category = await Category.findByPk(id);
    if (!category) {
      return res.status(404).json({ message: 'Kategori tidak ditemukan.' });
    }

    await category.destroy();
    return res.status(200).json({ message: 'Kategori berhasil dihapus.' });
  } catch (error) {
    console.error('Destroy Category Error:', error);
    return res.status(500).json({ message: 'Error server: ' + error.message });
  }
};
