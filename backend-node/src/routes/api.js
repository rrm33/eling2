import { Router } from 'express';
import * as AuthController from '../controllers/AuthController.js';
import * as UserController from '../controllers/UserController.js';
import * as ShopController from '../controllers/ShopController.js';
import * as AttendanceController from '../controllers/AttendanceController.js';
import * as FinanceController from '../controllers/FinanceController.js';
import * as CategoryController from '../controllers/CategoryController.js';
import * as ProductController from '../controllers/ProductController.js';
import * as TransactionController from '../controllers/TransactionController.js';
import * as CashierShiftController from '../controllers/CashierShiftController.js';
import * as CourierController from '../controllers/CourierController.js';
import * as ChatController from '../controllers/ChatController.js';

import auth from '../middlewares/auth.js';
import { uploadProduct, uploadAttendance, uploadLogo } from '../middlewares/upload.js';

const router = Router();

// Guest routes
router.post('/login', AuthController.login);
router.post('/login-google', AuthController.loginGoogle);
router.get('/app-version', (req, res) => {
  return res.status(200).json({
    version: '1.0.1',
    download_url: '/storage/app-release.apk',
  });
});

// Authenticated routes (under Sanctum Bearer token check)
router.use(auth);

router.get('/me', AuthController.me);
router.post('/logout', AuthController.logout);

// Courier Stock Summary
router.get('/courier/stock-summary', CourierController.stockSummary);
router.post('/courier/plan-delivery/:shop_id', CourierController.planDelivery);
router.post('/courier/complete-delivery/:shop_id', CourierController.completeDelivery);
router.post('/courier/request-stock', CourierController.requestStock);
router.post('/courier/clear-request/:shop_id', CourierController.clearRequest);

// Cashier Shifts
router.get('/cashier', CashierShiftController.index);
router.get('/cashier/current', CashierShiftController.current);
router.post('/cashier/open', CashierShiftController.open);
router.post('/cashier/close', CashierShiftController.close);
router.delete('/cashier/:id', CashierShiftController.destroy);

// Shop & User Management
router.post('/shops/receive-stock', ShopController.receiveStock);
router.post('/shops/update-branch-stocks/:shop_id', ShopController.updateBranchStocks);
router.post('/user/fcm-token', UserController.updateFcmToken);

// User resource CRUD
router.get('/users', UserController.index);
router.post('/users', UserController.store);
router.put('/users/:id', UserController.update);
router.delete('/users/:id', UserController.destroy);

// Shop resource CRUD
router.get('/shops', ShopController.index);
router.post('/shops', uploadLogo.single('logo'), ShopController.store);
router.put('/shops/:id', uploadLogo.single('logo'), ShopController.update);
router.delete('/shops/:id', ShopController.destroy);

// Attendance
router.post('/attendance', uploadAttendance.single('photo'), AttendanceController.store);
router.get('/attendance/history', AttendanceController.history);
router.delete('/attendance/:id', AttendanceController.destroy);

// Finance
router.get('/finance/summary', FinanceController.summary);
router.get('/finance/chart', FinanceController.chart);
router.get('/finance', FinanceController.index);
router.post('/finance', FinanceController.store);
router.put('/finance/:id', FinanceController.update);

// Categories
router.get('/categories', CategoryController.index);
router.post('/categories', CategoryController.store);
router.get('/categories/:id', CategoryController.show);
router.put('/categories/:id', CategoryController.update);
router.delete('/categories/:id', CategoryController.destroy);

// Products
router.post('/products/:id/stocks', ProductController.updateStocks);
router.get('/products', ProductController.index);
router.post('/products', uploadProduct.single('image'), ProductController.store);
router.get('/products/:id', ProductController.show);
router.put('/products/:id', uploadProduct.single('image'), ProductController.update);
router.delete('/products/:id', ProductController.destroy);

// Transactions
router.post('/transactions/:id/void', TransactionController.voidTransaction);
router.get('/transactions', TransactionController.index);
router.post('/transactions', TransactionController.store);
router.get('/transactions/:id', TransactionController.show);
router.put('/transactions/:id', TransactionController.update);
router.delete('/transactions/:id', TransactionController.destroy);

// Chat
router.get('/chat/unread-count', ChatController.getUnreadCount);
router.get('/chat/conversations', ChatController.getConversations);
router.get('/chat/messages/:shop_id?', ChatController.getMessages);
router.post('/chat/messages', ChatController.sendMessage);
router.post('/chat/read/:shop_id?', ChatController.markAsRead);

export default router;
