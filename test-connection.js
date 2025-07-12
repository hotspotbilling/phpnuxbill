#!/usr/bin/env node

import { connectDB } from './src/config/database.js';
import { Customer, User, Plan } from './src/models/index.js';

const testConnection = async () => {
  try {
    console.log('🔄 Testing database connection...');
    
    // Test database connection
    await connectDB();
    console.log('✅ Database connection successful');
    
    // Test models
    const userCount = await User.count();
    const customerCount = await Customer.count();
    const planCount = await Plan.count();
    
    console.log(`📊 Statistics:`);
    console.log(`   - Users: ${userCount}`);
    console.log(`   - Customers: ${customerCount}`);
    console.log(`   - Plans: ${planCount}`);
    
    if (userCount === 0) {
      console.log('⚠️  No users found. Run "npm run migrate" to create sample data.');
    }
    
    console.log('✅ All tests passed!');
    process.exit(0);
    
  } catch (error) {
    console.error('❌ Test failed:', error.message);
    
    if (error.name === 'SequelizeConnectionError') {
      console.error('💡 Database connection failed. Please check:');
      console.error('   - MySQL server is running');
      console.error('   - Database credentials in .env file');
      console.error('   - Database exists');
    }
    
    process.exit(1);
  }
};

testConnection();
