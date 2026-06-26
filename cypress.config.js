const { defineConfig } = require('cypress');

module.exports = defineConfig({
  e2e: {
    baseUrl: 'https://pt24.pro',
    specPattern: 'cypress/e2e/**/*.cy.js',
    supportFile: 'cypress/support/e2e.js',
    
    // Test timeout settings
    defaultCommandTimeout: 10000,
    requestTimeout: 10000,
    
    // Retry settings
    retries: {
      runMode: 2,
      openMode: 0
    },
    
    // Browser configuration
    browser: ['chrome', 'firefox', 'edge'],
    headless: true,
    
    // Screenshot and video settings
    screenshotOnRunFailure: true,
    video: true,
    videoCompression: 32,
    
    // Test isolation
    numTestsKeptInMemory: 1,
    
    // Environment variables
    env: {
      API_KEY: process.env.PT24_API_KEY || 'test_key',
      BASE_URL: 'https://pt24.pro',
      WORDPRESS_ADMIN: process.env.WP_ADMIN_USER || 'admin',
      WORDPRESS_PASSWORD: process.env.WP_ADMIN_PASS || 'password'
    },
    
    // Viewport settings
    viewportWidth: 1280,
    viewportHeight: 720,
    
    // Test patterns to ignore
    excludeSpecPattern: [
      '**/node_modules/**',
      '**/fixtures/**'
    ]
  },
  
  component: {
    devServer: {
      framework: 'next',
      bundler: 'webpack'
    }
  }
});
