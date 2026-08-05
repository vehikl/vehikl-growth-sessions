import { RouteParams, Router } from 'ziggy-js';

declare global {
    function route(): Router;
    function route(name: string, params?: RouteParams<typeof name> | undefined, absolute?: boolean): string;
}
