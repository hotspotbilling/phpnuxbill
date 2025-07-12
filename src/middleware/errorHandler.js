import { Log } from '../models/index.js';

export const errorHandler = (err, req, res, next) => {
  // Log the error
  console.error(err.stack);
  
  // Log to database
  Log.create({
    type: 'error',
    description: `${err.message} - ${req.method} ${req.path}`,
    userid: req.user?.id || 0,
    ip: req.ip || req.connection.remoteAddress
  }).catch(dbErr => {
    console.error('Error logging to database:', dbErr);
  });

  // Default to 500 server error
  let error = { ...err };
  error.message = err.message;

  // Mongoose bad ObjectId
  if (err.name === 'CastError') {
    const message = 'Resource not found';
    error = { message, statusCode: 404 };
  }

  // Mongoose duplicate key
  if (err.code === 11000) {
    const message = 'Duplicate field value entered';
    error = { message, statusCode: 400 };
  }

  // Mongoose validation error
  if (err.name === 'ValidationError') {
    const message = Object.values(err.errors).map(val => val.message);
    error = { message, statusCode: 400 };
  }

  // Sequelize validation error
  if (err.name === 'SequelizeValidationError') {
    const message = err.errors.map(e => e.message).join(', ');
    error = { message, statusCode: 400 };
  }

  // Sequelize unique constraint error
  if (err.name === 'SequelizeUniqueConstraintError') {
    const message = 'Duplicate entry';
    error = { message, statusCode: 400 };
  }

  const statusCode = error.statusCode || 500;
  const message = error.message || 'Server Error';

  res.status(statusCode).json({
    success: false,
    error: message,
    ...(process.env.NODE_ENV === 'development' && { stack: err.stack })
  });
};
