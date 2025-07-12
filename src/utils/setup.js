import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

// Create necessary directories
const createDirectories = () => {
  const directories = [
    'logs',
    'uploads',
    'public',
    'src/views',
    'src/views/admin',
    'src/views/customer',
    'src/views/auth',
    'src/views/errors',
    'src/views/layouts',
    'src/views/partials'
  ];

  directories.forEach(dir => {
    const dirPath = path.join(__dirname, dir);
    if (!fs.existsSync(dirPath)) {
      fs.mkdirSync(dirPath, { recursive: true });
      console.log(`✅ Created directory: ${dir}`);
    }
  });
};

// Create a basic logger
const createLogger = () => {
  const logPath = path.join(__dirname, 'logs');
  if (!fs.existsSync(logPath)) {
    fs.mkdirSync(logPath, { recursive: true });
  }
  
  const logFile = path.join(logPath, 'app.log');
  if (!fs.existsSync(logFile)) {
    fs.writeFileSync(logFile, `PHPNuxBill-JS Application Log\nStarted: ${new Date().toISOString()}\n\n`);
  }
};

// Setup function
const setup = () => {
  console.log('🚀 Setting up PHPNuxBill-JS...');
  
  createDirectories();
  createLogger();
  
  console.log('✅ Setup completed successfully!');
  console.log('🔧 Next steps:');
  console.log('   1. Configure your .env file');
  console.log('   2. Set up your MySQL database');
  console.log('   3. Run: npm run migrate (if needed)');
  console.log('   4. Run: npm start');
};

export default setup;

// Run setup if called directly
if (import.meta.url === `file://${process.argv[1]}`) {
  setup();
}
