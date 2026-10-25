# Aurix

Weryfikacja głosu jako dodatkowa warstwa zabezpieczeń — aplikacja webowa (Symfony).

> **Status:** Faza 1 — aplikacja webowa (bez podłączenia silnika weryfikacji).
> Backend Python (modele ECAPA-TDNN + AASIST) dołączy w Fazie 2.

## Stack

| Element | Wersja |
|---|---|
| PHP | **8.5** |
| Symfony | **8.1** |
| Baza danych | MariaDB 11.4 |
| Serwer | nginx 1.29 + PHP-FPM |
| Docker Compose | tak |

## Struktura (warstwowa)

```
aurix/
├── compose.yaml              # orkiestracja: web + php + db + mailer
├── compose.override.yaml     # porty dev
├── .env                      # zmienne compose (porty, token)
├── .dockerignore
├── docker/
│   ├── Dockerfile            # multi-stage: base / dev / prod + nginx_prod
│   ├── nginx/default.conf
│   └── php/conf.d/           # app.ini, opcache.dev.ini, opcache.prod.ini
└── symfony_app/              # root aplikacji Symfony
    ├── composer.json
    ├── bin/
    ├── config/
    ├── public/
    ├── src/
    │   ├── Controller/
    │   │   └── HomeController.php      # tymczasowy placeholder
    ├── templates/home/index.html.twig  # do wymiany na docelowy design
    ├── tests/
    └── var/ vendor/          # nie commitowane
```

## Szybki start

```bash
# 1. Zbuduj i uruchom
docker compose up -d --build

# 2. Zależności (jeśli budujesz od zera)
docker compose exec php composer install

# 3. Migracje
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction

# 4. Otwórz
#    http://localhost:8000
#    Mailpit: http://localhost:8025
```

Zatrzymanie:

```bash
docker compose down          # zachowuje dane
docker compose down -v       # usuwa też wolumen bazy
```

## Architektura integracji (Faza 2)

```
przeglądarka → nginx → Symfony → HTTP (sieć wewnętrzna) → Python (FastAPI)
```

- Python **nie jest wystawiony publicznie**.
- Symfony jest jedynym punktem wejścia (auth, walidacja, audyt).
- Podłączenie silnika omówimy w Fazie 2 (osobny serwis + klient HTTP).

### Compose profile

Serwis `ml` jest przygotowany (zakomentowany) w `compose.yaml`:

```bash
docker compose up -d                  # Faza 1 — bez Pythona
docker compose --profile ml up -d     # Faza 2 — z Pythonem
```

## API (planowane)

Endpointy powstaną w kolejnym kroku, gdy frontend będzie ustalony:

| Metoda | Endpoint | Opis |
|---|---|---|
| `POST` | `/api/enroll` | rejestracja profilu głosowego |
| `POST` | `/api/verify` | weryfikacja próbki głosu |

Pole pliku: `audio` (multipart/form-data).

## Lint i testy

```bash
docker compose exec php php bin/console lint:container
docker compose exec php php bin/console lint:twig templates/
docker compose exec php php bin/console lint:yaml config/
```

Testy dopiszemy razem z docelowymi endpointami.

Lokalnie (bez Dockera, z hosta):

```bash
cd symfony_app
php bin/console lint:container
```

## Uwagi

- `FakeVoiceVerifier` zwraca **losowy** wynik — to celowe, żeby zbudować UI i flow bez modeli.
- Docelowo audio z przeglądarki (webm/opus) trzeba konwertować do 16 kHz mono WAV (`ffmpeg`) po stronie Pythona.
- Dokumentacja projektu: notatki w Obsidianie (`PBR - Architektura`, `PBR - MVP i plan realizacji`).
