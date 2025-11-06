/**
 * Jest Environment Setup
 * Loads environment variables for JavaScript tests
 */

// Load environment variables from .env file if available
const fs = require('fs');
const path = require('path');

const envPath = path.join(__dirname, '../../.env');

if (fs.existsSync(envPath)) {
    const envContent = fs.readFileSync(envPath, 'utf8');
    const envVars = {};
    
    envContent.split('\n').forEach(line => {
        // Skip comments and empty lines
        if (line.startsWith('#') || !line.trim()) {
            return;
        }
        
        // Parse key=value pairs
        const [key, ...valueParts] = line.split('=');
        if (key && valueParts.length > 0) {
            let value = valueParts.join('=').trim();
            
            // Remove quotes if present
            if ((value.startsWith('"') && value.endsWith('"')) || 
                (value.startsWith("'") && value.endsWith("'"))) {
                value = value.slice(1, -1);
            }
            
            // Convert boolean strings
            if (value === 'true') value = true;
            if (value === 'false') value = false;
            
            // Convert numeric strings
            if (!isNaN(value) && value !== '') {
                value = Number(value);
            }
            
            envVars[key.trim()] = value;
            process.env[key.trim()] = value;
        }
    });
    
    console.log('Environment variables loaded for Jest tests');
} else {
    console.log('No .env file found, using defaults for Jest tests');
}

// Set default test environment variables
const defaults = {
    VCC_ENVIRONMENT: 'testing',
    VCC_DEBUG: 'true',
    VCC_TEST_MODE: 'true',
    VCC_EMAIL_NOTIFICATIONS: 'false',
    VCC_RATE_LIMIT_ENABLED: 'false',
    VCC_AUTO_CLEANUP: 'false',
    VCC_TRACKING_ENABLED: 'false',
    VCC_ERROR_REPORTING: 'true',
    VCC_LOG_ERRORS: 'true',
    VCC_TEST_EMAIL: 'test@example.com',
    VCC_DEFAULT_ADMIN_EMAIL: 'admin@example.com',
    VCC_COVERAGE_THRESHOLD: '80'
};

// Apply defaults for variables not already set
Object.keys(defaults).forEach(key => {
    if (!process.env[key]) {
        process.env[key] = defaults[key];
    }
});

// Make environment variables available globally for tests
global.VCC_CONFIG = {
    environment: process.env.VCC_ENVIRONMENT,
    debug: process.env.VCC_DEBUG === 'true',
    testMode: process.env.VCC_TEST_MODE === 'true',
    emailNotifications: process.env.VCC_EMAIL_NOTIFICATIONS === 'true',
    rateLimitEnabled: process.env.VCC_RATE_LIMIT_ENABLED === 'true',
    autoCleanup: process.env.VCC_AUTO_CLEANUP === 'true',
    trackingEnabled: process.env.VCC_TRACKING_ENABLED === 'true',
    errorReporting: process.env.VCC_ERROR_REPORTING === 'true',
    logErrors: process.env.VCC_LOG_ERRORS === 'true',
    testEmail: process.env.VCC_TEST_EMAIL,
    defaultAdminEmail: process.env.VCC_DEFAULT_ADMIN_EMAIL,
    coverageThreshold: parseInt(process.env.VCC_COVERAGE_THRESHOLD) || 80
};

// Helper function to get configuration values in tests
global.getVCCConfig = function(key, defaultValue = null) {
    return global.VCC_CONFIG[key] !== undefined ? global.VCC_CONFIG[key] : defaultValue;
};

// Mock WordPress global functions commonly used in tests
global.wp = {
    ajax: {
        post: jest.fn(),
        get: jest.fn()
    },
    hooks: {
        addAction: jest.fn(),
        addFilter: jest.fn(),
        doAction: jest.fn(),
        applyFilters: jest.fn()
    }
};

global.jQuery = jest.fn(() => ({
    ready: jest.fn(),
    on: jest.fn(),
    off: jest.fn(),
    trigger: jest.fn(),
    find: jest.fn(),
    val: jest.fn(),
    serialize: jest.fn(),
    prop: jest.fn(),
    attr: jest.fn(),
    addClass: jest.fn(),
    removeClass: jest.fn(),
    hasClass: jest.fn(),
    show: jest.fn(),
    hide: jest.fn(),
    fadeIn: jest.fn(),
    fadeOut: jest.fn(),
    slideUp: jest.fn(),
    slideDown: jest.fn()
}));

global.$ = global.jQuery;

// Mock console methods if not in debug mode
if (!global.VCC_CONFIG.debug) {
    global.console.log = jest.fn();
    global.console.info = jest.fn();
    global.console.warn = jest.fn();
    global.console.error = jest.fn();
}