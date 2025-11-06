/**
 * Jest Setup File
 * Global test configuration and mocks
 */

// Import Jest DOM matchers
import '@testing-library/jest-dom';

// Mock jQuery
global.$ = global.jQuery = jest.fn(() => ({
  ready: jest.fn(),
  on: jest.fn(),
  off: jest.fn(),
  trigger: jest.fn(),
  val: jest.fn(),
  attr: jest.fn(),
  prop: jest.fn(),
  addClass: jest.fn(),
  removeClass: jest.fn(),
  hasClass: jest.fn(),
  show: jest.fn(),
  hide: jest.fn(),
  fadeIn: jest.fn(),
  fadeOut: jest.fn(),
  slideDown: jest.fn(),
  slideUp: jest.fn(),
  find: jest.fn(() => global.$()),
  closest: jest.fn(() => global.$()),
  parent: jest.fn(() => global.$()),
  children: jest.fn(() => global.$()),
  siblings: jest.fn(() => global.$()),
  append: jest.fn(),
  prepend: jest.fn(),
  html: jest.fn(),
  text: jest.fn(),
  remove: jest.fn(),
  clone: jest.fn(() => global.$()),
  serialize: jest.fn(),
  serializeArray: jest.fn(),
  ajax: jest.fn(),
  get: jest.fn(),
  post: jest.fn(),
  each: jest.fn()
}));

// Mock WordPress AJAX object
global.vcc_ajax = {
  ajax_url: '/wp-admin/admin-ajax.php',
  nonce: 'test_nonce_123'
};

global.vcc_admin_ajax = {
  ajax_url: '/wp-admin/admin-ajax.php',
  nonce: 'test_admin_nonce_123'
};

// Mock WordPress functions
global.wp = {
  i18n: {
    __: jest.fn((text) => text),
    _x: jest.fn((text) => text),
    _n: jest.fn((single, plural, number) => number === 1 ? single : plural)
  }
};

// Mock console methods for cleaner test output
global.console = {
  ...console,
  log: jest.fn(),
  warn: jest.fn(),
  error: jest.fn(),
  info: jest.fn(),
  debug: jest.fn()
};

// Mock window.location
delete window.location;
window.location = {
  href: 'http://localhost',
  origin: 'http://localhost',
  protocol: 'http:',
  host: 'localhost',
  hostname: 'localhost',
  port: '',
  pathname: '/',
  search: '',
  hash: '',
  reload: jest.fn(),
  assign: jest.fn(),
  replace: jest.fn()
};

// Mock localStorage
const localStorageMock = {
  getItem: jest.fn(),
  setItem: jest.fn(),
  removeItem: jest.fn(),
  clear: jest.fn(),
  length: 0,
  key: jest.fn()
};
global.localStorage = localStorageMock;

// Mock sessionStorage
const sessionStorageMock = {
  getItem: jest.fn(),
  setItem: jest.fn(),
  removeItem: jest.fn(),
  clear: jest.fn(),
  length: 0,
  key: jest.fn()
};
global.sessionStorage = sessionStorageMock;

// Mock fetch API
global.fetch = jest.fn(() =>
  Promise.resolve({
    ok: true,
    status: 200,
    json: () => Promise.resolve({}),
    text: () => Promise.resolve(''),
    blob: () => Promise.resolve(new Blob()),
    arrayBuffer: () => Promise.resolve(new ArrayBuffer(0))
  })
);

// Helper function to create DOM elements for testing
global.createTestElement = (tag = 'div', attributes = {}) => {
  const element = document.createElement(tag);
  Object.keys(attributes).forEach(key => {
    if (key === 'class') {
      element.className = attributes[key];
    } else if (key === 'style') {
      element.style.cssText = attributes[key];
    } else {
      element.setAttribute(key, attributes[key]);
    }
  });
  return element;
};

// Helper function to create form elements
global.createTestForm = () => {
  const form = document.createElement('form');
  form.className = 'vcc-contact-form';
  form.innerHTML = `
    <input type="text" name="vcc_full_name" id="vcc_full_name" required>
    <input type="email" name="vcc_email" id="vcc_email" required>
    <input type="tel" name="vcc_phone" id="vcc_phone" required>
    <input type="checkbox" name="vcc_consent" id="vcc_consent" required>
    <button type="submit">Submit</button>
  `;
  return form;
};

// Helper function to simulate events
global.simulateEvent = (element, eventType, eventData = {}) => {
  const event = new Event(eventType, { bubbles: true, cancelable: true });
  Object.keys(eventData).forEach(key => {
    event[key] = eventData[key];
  });
  element.dispatchEvent(event);
  return event;
};

// Helper function to wait for async operations
global.waitFor = (callback, timeout = 1000) => {
  return new Promise((resolve, reject) => {
    const startTime = Date.now();
    const check = () => {
      try {
        const result = callback();
        if (result) {
          resolve(result);
        } else if (Date.now() - startTime > timeout) {
          reject(new Error('Timeout waiting for condition'));
        } else {
          setTimeout(check, 10);
        }
      } catch (error) {
        if (Date.now() - startTime > timeout) {
          reject(error);
        } else {
          setTimeout(check, 10);
        }
      }
    };
    check();
  });
};

// Setup DOM
document.body.innerHTML = '';

// Reset mocks before each test
beforeEach(() => {
  // Clear all mocks
  jest.clearAllMocks();
  
  // Reset DOM
  document.body.innerHTML = '';
  
  // Reset localStorage and sessionStorage
  localStorageMock.getItem.mockClear();
  localStorageMock.setItem.mockClear();
  localStorageMock.removeItem.mockClear();
  localStorageMock.clear.mockClear();
  
  sessionStorageMock.getItem.mockClear();
  sessionStorageMock.setItem.mockClear();
  sessionStorageMock.removeItem.mockClear();
  sessionStorageMock.clear.mockClear();
  
  // Reset fetch mock
  global.fetch.mockClear();
});

// Cleanup after each test
afterEach(() => {
  // Clean up any timers
  jest.clearAllTimers();
  
  // Clean up any pending promises
  jest.runOnlyPendingTimers();
});