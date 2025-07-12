import { AppConfig } from '../models/index.js';

export const maintenanceMiddleware = async (req, res, next) => {
  try {
    // Skip maintenance check for certain paths
    const excludedPaths = ['/api/health', '/maintenance'];
    if (excludedPaths.includes(req.path)) {
      return next();
    }

    const maintenanceMode = await AppConfig.findOne({
      where: { setting: 'maintenance_mode' }
    });

    if (maintenanceMode && maintenanceMode.value === '1') {
      return res.status(503).render('maintenance', {
        title: 'System Maintenance',
        message: 'System is currently under maintenance. Please try again later.'
      });
    }

    next();
  } catch (error) {
    console.error('Maintenance middleware error:', error);
    next();
  }
};
