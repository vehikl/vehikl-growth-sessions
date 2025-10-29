# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 12 application for managing Growth Sessions at Vehikl. It uses:
- **Backend**: Laravel 12 with Inertia.js for server-side rendering
- **Frontend**: Vue 3 (TypeScript) with Tailwind CSS v4
- **Testing**: PHPUnit for backend, Vitest for frontend
- **Key features**: Growth session scheduling, authentication via OAuth (Slack/Discord), commenting system, statistics dashboard

## Project History & Migration Context

**This application was migrated from Laravel 9 to Laravel 12.** Features were ported from the old codebase into a fresh Laravel 12 project. Key migration details:

- The original Laravel 9 app did NOT use Inertia.js
- Inertia integration was added during the migration and may have some rough edges
- Files/folders labeled "legacy" (e.g., `routes/legacy-web.php`, `resources/js/components/legacy/`) contain code that was ported with light modifications to work with the new setup
- **These "legacy" components are actively used** - the naming just indicates they were migrated rather than built fresh
- There may be lingering bugs, inconsistencies, or architectural imperfections from the migration
- **When working on this codebase, strive to improve code quality and adhere to Laravel 12 and modern Vue 3/TypeScript standards**

## Development Commands

**This project uses Laravel Sail for development.** All backend commands should be run through Sail.

### Initial Setup
```bash
# Install dependencies
composer install
pnpm install

# Start Docker containers
./vendor/bin/sail up -d

# Configure environment and run migrations
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
```

### Development Server
```bash
# Start Docker containers (MySQL, Laravel app)
./vendor/bin/sail up       # Foreground (shows logs)
./vendor/bin/sail up -d    # Background (detached)

# Stop containers
./vendor/bin/sail down

# Or start services individually:
pnpm dev               # Vite dev server
./vendor/bin/sail artisan queue:listen   # Queue worker
./vendor/bin/sail artisan pail           # View logs
```

### Testing
```bash
# Backend tests (via Sail)
./vendor/bin/sail artisan test                    # Run all PHPUnit tests
./vendor/bin/sail artisan test --filter TestName # Run specific test

# Frontend tests (runs on host machine)
pnpm test                              # Run Vitest tests
pnpm test -- --watch                   # Watch mode
pnpm test -- path/to/test.spec.ts     # Run specific test file
```

### Code Quality
```bash
# Linting and formatting (runs on host machine)
pnpm lint                              # ESLint with auto-fix
pnpm format                            # Prettier format
pnpm format:check                      # Check formatting only

# PHP formatting (via Sail)
./vendor/bin/sail bin pint             # PHP CS Fixer (Laravel Pint)
```

### Building for Production
```bash
pnpm build                 # Build frontend assets
pnpm build:ssr             # Build with SSR support
composer prod              # Alias for pnpm build
```

### Common Sail Commands
```bash
./vendor/bin/sail artisan ...          # Run any artisan command
./vendor/bin/sail composer ...         # Run composer commands
./vendor/bin/sail npm ...              # Run npm commands (prefer pnpm on host)
./vendor/bin/sail mysql                # Access MySQL CLI
./vendor/bin/sail shell                # Access container shell
```

## Architecture

### Backend Structure

**Route Organization**: Routes are split across multiple files:
- `routes/web.php` - Main entry point with basic routes
- `routes/legacy-web.php` - Growth session routes and API endpoints
- `routes/auth.php` - Authentication routes
- `routes/settings.php` - Settings routes

**Key Domains**:
- **GrowthSession**: Core domain with models, controllers, policies for scheduling/managing sessions
- **Comments**: Commenting system for growth sessions
- **Statistics**: Analytics dashboard (restricted to Vehikl users via middleware)
- **OAuth**: Authentication via Slack and Discord using Laravel Socialite

**Middleware**: `AuthenticateSlackApp` middleware protects growth session viewing routes

### Frontend Structure

**Inertia Pages**: Located in `resources/js/pages/`
- All pages automatically use `AppLayout.vue` as default layout
- Pages are resolved dynamically via Inertia's page resolver

**Component Organization**:
- `resources/js/components/legacy/` - Growth session components (WeekView, GrowthSessionCard, GrowthSessionForm, etc.)
- `resources/js/classes/` - TypeScript domain classes (GrowthSession, User, DateTime, WeekGrowthSessions)
- `resources/js/services/` - API service layer (GrowthSessionApi, DiscordChannelApi, etc.)
- `resources/js/composables/` - Vue composables (useAppearance for theme management)

**Type Safety**: Full TypeScript setup with strict type checking. Types in `resources/js/types/`

**Path Aliases**:
- `@/` resolves to `resources/js/`
- `ziggy-js` for Laravel route helper in frontend

### Testing Architecture

**Backend Tests**:
- Feature tests in `tests/Feature/`
- Unit tests in `tests/Unit/`
- Test fixtures in `tests/fixtures/`
- Uses separate MySQL test database container (`mysql_test`)

**Frontend Tests**:
- Vitest with happy-dom environment
- Custom matchers defined in `setup-vitest.ts`: `toBeVisible()`, `toBeChecked()`
- Tests colocated with components (`.spec.ts` files)
- Timezone set to `America/Toronto` for test consistency

### State Management

No global state management library. Uses:
- Inertia shared data for global state
- Vue composables for reusable stateful logic
- Props/events for component communication

### API Layer

API services in `resources/js/services/` follow consistent patterns:
- Extend `BaseApi` class
- Use Axios for HTTP requests
- Return typed responses matching TypeScript domain classes
- Comprehensive test coverage for each API service

## Code Style

**Prettier Configuration**:
- 4-space indentation (2 for YAML)
- Single quotes
- Semicolons required
- Print width: 150 characters
- Auto-import organization
- Tailwind CSS class sorting

**ESLint**: Vue 3 + TypeScript ESLint configuration with Prettier integration

## Important Notes

- **Authentication**: OAuth-based only (Slack/Discord). No traditional email/password auth.
- **Docker Test Database**: PHPUnit configured to use `mysql_test` container for isolated test environment.
- **Ziggy Routes**: Laravel routes available in Vue via Ziggy. Import from `ziggy-js`.
- **Theme System**: Light/dark mode managed via `useAppearance` composable, initialized on page load.
- **Refactoring Welcome**: Given the migration context, improvements to code quality, better TypeScript types, cleaner Inertia patterns, and Laravel 12 best practices are encouraged.
