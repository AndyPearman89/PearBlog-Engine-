// Cypress support file for E2E tests
// Run before all tests

import './commands';

// Global configuration
Cypress.on('uncaught:exception', (err, runnable) => {
  // Ignore specific errors if needed
  if (err.message.includes('ResizeObserver loop')) {
    return false;
  }
  return true;
});

// Set default viewport
beforeEach(() => {
  cy.viewport(1280, 720);
});

// Add performance marks
afterEach(() => {
  cy.window().then(win => {
    console.log('Performance metrics:', {
      navigationStart: win.performance.timing.navigationStart,
      loadEventEnd: win.performance.timing.loadEventEnd,
      duration: win.performance.timing.loadEventEnd - win.performance.timing.navigationStart
    });
  });
});
