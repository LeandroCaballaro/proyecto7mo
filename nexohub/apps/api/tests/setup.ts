// Se ejecuta antes de importar cada archivo de test: fija el entorno de pruebas.
process.env.NODE_ENV = 'test';
process.env.DATABASE_URL =
  process.env.TEST_DATABASE_URL ?? 'mysql://root:@127.0.0.1:3306/nexohub_test';
process.env.JWT_ACCESS_SECRET = 'test-secret-test-secret-test-secret-0123456789';
process.env.CLIENT_URLS = 'http://localhost:5173';
process.env.APP_URL = 'http://localhost:5173';

