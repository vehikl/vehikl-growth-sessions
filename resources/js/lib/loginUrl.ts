import { route } from 'ziggy-js';

/** The slice of `window.location` this helper reads, so tests can pass a fake. */
export interface LoginUrlLocation {
    pathname: string;
    search: string;
}

/**
 * The OAuth entry point, carrying the page the visitor is on so they come back to it once logged in. That matters
 * most for an invited guest, whose open session drawer would otherwise be lost on the round trip.
 */
export function loginUrl(driver = 'github', location: LoginUrlLocation = window.location): string {
    return route('oauth.login.redirect', { driver, redirect: `${location.pathname}${location.search}` });
}
