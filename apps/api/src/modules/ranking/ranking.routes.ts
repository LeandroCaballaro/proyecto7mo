import { Router } from 'express';
import { rankingController as c } from './ranking.controller';

export const rankingRouter = Router();

rankingRouter.get('/', c.index);

