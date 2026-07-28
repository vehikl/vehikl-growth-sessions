import 'vitest';

declare module 'vitest' {
    interface Assertion<T = any> {
        toBeVisible(): T;
        toBeChecked(): T;
    }
}
