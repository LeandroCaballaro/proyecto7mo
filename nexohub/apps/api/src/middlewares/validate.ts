import type { NextFunction, Request, Response } from 'express';
import type { ZodType } from 'zod';
import type { ApiFieldIssue } from '@nexohub/shared';
import { AppError } from '../utils/AppError';

interface Schemas {
  body?: ZodType;
  query?: ZodType;
  params?: ZodType;
}

/**
 * Valida y normaliza body/query/params con Zod. Los valores parseados (trim, coerción,
 * defaults) reemplazan a los originales, así los controllers reciben datos limpios.
 */
export function validate(schemas: Schemas) {
  return (req: Request, _res: Response, next: NextFunction): void => {
    const issues: ApiFieldIssue[] = [];

    for (const key of ['params', 'query', 'body'] as const) {
      const schema = schemas[key];
      if (!schema) continue;

      const result = schema.safeParse(req[key] ?? {});
      if (!result.success) {
        for (const issue of result.error.issues) {
          issues.push({ path: [key, ...issue.path].join('.'), message: issue.message });
        }
        continue;
      }

      if (key === 'query') {
        // Express 5: `req.query` es un getter; lo sombreamos con una propiedad propia.
        Object.defineProperty(req, 'query', { value: result.data, writable: true, configurable: true });
      } else {
        req[key] = result.data as never;
      }
    }

    if (issues.length > 0) throw AppError.validation(issues);
    next();
  };
}

