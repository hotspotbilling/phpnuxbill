import express from 'express';
import { Router } from '../models/index.js';
import { adminOnly } from '../middleware/auth.js';

const router = express.Router();

// List routers
router.get('/', adminOnly, async (req, res) => {
  try {
    const routers = await Router.findAll({
      order: [['created_at', 'DESC']]
    });

    res.render('routers/list', {
      title: 'Routers',
      routers
    });

  } catch (error) {
    console.error('Routers list error:', error);
    res.status(500).render('errors/500', {
      title: 'Server Error',
      message: 'Failed to load routers'
    });
  }
});

export default router;
