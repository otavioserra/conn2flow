import { defineConfig } from 'vitest/config';

export default defineConfig({
  test: {
    environment: 'happy-dom',
    globals: true,
    setupFiles: ['./tests/Unit/JS/setup.js'],
    include: ['tests/Unit/JS/**/*.test.js'],
    restoreMocks: true,
    clearMocks: true,
    pool: 'forks'
  }
});
