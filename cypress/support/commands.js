// Custom Cypress commands for PT24 testing

// Command: Login to WordPress
Cypress.Commands.add('login', (username = 'admin', password = 'password') => {
  cy.session([username, password], () => {
    cy.visit('/wp-login.php');
    cy.get('#user_login').type(username);
    cy.get('#user_pass').type(password);
    cy.get('#wp-submit').click();
    cy.url().should('include', '/wp-admin');
  });
});

// Command: Fill and submit lead form
Cypress.Commands.add('submitLeadForm', (data) => {
  cy.get('input[name="name"]').type(data.name || 'Test User');
  cy.get('input[name="email"]').type(data.email || 'test@example.com');
  cy.get('input[name="phone"]').type(data.phone || '123456789');
  
  if (data.description) {
    cy.get('textarea[name="description"]').type(data.description);
  }
  
  if (data.gdpr !== false) {
    cy.get('input[type="checkbox"][name="gdpr"]').check({ force: true });
  }
  
  cy.get('button[type="submit"]').click();
});

// Command: Check API response
Cypress.Commands.add('checkApiResponse', (endpoint, expectedKeys) => {
  cy.request('GET', `/wp-json/pt24/v1/${endpoint}`).then((response) => {
    expect(response.status).to.equal(200);
    expectedKeys.forEach(key => {
      expect(response.body).to.have.property(key);
    });
  });
});

// Command: Navigate to service
Cypress.Commands.add('navigateToService', (city, service) => {
  cy.visit(`/${city}/${service}/`);
  cy.url().should('include', `/${city}/${service}`);
});

// Command: Check CSS classes
Cypress.Commands.add('checkCssClasses', (classes) => {
  classes.forEach(className => {
    cy.get(`.${className}`).should('exist');
  });
});

// Command: Scroll element into view
Cypress.Commands.add('scrollToElement', (selector) => {
  cy.get(selector).scrollIntoView().should('be.visible');
});

// Command: Wait for network idle
Cypress.Commands.add('waitForNetworkIdle', (timeout = 5000) => {
  cy.intercept('**/*').as('requests');
  cy.get('body').then(() => {
    cy.wait('@requests', { timeout: timeout }).catch(() => {
      // Network idle achieved
    });
  });
});

// Command: Check performance metric
Cypress.Commands.add('checkPerformance', (metric, maxTime = 3000) => {
  cy.window().then(win => {
    const perf = win.performance.timing;
    const duration = perf.loadEventEnd - perf.navigationStart;
    expect(duration).to.be.lessThan(maxTime);
  });
});

// Command: Check SEO meta tags
Cypress.Commands.add('checkSeoTags', (title, description) => {
  cy.title().should('include', title);
  cy.get('meta[name="description"]').should('have.attr', 'content').and('include', description);
  cy.get('link[rel="canonical"]').should('exist');
});

// Command: Check schema markup
Cypress.Commands.add('checkSchema', (schemaType) => {
  cy.get('script[type="application/ld+json"]').then(($scripts) => {
    let found = false;
    $scripts.each((index, script) => {
      if (script.textContent.includes(`"@type":"${schemaType}"`)) {
        found = true;
      }
    });
    expect(found).to.be.true;
  });
});

// Command: Test accessibility
Cypress.Commands.add('checkAccessibility', () => {
  // Check headings
  cy.get('h1').should('have.length', 1);
  cy.get('h2').should('have.length.greaterThan', 0);
  
  // Check alt text on images
  cy.get('img').each(($img) => {
    cy.wrap($img).should('have.attr', 'alt');
  });
  
  // Check form labels
  cy.get('input, textarea, select').each(($field) => {
    if ($field.attr('type') !== 'hidden' && !$field.attr('aria-label')) {
      cy.wrap($field).parent().should('contain', 'label');
    }
  });
});
