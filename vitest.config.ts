import path from 'node:path';
import { fileURLToPath } from 'node:url';
import react from '@vitejs/plugin-react';
import { defineConfig } from 'vitest/config';

const rootDirectory = path.dirname(fileURLToPath(import.meta.url));

export default defineConfig({
    plugins: [react()],
    resolve: {
        alias: {
            '@': path.resolve(rootDirectory, 'resources/js'),
        },
    },
    test: {
        environment: 'jsdom',
        setupFiles: ['resources/js/tests/setup.ts'],
        globals: true,
        include: ['resources/js/**/*.test.ts', 'resources/js/**/*.test.tsx'],
    },
});
