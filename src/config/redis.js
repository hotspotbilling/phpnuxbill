import { createClient } from 'redis';
import dotenv from 'dotenv';

dotenv.config();

const redisClient = createClient({
  url: process.env.REDIS_URL || 'redis://localhost:6379',
  password: process.env.REDIS_PASSWORD || undefined,
  database: process.env.REDIS_DB || 0
});

redisClient.on('error', (err) => {
  console.error('Redis Client Error:', err);
});

redisClient.on('connect', () => {
  console.log('✅ Redis connected successfully');
});

redisClient.on('ready', () => {
  console.log('🔄 Redis ready for use');
});

redisClient.on('end', () => {
  console.log('❌ Redis connection ended');
});

export const connectRedis = async () => {
  try {
    await redisClient.connect();
    return redisClient;
  } catch (error) {
    console.error('❌ Unable to connect to Redis:', error);
    throw error;
  }
};

export default redisClient;
