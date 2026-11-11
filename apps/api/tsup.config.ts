import { defineConfig } from 'tsup';

export default defineConfig({
  entry: ['src/server.ts'],
  format: ['esm'],
  target: 'node20',
  platform: 'node',
  outDir: 'dist',
  clean: true,
  sourcemap: true,
  // El paquete compartido se publica como TS fuente: lo incluimos en el bundle.
  noExternal: ['@nexohub/shared'],
});

