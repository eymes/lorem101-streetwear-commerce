# LOREM101 — Limited Streetwear Drop Store

Portfolio projekt: custom WordPress theme + custom plugin dla fikcyjnej marki
streetwear sprzedającej limitowane dropy ubrań. Budowany od zera, bez
page builderów (Elementor itp).

> Status: 🚧 w budowie — Faza 1 (fundament)

## Stack
- WordPress + WooCommerce (produkty zmienne: kolor/rozmiar)
- Custom theme (`theme/lorem101-theme`) — PHP, SCSS (BEM), JS, budowane przez Vite
- Custom plugin (`plugins/lorem101-drop-manager`) — zarządzanie limitowanymi dropami
- GSAP + Lenis (smooth scroll) + Swiper — planowane w Fazie 4
- ACF (darmowe) — dodatkowe pola produktu
- WordPress REST API — customowe endpointy statusu dropu
- Docker Compose — lokalne środowisko developerskie

## Struktura repo
```
lorem101-streetwear-commerce/
├── theme/lorem101-theme/          # custom motyw WordPress
├── plugins/lorem101-drop-manager/ # custom plugin
├── assets/screenshots/        # zrzuty ekranu do prezentacji projektu
├── docker-compose.yml
└── README.md
```

## Instalacja lokalna

Wymagania: Docker Desktop, Node.js 18+.

```bash
git clone <adres-repo>
cd lorem101-streetwear-commerce
cp .env.example .env
docker compose up -d
```

Poczekaj chwilę, wejdź na `http://localhost:8080` i przejdź kreator instalacji
WordPressa (nazwa strony, użytkownik admina, hasło).

Następnie zainstaluj i skonfiguruj WooCommerce oraz aktywuj nasz kod:

```bash
docker compose exec wpcli wp plugin install woocommerce --activate
docker compose exec wpcli wp theme activate lorem101-theme
docker compose exec wpcli wp plugin activate lorem101-drop-manager
```

Build frontendu:

```bash
cd theme/lorem101-theme
npm install
npm run dev     # tryb developerski z hot reload
# albo
npm run build   # build produkcyjny
```

## Funkcjonalności

_(sekcja rozbudowywana w miarę postępu prac)_

## Screenshoty

_(dodane po ukończeniu warstwy wizualnej)_
