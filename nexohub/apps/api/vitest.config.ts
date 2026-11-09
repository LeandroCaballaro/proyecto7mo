import { defineConfig } from 'vitest/config';

export default defineConfig({
  test: {
    environment: 'node',
    include: ['tests/**/*.test.ts'],
    setupFiles: ['tests/setup.ts'],
    // Los tests de integración comparten una BD: se ejecutan en serie.
    fileParallelism: false,
    testTimeout: 20_000,
  },
});

