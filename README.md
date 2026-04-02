# Track Manager

[![CI](https://img.shields.io/github/actions/workflow/status/dx-tooling/etfs-app-starter-kit/ci.yml?branch=main)](https://github.com/dx-tooling/etfs-app-starter-kit/actions/workflows/ci.yml)

A Symfony application for managing music tracks, checklists and the latest exported or uploaded audio files.


## Vision

The Track Manager is built on top of the ETFS starter architecture and focuses on:

- **Track management** with `trackNumber`, `beatName`, editable `title`, `bpm` and `musicalKey`
- **Checklist-driven progress** with derived status and progress values
- **Current audio file handling** for upload, replacement, in-app playback and export
- **Clean ETFS-style architecture** with verticals, facades, DTOs and presentation services


## Prerequisites

- macOS (Apple Silicon or Intel)
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) — configured with VirtioFS and Docker VMM for optimal performance
- [Mise](https://mise.jdx.dev/) — install via `brew install mise` or see [mise.jdx.dev](https://mise.jdx.dev/getting-started.html)


## Quick Start

```bash
# Enter the repository
cd etfs-app-starter-kit

# Trust mise configuration
mise trust

# Configure your project name
echo "ETFS_PROJECT_NAME=track-manager" > .env.local

# Bootstrap everything
mise run setup
```

The setup command will:
1. Check Docker Desktop performance settings
2. Build and start Docker containers
3. Install PHP dependencies via Composer
4. Install Node.js dependencies
5. Create and migrate the MariaDB database
6. Build frontend assets
7. Run quality checks and tests
8. Open the track manager in your browser


## Available Commands

All commands are run via Mise:

| Command | Description |
|---------|-------------|
| `mise run setup` | Bootstrap complete development environment |
| `mise run quality` | Run all quality tools (PHPStan, ESLint, Prettier, PHP-CS-Fixer) |
| `mise run tests` | Run all test suites |
| `mise run tests:frontend` | Run frontend tests (supports `--watch` and `--coverage`) |
| `mise run frontend` | Build frontend assets (Tailwind, TypeScript, AssetMapper) |
| `mise run console <cmd>` | Run Symfony console commands |
| `mise run composer <cmd>` | Run Composer commands |
| `mise run npm <cmd>` | Run npm commands |
| `mise run db` | Connect to the database |
| `mise run browser` | Open the application in your browser |
| `mise run in-app-container <cmd>` | Run any command inside the app container |


## Tech Stack

### Backend
- **PHP 8.4** with strict typing
- **Symfony 7.4** framework
- **Doctrine ORM** with MariaDB
- **PHPStan Level 10** for static analysis
- **Pest** for testing (with architecture tests)

### Frontend
- **TypeScript** for type-safe JavaScript
- **Stimulus.js** for modest, HTML-first interactivity
- **Tailwind CSS** for styling
- **Symfony AssetMapper** (no webpack/vite bundler)
- **Vitest** for frontend testing

### Infrastructure
- **Docker Compose** for local development
- **Mise** for tool management and task running
- **Nginx** as web server
- **MariaDB** as database


## Project Structure

```
├── .cursor/rules/      # AI-assisted development guidelines
├── .mise/tasks/        # Mise task definitions
├── assets/             # Frontend assets (TypeScript, CSS, Stimulus controllers)
├── config/             # Symfony configuration
├── docker/             # Docker configuration (Dockerfile, nginx, php.ini)
├── docs/               # Project documentation
│   ├── archbook.md     # Architecture documentation
│   ├── devbook.md      # Development guidelines
│   ├── frontendbook.md # Frontend development guide
│   └── ...
├── migrations/         # Doctrine migrations
├── src/                # Application source code (vertical slices)
├── tests/              # Test suites
│   ├── Application/    # End-to-end application tests
│   ├── Architecture/   # Architecture constraint tests
│   ├── Integration/    # Integration tests
│   ├── Unit/           # Unit tests
│   └── frontend/       # Frontend tests
└── public/             # Web root
```


## Configuration

### Environment Variables

The project uses Symfony's standard `.env` file hierarchy:

- `.env` — Default values (committed)
- `.env.local` — Local overrides (not committed)
- `.env.test` — Test environment defaults

Key variables:
- `ETFS_PROJECT_NAME` — Used for Docker container/volume naming (set in `.env.local` to avoid conflicts)
- `DATABASE_*` — Database connection settings
- `APP_ENV` — Application environment (`dev`, `test`, `prod`)


## Documentation

Detailed documentation is available in the `docs/` folder:

- **[archbook.md](docs/archbook.md)** — Architecture decisions and patterns
- **[devbook.md](docs/devbook.md)** — Development workflow and conventions
- **[frontendbook.md](docs/frontendbook.md)** — Frontend development guide (Stimulus, TypeScript)
- **[techbook.md](docs/techbook.md)** — Technical specifications
- **[runbook.md](docs/runbook.md)** — Operations and deployment


## AI-Assisted Development

The `.cursor/rules/` directory contains guidelines for AI-assisted development in Cursor IDE, covering:

- Architecture boundaries and vertical slices
- PHP code standards and type safety
- DTO patterns and data flow
- Frontend development with Stimulus
- Database and Doctrine conventions
- Development workflow


## Background

The application uses the ETFS starter kit as its technical foundation and keeps the ETFS-style structure for:

- `TrackManagement`
- `FileImport`
- `FileExport`

The underlying infrastructure still comes from:
- [etfs-shared-bundle](https://github.com/dx-tooling/etfs-shared-bundle)
- [etfs-webui-bundle](https://github.com/dx-tooling/etfs-webui-bundle)
