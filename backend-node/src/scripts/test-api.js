import { sequelize, User } from '../models/index.js';
import bcrypt from 'bcryptjs';

const runVerification = async () => {
  console.log('--- Verification Script ---');
  try {
    // 1. Authenticate Database
    await sequelize.authenticate();
    console.log('✓ Database Connection: Success');

    // 2. Query admin user
    const adminUser = await User.findOne({
      where: { email: 'admin@gmail.com' },
      include: ['shop'],
    });

    if (adminUser) {
      console.log(`✓ Admin User Found: ${adminUser.name} (${adminUser.email})`);
      console.log(`✓ Shop Association Found: ${adminUser.shop ? adminUser.shop.name : 'No shop'}`);

      // 3. Test Bcrypt compatibility with Laravel hashes
      const isMatch = await bcrypt.compare('password', adminUser.password);
      console.log(`✓ Password Hash Compatibility (Bcrypt): ${isMatch ? 'Success' : 'Failed'}`);
    } else {
      console.warn('✗ Admin User (admin@gmail.com) not found in database. Make sure seeds are run.');
    }

    console.log('--- Verification Completed Successfully ---');
    process.exit(0);
  } catch (error) {
    console.error('✗ Verification Failed:', error);
    process.exit(1);
  }
};

runVerification();
