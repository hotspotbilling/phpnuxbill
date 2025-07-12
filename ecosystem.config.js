module.exports = {
  apps: [
    {
      name: 'robdius',
      script: './server.js',
      instances: 'max',
      exec_mode: 'cluster',
      env: {
        NODE_ENV: 'production',
        PORT: 3000,
      },
      env_production: {
        NODE_ENV: 'production',
        PORT: 3000,
      },
      log_date_format: 'YYYY-MM-DD HH:mm:ss Z',
      error_file: './logs/err.log',
      out_file: './logs/out.log',
      log_file: './logs/combined.log',
      time: true,
      autorestart: true,
      max_restarts: 10,
      min_uptime: '10s',
      max_memory_restart: '1G',
      merge_logs: true,
      watch: false,
      ignore_watch: ['node_modules', 'logs', 'uploads'],
      watch_options: {
        followSymlinks: false,
        usePolling: true,
        interval: 1000
      }
    }
  ]
};
