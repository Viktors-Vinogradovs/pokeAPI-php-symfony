# Pokemon Finder - Symfony + PokeAPI

A Symfony web application for browsing, searching, and managing favorite Pokemon using the PokeAPI v2.

## Project Status

**Sprint 0 Complete ✓** - Infrastructure and Docker setup ready

- ✅ Docker Compose configuration
- ✅ Symfony 8 skeleton with Twig templating
- ✅ Home page with basic styling
- ✅ Health check endpoint
- 🔄 Next: PokeAPI integration (Sprint 1)

## Tech Stack

- **PHP 8.4** with FrankenPHP runtime
- **Symfony 8.0** (MVC architecture)
- **Twig** for server-side rendering
- **Caddy** web server (via FrankenPHP)
- **Docker Compose** for containerization

## Prerequisites

- **Docker Desktop** for Windows installed and running
- **Git** for version control
- **Windows 11** with PowerShell

Verify Docker is running:
```powershell
docker --version
docker compose version
```

## Quick Start

### 1. Clone the Repository
```powershell
git clone <your-repo-url> pokeAPI-php-symfony
cd pokeAPI-php-symfony
```

### 2. Start the Application
```powershell
docker compose up --build
```

Wait for the message: **"FrankenPHP started 🐘"**

### 3. Access the Application

- **Home Page**: http://localhost:8000
- **Health Check**: http://localhost:8000/health

To stop the application, press `Ctrl+C` in the terminal.

## Common Commands

### Start/Stop/Restart

```powershell
# Start containers (detached mode)
docker compose up -d

# Start with rebuild
docker compose up --build -d

# Stop containers
docker compose down

# Restart containers
docker compose restart

# View container status
docker compose ps
```

### View Logs

```powershell
# All logs
docker compose logs

# Follow logs (live tail)
docker compose logs -f

# Logs for specific service only
docker compose logs -f php

# Last 50 lines
docker compose logs --tail=50 php
```

### Execute Commands Inside Container

```powershell
# Open shell in container
docker compose exec php sh

# Run Composer commands
docker compose exec php composer install
docker compose exec php composer require <package-name>
docker compose exec php composer update

# Run Symfony console commands
docker compose exec php bin/console cache:clear
docker compose exec php bin/console debug:router
docker compose exec php bin/console list

# Check Symfony version
docker compose exec php bin/console --version
```

### Cache Management

```powershell
# Clear Symfony cache
docker compose exec php bin/console cache:clear

# Warm up cache
docker compose exec php bin/console cache:warmup
```

## Project Structure

```
pokeAPI-php-symfony/
├── bin/
│   └── console                 # Symfony CLI
├── config/                     # Symfony configuration
├── frankenphp/
│   ├── Caddyfile              # Web server config
│   └── docker-entrypoint.sh   # Container startup script
├── public/
│   ├── index.php              # Application entry point
│   └── styles.css             # Basic CSS
├── src/
│   ├── Controller/            # Application controllers
│   │   ├── HomeController.php
│   │   └── HealthController.php
│   └── Kernel.php
├── templates/
│   ├── base.html.twig         # Base layout
│   └── home/
│       └── index.html.twig    # Home page template
├── var/                       # Cache, logs (auto-generated)
├── vendor/                    # Composer dependencies (ignored by git)
├── .env                       # Environment config
├── .gitignore
├── compose.yaml               # Docker Compose config
├── composer.json              # PHP dependencies
├── Dockerfile
└── README.md
```

## Troubleshooting

### Port 8000 Already in Use

If port 8000 is occupied by another application:

1. **Option A**: Stop the other application
2. **Option B**: Change the port in `.env`:
   ```
   HTTP_PORT=8080
   ```
   Then restart: `docker compose down && docker compose up -d`

### Container Won't Start

```powershell
# Check Docker Desktop is running
docker ps

# View error logs
docker compose logs php

# Force rebuild
docker compose down
docker compose up --build --force-recreate
```

### "Composer not found" or "PHP not found"

These tools run **inside the container**, not on your host machine. Always prefix with:
```powershell
docker compose exec php <command>
```

### Symfony Cache Issues

```powershell
# Clear cache and restart
docker compose exec php bin/console cache:clear
docker compose restart
```

### Permission Errors (Rare on Windows)

If you encounter permission issues with `var/` directory:
```powershell
docker compose exec php chmod -R 777 var/
```

## Available Routes

View all routes:
```powershell
docker compose exec php bin/console debug:router
```

Current routes:
- `GET /` - Home page (renders Twig template)
- `GET /health` - Health check (returns "OK")

## Development Workflow

### Adding a New Controller

1. Create controller file in `src/Controller/`:
   ```php
   <?php
   namespace App\Controller;

   use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
   use Symfony\Component\HttpFoundation\Response;
   use Symfony\Component\Routing\Attribute\Route;

   class MyController extends AbstractController
   {
       #[Route('/my-route', name: 'app_my_route')]
       public function index(): Response
       {
           return $this->render('my_template.html.twig');
       }
   }
   ```

2. Clear cache:
   ```powershell
   docker compose exec php bin/console cache:clear
   ```

3. Test the route: http://localhost:8000/my-route

### Installing Packages

```powershell
# Example: Install HTTP Client for PokeAPI
docker compose exec php composer require symfony/http-client

# Example: Install database support
docker compose exec php composer require symfony/orm-pack
```

## Environment Configuration

Edit `.env` file to configure:
- `HTTP_PORT` - Host port (default: 8000)
- `SERVER_NAME` - Server name (default: localhost)
- `APP_ENV` - Application environment (dev/prod)
- `APP_SECRET` - Application secret key

**Important**: Never commit `.env` with sensitive data. Use `.env.local` for local overrides (already gitignored).

## Git Workflow

```powershell
# Initialize repository (if not already done)
git init
git add .
git commit -m "Sprint 0: Initial Symfony + Docker setup"

# Push to remote
git remote add origin <your-repo-url>
git push -u origin main
```

## What's Ignored by Git

The `.gitignore` file excludes:
- `vendor/` - Composer dependencies (regenerated via `composer install`)
- `var/` - Cache and logs
- `.env.local` - Local environment overrides
- IDE files (`.idea/`, `.vscode/`)

## Next Steps (Sprint 1+)

- [ ] Install Symfony HTTP Client
- [ ] Create `PokeApiClient` service
- [ ] Implement Pokemon list page with search
- [ ] Add type filtering
- [ ] Create Pokemon details page
- [ ] Implement favorites functionality
- [ ] Add pagination

## Resources

- [Symfony Documentation](https://symfony.com/doc/current/index.html)
- [PokeAPI v2 Docs](https://pokeapi.co/docs/v2)
- [Twig Documentation](https://twig.symfony.com/)
- [Docker Compose Reference](https://docs.docker.com/compose/)

## Support

For issues related to:
- **Symfony**: Check the [Symfony documentation](https://symfony.com/doc)
- **Docker**: Ensure Docker Desktop is running and updated
- **Project setup**: Review this README and troubleshooting section

---

**Built with ❤️ using Symfony and PokeAPI**
