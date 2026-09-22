import multer from 'multer';
import path from 'path';
import fs from 'fs';
import crypto from 'crypto';

const getDestination = (subfolder) => {
  const basePath = process.env.STORAGE_PATH || '/Users/ryanrizqimaulana/Documents/Aplikasi/dimsum/storage/app/public';
  const dir = path.join(basePath, subfolder);
  if (!fs.existsSync(dir)) {
    fs.mkdirSync(dir, { recursive: true });
  }
  return dir;
};

const createStorage = (subfolder) => {
  return multer.diskStorage({
    destination: (req, file, cb) => {
      cb(null, getDestination(subfolder));
    },
    filename: (req, file, cb) => {
      const ext = path.extname(file.originalname);
      const randomName = crypto.randomBytes(20).toString('hex');
      cb(null, randomName + ext);
    },
  });
};

const fileFilter = (req, file, cb) => {
  if (file.mimetype.startsWith('image/')) {
    cb(null, true);
  } else {
    cb(new Error('Only images are allowed!'), false);
  }
};

export const uploadProduct = multer({
  storage: createStorage('products'),
  fileFilter,
  limits: { fileSize: 2 * 1024 * 1024 }
});

export const uploadAttendance = multer({
  storage: createStorage('attendances'),
  fileFilter,
  limits: { fileSize: 5 * 1024 * 1024 }
});

export const uploadLogo = multer({
  storage: createStorage('logos'),
  fileFilter,
  limits: { fileSize: 2 * 1024 * 1024 }
});
