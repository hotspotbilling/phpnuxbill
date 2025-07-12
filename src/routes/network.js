import express from 'express';
import { body, param, validationResult } from 'express-validator';
import { Op } from 'sequelize';
import { Bandwidth, Pool, Router } from '../models/index.js';
import { adminOnly } from '../middleware/auth.js';

const router = express.Router();

// Bandwidth Management
router.get('/bandwidth/list', adminOnly, async (req, res) => {
  try {
    const { search, page = 1, limit = 10 } = req.query;
    const offset = (page - 1) * limit;
    
    const whereClause = search ? {
      name_bw: { [Op.like]: `%${search}%` }
    } : {};

    const bandwidths = await Bandwidth.findAndCountAll({
      where: whereClause,
      offset,
      limit: parseInt(limit),
      order: [['id', 'DESC']]
    });

    res.render('admin/network/bandwidth', {
      title: 'Bandwidth Plans',
      page: 'network',
      bandwidths: bandwidths.rows,
      pagination: {
        page: parseInt(page),
        limit: parseInt(limit),
        total: bandwidths.count,
        totalPages: Math.ceil(bandwidths.count / limit)
      }
    });
  } catch (error) {
    console.error('Bandwidth list error:', error);
    res.status(500).render('errors/500', {
      title: 'Server Error',
      message: 'Failed to load bandwidth plans'
    });
  }
});

router.post('/bandwidth/add', [
  adminOnly,
  body('name_bw').trim().isLength({ min: 1 }).withMessage('Bandwidth name is required'),
  body('rate_down').isInt({ min: 0 }).withMessage('Download rate must be a positive number'),
  body('rate_up').isInt({ min: 0 }).withMessage('Upload rate must be a positive number'),
  body('rate_down_unit').isIn(['Kbps', 'Mbps']).withMessage('Invalid download rate unit'),
  body('rate_up_unit').isIn(['Kbps', 'Mbps']).withMessage('Invalid upload rate unit')
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ 
        success: false, 
        errors: errors.array() 
      });
    }

    const bandwidth = await Bandwidth.create(req.body);

    res.json({
      success: true,
      message: 'Bandwidth plan created successfully',
      bandwidth: bandwidth.id
    });
  } catch (error) {
    console.error('Bandwidth create error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to create bandwidth plan'
    });
  }
});

router.put('/bandwidth/edit/:id', [
  adminOnly,
  param('id').isInt({ min: 1 }).withMessage('Invalid bandwidth ID'),
  body('name_bw').trim().isLength({ min: 1 }).withMessage('Bandwidth name is required')
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ 
        success: false, 
        errors: errors.array() 
      });
    }

    const bandwidth = await Bandwidth.findByPk(req.params.id);
    if (!bandwidth) {
      return res.status(404).json({
        success: false,
        message: 'Bandwidth plan not found'
      });
    }

    await bandwidth.update(req.body);

    res.json({
      success: true,
      message: 'Bandwidth plan updated successfully'
    });
  } catch (error) {
    console.error('Bandwidth update error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to update bandwidth plan'
    });
  }
});

router.delete('/bandwidth/delete/:id', [
  adminOnly,
  param('id').isInt({ min: 1 }).withMessage('Invalid bandwidth ID')
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ 
        success: false, 
        errors: errors.array() 
      });
    }

    const bandwidth = await Bandwidth.findByPk(req.params.id);
    if (!bandwidth) {
      return res.status(404).json({
        success: false,
        message: 'Bandwidth plan not found'
      });
    }

    await bandwidth.destroy();

    res.json({
      success: true,
      message: 'Bandwidth plan deleted successfully'
    });
  } catch (error) {
    console.error('Bandwidth delete error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to delete bandwidth plan'
    });
  }
});

// Pool Management
router.get('/pool/list', adminOnly, async (req, res) => {
  try {
    const { search, page = 1, limit = 10 } = req.query;
    const offset = (page - 1) * limit;
    
    const whereClause = search ? {
      pool_name: { [Op.like]: `%${search}%` }
    } : {};

    const pools = await Pool.findAndCountAll({
      where: whereClause,
      offset,
      limit: parseInt(limit),
      order: [['id', 'DESC']]
    });

    const routers = await Router.findAll({
      where: { enabled: '1' },
      order: [['name', 'ASC']]
    });

    res.render('admin/network/pool', {
      title: 'IP Pools',
      page: 'network',
      pools: pools.rows,
      routers,
      pagination: {
        page: parseInt(page),
        limit: parseInt(limit),
        total: pools.count,
        totalPages: Math.ceil(pools.count / limit)
      }
    });
  } catch (error) {
    console.error('Pool list error:', error);
    res.status(500).render('errors/500', {
      title: 'Server Error',
      message: 'Failed to load IP pools'
    });
  }
});

router.post('/pool/add', [
  adminOnly,
  body('pool_name').trim().isLength({ min: 1 }).withMessage('Pool name is required'),
  body('range_ip').trim().isLength({ min: 1 }).withMessage('IP range is required'),
  body('routers').trim().isLength({ min: 1 }).withMessage('Router is required')
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ 
        success: false, 
        errors: errors.array() 
      });
    }

    const pool = await Pool.create(req.body);

    res.json({
      success: true,
      message: 'IP pool created successfully',
      pool: pool.id
    });
  } catch (error) {
    console.error('Pool create error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to create IP pool'
    });
  }
});

router.put('/pool/edit/:id', [
  adminOnly,
  param('id').isInt({ min: 1 }).withMessage('Invalid pool ID'),
  body('pool_name').trim().isLength({ min: 1 }).withMessage('Pool name is required')
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ 
        success: false, 
        errors: errors.array() 
      });
    }

    const pool = await Pool.findByPk(req.params.id);
    if (!pool) {
      return res.status(404).json({
        success: false,
        message: 'IP pool not found'
      });
    }

    await pool.update(req.body);

    res.json({
      success: true,
      message: 'IP pool updated successfully'
    });
  } catch (error) {
    console.error('Pool update error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to update IP pool'
    });
  }
});

router.delete('/pool/delete/:id', [
  adminOnly,
  param('id').isInt({ min: 1 }).withMessage('Invalid pool ID')
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ 
        success: false, 
        errors: errors.array() 
      });
    }

    const pool = await Pool.findByPk(req.params.id);
    if (!pool) {
      return res.status(404).json({
        success: false,
        message: 'IP pool not found'
      });
    }

    await pool.destroy();

    res.json({
      success: true,
      message: 'IP pool deleted successfully'
    });
  } catch (error) {
    console.error('Pool delete error:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to delete IP pool'
    });
  }
});

export default router;
