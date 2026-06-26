// Cypress E2E Tests for PT24
// Run: npx cypress run

describe('PT24 Complete User Flow', () => {
  const baseUrl = 'https://pt24.pro';

  beforeEach(() => {
    cy.clearCookies();
  });

  describe('Homepage', () => {
    it('should load homepage with all CSS classes', () => {
      cy.visit(baseUrl);
      cy.title().should('include', 'PT24.PRO');
      
      // Check key CSS classes present
      cy.get('.pt24-hero').should('exist');
      cy.get('.pt24-home__cat-grid').should('exist');
      cy.get('.pt24-home__stats-bar').should('exist');
      cy.get('.pt24-home__reviews').should('exist');
      
      // Check buttons
      cy.get('.pt24-btn--primary').should('be.visible');
      cy.get('.pt24-btn--cta').should('be.visible');
    });

    it('should have proper SEO meta tags', () => {
      cy.visit(baseUrl);
      cy.get('meta[name="description"]').should('have.attr', 'content').and('include', 'sprawdzonego fachowca');
      cy.get('link[rel="canonical"]').should('have.attr', 'href', `${baseUrl}/`);
      cy.get('meta[property="og:title"]').should('exist');
      cy.get('meta[property="og:image"]').should('exist');
    });

    it('should have breadcrumbs schema', () => {
      cy.visit(baseUrl);
      cy.get('script[type="application/ld+json"]').then(($scripts) => {
        let hasSchema = false;
        $scripts.each((index, script) => {
          if (script.textContent.includes('BreadcrumbList')) {
            hasSchema = true;
          }
        });
        expect(hasSchema).to.be.true;
      });
    });
  });

  describe('Service Selection Flow', () => {
    it('should navigate to landing page from homepage', () => {
      cy.visit(baseUrl);
      
      // Find and click hydraulik link
      cy.contains('Hydraulik w Warszawie').click();
      cy.url().should('include', 'warszawa/hydraulik');
      cy.title().should('include', 'Hydraulik');
    });

    it('should display landing page with firms list', () => {
      cy.visit(`${baseUrl}/warszawa/hydraulik/`);
      
      // Check page structure
      cy.get('.pt24-landing').should('exist');
      cy.get('.pt24-firms').should('exist');
      cy.get('.pt24-firm').should('have.length.greaterThan', 0);
      
      // Check firm card elements
      cy.get('.pt24-firm__name').first().should('be.visible');
      cy.get('.pt24-firm__meta').first().should('be.visible');
    });

    it('should display ranking page correctly', () => {
      cy.visit(`${baseUrl}/ranking/warszawa/hydraulik/`);
      
      // Check ranking-specific elements
      cy.get('.pt24-ranking').should('exist');
      cy.contains('Ranking').should('be.visible');
      
      // Check breadcrumbs for ranking
      cy.get('.breadcrumb-list').should('include.text', 'Rankingi');
    });
  });

  describe('City Hub Pages', () => {
    it('should display city hub page', () => {
      cy.visit(`${baseUrl}/miasto/warszawa/`);
      
      cy.get('.pt24-city-hub').should('exist');
      cy.contains('Warszawa').should('be.visible');
      
      // Check city-specific content
      cy.get('.pt24-services-grid').should('exist');
      cy.get('.pt24-services-grid .pt24-service-card').should('have.length.greaterThan', 0);
    });

    it('should display services hub page', () => {
      cy.visit(`${baseUrl}/uslugi/`);
      
      cy.get('.pt24-services-hub').should('exist');
      cy.contains('Wszystkie usługi').should('be.visible');
      
      // Check services listing
      cy.get('.pt24-service-item').should('have.length', 10);
    });
  });

  describe('Lead Submission Form', () => {
    it('should fill and submit lead form successfully', () => {
      cy.visit(`${baseUrl}/warszawa/hydraulik/`);
      
      // Scroll to form
      cy.get('.pt24-leadform').scrollIntoView();
      
      // Fill form fields
      cy.get('input[name="name"]').type('Jan Kowalski');
      cy.get('input[name="email"]').type('jan@example.com');
      cy.get('input[name="phone"]').type('123456789');
      cy.get('textarea[name="description"]').type('Przecieka kran w łazience');
      
      // Check GDPR checkbox if present
      cy.get('input[type="checkbox"][name="gdpr"]').then(($checkbox) => {
        if ($checkbox.length > 0) {
          cy.get('input[type="checkbox"][name="gdpr"]').check();
        }
      });
      
      // Submit form
      cy.get('button[type="submit"]').contains(/Wyślij|Submit/).click();
      
      // Check success message
      cy.get('.pt24-leadform__ok').should('be.visible');
      cy.contains('Zapytanie wysłane').should('be.visible');
    });

    it('should validate required fields', () => {
      cy.visit(`${baseUrl}/warszawa/hydraulik/`);
      
      // Try submitting empty form
      cy.get('.pt24-leadform').scrollIntoView();
      cy.get('button[type="submit"]').contains(/Wyślij|Submit/).click();
      
      // Should show validation errors or prevent submission
      cy.get('input[name="name"]').should('have.attr', 'required');
      cy.get('input[name="email"]').should('have.attr', 'required');
    });

    it('should show error message on failed submission', () => {
      cy.visit(`${baseUrl}/warszawa/hydraulik/`);
      
      // Fill with invalid email
      cy.get('.pt24-leadform').scrollIntoView();
      cy.get('input[name="name"]').type('Test');
      cy.get('input[name="email"]').type('not-an-email');
      cy.get('input[name="phone"]').type('123456789');
      
      cy.get('button[type="submit"]').contains(/Wyślij|Submit/).click();
      
      // Should show error (either client-side or server-side)
      cy.get('.pt24-leadform__err').should('exist');
    });
  });

  describe('Blog Articles', () => {
    it('should display blog article with proper styling', () => {
      // Assuming there's at least one blog post
      cy.visit(`${baseUrl}/blog/`);
      cy.get('article a').first().click();
      
      // Check blog article structure
      cy.get('.pt24-blog-article').should('exist');
      cy.get('.pt24-blog-meta').should('exist');
      cy.get('.pt24-blog-body').should('exist');
      
      // Check styling is applied
      cy.get('article h1').should('be.visible');
      cy.get('article p').should('be.visible');
    });
  });

  describe('Navigation & Interactive Elements', () => {
    it('should have working header navigation', () => {
      cy.visit(baseUrl);
      
      // Check header exists
      cy.get('header').should('exist');
      
      // Check logo link
      cy.get('header a[href="/"]').should('exist');
      
      // Check main nav items
      cy.get('nav').should('exist');
    });

    it('should have working scroll-to-top button', () => {
      cy.visit(baseUrl);
      
      // Scroll down
      cy.scrollTo('bottom');
      
      // Scroll-to-top button should be visible
      cy.get('.pt24-scroll-top').should('be.visible');
      
      // Click and scroll back to top
      cy.get('.pt24-scroll-top').click();
      cy.window().its('scrollY').should('equal', 0);
    });

    it('should have sticky CTA button', () => {
      cy.visit(`${baseUrl}/warszawa/hydraulik/`);
      
      // Scroll down
      cy.scrollTo('bottom');
      
      // Sticky CTA should be visible
      cy.get('.pt24-sticky-cta').should('be.visible');
      
      // Should contain button
      cy.get('.pt24-sticky-cta .pt24-btn').should('exist');
    });
  });

  describe('Search/Filter Functionality', () => {
    it('should filter firms on landing page', () => {
      cy.visit(`${baseUrl}/warszawa/hydraulik/`);
      
      // Check if filter controls exist
      cy.get('.pt24-filters').then(($filters) => {
        if ($filters.length > 0) {
          // If filters present, test filtering
          cy.get('[data-filter-rating]').click();
          cy.get('.pt24-firm').should('have.length.greaterThan', 0);
        }
      });
    });

    it('should have working search page', () => {
      cy.visit(`${baseUrl}/szukaj/`);
      
      cy.get('input[name="s"]').type('hydraulik');
      cy.get('button[type="submit"]').click();
      
      // Should show search results
      cy.url().should('include', 's=hydraulik');
      cy.get('.search-results').should('exist');
    });
  });

  describe('API Endpoints', () => {
    it('should access businesses API endpoint', () => {
      cy.request('GET', `${baseUrl}/wp-json/pt24/v1/businesses`)
        .should((response) => {
          expect(response.status).to.equal(200);
          expect(response.body).to.have.property('businesses');
          expect(response.body.businesses).to.be.an('array');
        });
    });

    it('should access specific business endpoint', () => {
      cy.request('GET', `${baseUrl}/wp-json/pt24/v1/businesses`)
        .then((response) => {
          if (response.body.businesses.length > 0) {
            const businessId = response.body.businesses[0].id;
            
            cy.request('GET', `${baseUrl}/wp-json/pt24/v1/businesses/${businessId}`)
              .should((response) => {
                expect(response.status).to.equal(200);
                expect(response.body).to.have.property('name');
                expect(response.body).to.have.property('city');
              });
          }
        });
    });

    it('should submit lead via API', () => {
      cy.request({
        method: 'POST',
        url: `${baseUrl}/wp-json/pt24/v1/leads/submit`,
        body: {
          name: 'Test Lead',
          email: 'test@example.com',
          phone: '123456789',
          service: 'hydraulik',
          city: 'warszawa',
          description: 'Test description'
        },
        headers: {
          'Content-Type': 'application/json'
        }
      }).should((response) => {
        expect(response.status).to.be.oneOf([201, 200]);
        expect(response.body).to.have.property('status');
      });
    });
  });

  describe('Responsive Design', () => {
    it('should be responsive on mobile', () => {
      cy.viewport('iphone-x');
      cy.visit(baseUrl);
      
      // Mobile elements should be visible
      cy.get('.pt24-hero').should('be.visible');
      cy.get('.pt24-home__cat-grid').should('be.visible');
    });

    it('should be responsive on tablet', () => {
      cy.viewport('ipad-2');
      cy.visit(baseUrl);
      
      // Tablet layout
      cy.get('.pt24-hero').should('be.visible');
      cy.get('.pt24-home__stats-bar').should('be.visible');
    });

    it('should be responsive on desktop', () => {
      cy.viewport(1920, 1080);
      cy.visit(baseUrl);
      
      // Desktop layout
      cy.get('.pt24-hero').should('be.visible');
      cy.get('.pt24-home__reviews').should('be.visible');
    });
  });

  describe('Performance', () => {
    it('should load homepage in reasonable time', () => {
      const startTime = Date.now();
      cy.visit(baseUrl);
      cy.get('.pt24-hero').should('be.visible').then(() => {
        const loadTime = Date.now() - startTime;
        expect(loadTime).to.be.lessThan(5000); // 5 seconds
      });
    });

    it('should lazy-load images', () => {
      cy.visit(`${baseUrl}/warszawa/hydraulik/`);
      
      // Check for lazy-load attributes
      cy.get('img[loading="lazy"]').should('have.length.greaterThan', 0);
    });
  });

  describe('Accessibility', () => {
    it('should have proper heading hierarchy', () => {
      cy.visit(baseUrl);
      
      // Check h1 exists (should be only one)
      cy.get('h1').should('have.length', 1);
      
      // Check headings are in order
      cy.get('h2').should('have.length.greaterThan', 0);
    });

    it('should have proper focus indicators', () => {
      cy.visit(baseUrl);
      
      // Tab to first button
      cy.get('body').tab();
      cy.focused().should('have.css', 'outline');
    });

    it('should have alt text on images', () => {
      cy.visit(baseUrl);
      
      cy.get('img').each(($img) => {
        cy.wrap($img).should('have.attr', 'alt');
      });
    });
  });
});
