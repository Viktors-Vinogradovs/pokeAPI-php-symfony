PROJECT CONTEXT — Symfony + Twig “Pokemon Finder” (PokeAPI)

Goal
Build a small web app in Symfony (Controller + Service architecture) using Twig templates that lets users browse Pokémon, search by name, filter by one or multiple types, view Pokémon details, and manage a favorites list that persists between page refreshes. App must run via Docker Compose and be delivered as a GitHub repo.

Tech / Constraints
- Symfony (MVC style): Controllers must be thin; logic goes into Services.
- Twig templates (server-rendered UI, no SPA).
- PokeAPI v2 as the only external data source.
- Docker Compose required for running the app.
- Favor “working demo” over perfect features. Keep code clean and readable.

Required Features
1) Pokémon list page
- Show a table/grid of Pokémon.
- Search by name (substring match is fine).
- Filter by Pokémon type(s): user can select multiple types at once.
- Results link to a Pokémon details page.
- Add basic pagination or prev/next to avoid rendering huge lists.

2) Pokémon details page
- Route like /pokemon/{name}
- Show at least: name, sprite, types, stats, abilities.
- “Add to favorites / remove from favorites” action.

3) Favorites
- Favorites persist between page refreshes.
- No authentication required.
- MVP persistence can be server session (cookie-based) so it survives refresh.
- Include a /favorites page listing saved Pokémon with remove buttons.

Bonus (optional)
- Store favorites in PostgreSQL started by Docker Compose.
- No auth: identify user via anonymous token cookie.
- Responsive design.

Architecture Guidance
- Service: src/Service/PokeApiClient.php (or similar) that wraps all PokeAPI calls.
- Use Symfony HttpClient for requests.
- Use Symfony Cache for caching:
  - types list
  - type -> pokemon list
  - pokemon details
  This avoids slow UX and protects against rate limits.
- Controllers:
  - PokemonController: list + details
  - FavoritesController: list + toggle
- Templates:
  - base.html.twig (layout)
  - pokemon/index.html.twig (list)
  - pokemon/show.html.twig (details)
  - favorites/index.html.twig (favorites list)

Filtering Strategy (important)
PokeAPI doesn’t support “multi-type filter + name search” in one query.
Approach:
- If no types selected: base list can come from one source (e.g., pokemon?limit=... or cached dataset), but keep it simple.
- If one type selected: call /type/{type} → get pokemon names for that type.
- If multiple types selected: call /type/{type} for each, intersect the name sets.
- Apply name substring search on the resulting name list.
- For displaying results, fetch details only for the current page and rely on caching.

Definition of Done
- `docker compose up` starts the app and it’s usable in browser.
- List page: search + multi-type filter works.
- Details page works.
- Favorites persist across refresh (session-based MVP).
- Code is organized, readable, and “Symfony style” (thin controllers, services handle logic).
- README has setup + run instructions + brief feature notes.

Rules for AI edits
- Don’t introduce frontend frameworks or JS-heavy solutions unless necessary.
- Prefer simple Twig forms with GET params for filters.
- Favor predictable URLs and query params:
  - /pokemon?q=pika&types[]=fire&types[]=flying&page=1
- Use CSRF protection for POST actions (favorites toggle).
- Keep changes incremental and test after each sprint.
