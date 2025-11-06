/**
 * JavaScript Unit Tests Configuration
 * Using Jest testing framework
 */

// Jest configuration
module.exports = {
  testEnvironment: 'jsdom',
  setupFilesAfterEnv: ['<rootDir>/tests/js/setup.js'],
  testMatch: ['<rootDir>/tests/js/**/*.test.js'],
  collectCoverageFrom: [
    'assets/js/**/*.js',
    '!assets/js/**/*.min.js'
  ],
  moduleNameMapping: {
    '^@/(.*)$': '<rootDir>/assets/js/$1'
  }
};