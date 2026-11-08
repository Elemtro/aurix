# Aurix

Weryfikacja głosu jako dodatkowa warstwa zabezpieczeń — aplikacja webowa (Symfony).

> **Status:** rejestracja, enrollment trzech fraz i logowanie dwuetapowe (hasło + głos).
> Silnik ML działa jako wewnętrzny serwis Python (`../aurix-ml`).

## Stack

| Element | Wersja |
|---|---|
| PHP | **8.5** |
| Symfony | **8.1** |
| Baza danych | MariaDB 11.4 (Doctrine ORM) |
| Serwer | nginx 1.29 + PHP-FPM |
| Frontend | Stimulus 3 + Tailwind CSS 4 (AssetMapper) |
| Docker Compose | tak |

## Flow

```
Rejestracja
  login + hasło + zgoda
  → fraza 1 (nagranie, sprawdzenie treści)
  → fraza 2
  → fraza 3
  → konto gotowe

Logowanie
  login + hasło
  → losowa fraza z trzech
  → nagranie
  → similarity + phrase_match (PAD diagnostycznie)
  → panel użytkownika
```

Frazy enrollmentu są **generowane per konto** przy rejestracji (3 słowa + 2 liczebniki),
zapisywane w `UserPhrase`, a nagrania w `VoiceSample`. Przy logowaniu losowana jest
jedna z trzech fraz użytkownika.

## Struktura

```
aurix/
├── compose.yaml              # web + php + db + ml (profil)
├── compose.override.yaml     # porty dev
├── .env                      # porty, VOICE_ENGINE, token ML
├── docker/
│   ├── Dockerfile
│   ├── nginx/default.conf
│   └── php/conf.d/
└── symfony_app/
    ├── assets/controllers/   # enrollment, voice-login, csrf
    ├── config/packages/      # security, doctrine, csrf, tailwind
    ├── migrations/
    ├── public/               # manifest PWA, ikona
    ├── src/
    │   ├── Controller/       # auth, enrollment, voice login, API
    │   ├── Entity/           # User, UserPhrase, VoiceSample
    │   ├── Form/             # RegistrationFormType
    │   ├── Repository/
    │   ├── Security/         # guard, szyfrowanie szablonów
    │   └── Voice/            # frazy, matcher, klient ML
    ├── templates/
    └── tests/
```

## Wymagania

- Docker + Docker Compose
- Git
- ok. 5 GB miejsca (obrazy + modele ML)
- wolne porty `8000` i `3307` (można zmienić w `.env`)

## Uruchomienie od zera

Repozytoria muszą leżeć **obok siebie** (`aurix` i `aurix-ml`):

```bash
mkdir -p ~/Code && cd ~/Code
git clone git@github.com:Elemtro/aurix.git
git clone git@github.com:Elemtro/aurix-ml.git
```

Web + baza:

```bash
cd ~/Code/aurix
docker compose up -d --build
docker compose exec php composer install
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec php php bin/console tailwind:build
```

ML (do demo potrzebne):

```bash
VOICE_ENGINE=http docker compose --profile ml up -d
docker compose restart web
```

Otwórz `http://localhost:8000`.

phpMyAdmin (tylko dev): `http://localhost:8081` — login `aurix` / `aurix`.

> Pierwsze nagranie pobiera modele (Whisper, Silero VAD, ECAPA, AASIST) do wolumenu `ml_models`.
> Kolejne są szybkie. PAD jest diagnostyczny (`PAD_ENFORCED=false`).

Zatrzymanie:

```bash
docker compose --profile ml down      # zachowuje dane
docker compose --profile ml down -v   # usuwa też wolumeny (baza + modele)
```

### Zmiana portów

W `.env`:

```dotenv
NGINX_HOST_PORT=8000
DB_HOST_PORT=3307
PMA_HOST_PORT=8081
```

### Demo bez ML

```bash
docker compose up -d
```

Działa rejestracja, enrollment i logowanie, ale silnik jest atrapą (`stub`) — nie porównuje prawdziwego głosu.

## Testowy przebieg (demo)

1. `http://localhost:8000` → przekieruje na `/login`.
2. **Zarejestruj nowe konto** (login, hasło, zgoda).
3. Nagraj trzy frazy wyświetlone na ekranie — są generowane dla Twojego konta.
4. Po trzeciej frazie zobaczysz panel użytkownika.
5. **Wyloguj się**.
6. Zaloguj się hasłem → pojawi się losowa fraza z trzech.
7. Nagraj ją → panel pokaże `similarity`, `phraseMatch` i PAD (diagnostycznie).

> Do każdego demo użyj **nowego loginu** (np. `demo1`, `demo2`). Stare konta zostają w bazie.

## Szybki start (powrót do projektu)

```bash
cd ~/Code/aurix
VOICE_ENGINE=http docker compose --profile ml up -d
docker compose restart web
```

## Frontend

- **Stimulus** — `assets/controllers/` (`enrollment_controller.js`, `voice_login_controller.js`).
- **Tailwind CSS 4** — `symfonycasts/tailwind-bundle`, bez Node.
- Bez własnego CSS — wyłącznie utility classes.

```bash
docker compose exec php php bin/console tailwind:build
docker compose exec php php bin/console tailwind:build --watch
```

## API

| Metoda | Endpoint | Opis |
|---|---|---|
| `POST` | `/api/enroll` | zapis próbki dla frazy (1–3) |
| `POST` | `/api/login/voice` | weryfikacja głosu po haśle |

Pole pliku: `audio` (multipart/form-data). Endpointy wymagają `X-CSRF-Token`.

`PhraseGenerator` i `WordList` generują trzy unikalne frazy przy rejestracji.

## Bezpieczeństwo i prywatność

- Hasła: `auto` (argon2id/bcrypt).
- CSRF: sesyjny, formularze + API.
- Logowanie dwuetapowe: hasło → głos; guard blokuje resztę tras do czasu weryfikacji.
- Próbki głosu: **szyfrowane** (`sodium_crypto_secretbox`, klucz z `APP_SECRET`).
- Brak publicznego dostępu do audio; brak logowania audio i transkryptów.
- Limit 5 prób głosu na logowanie.
- Docelowo: embeddingi zamiast audio (ISO/IEC 24745), dedykowany klucz szablonów.

## Mobile

UI jest mobile-first, jest manifest PWA. `getUserMedia` wymaga HTTPS, więc prezentacja na telefonie
wymaga VPS z Caddy (ważny certyfikat) albo tunelu HTTPS. Symfony za proxy: ustaw `TRUSTED_PROXIES`
i `X-Forwarded-Proto: https`, cookies `Secure`.

## Lint

```bash
docker compose exec php php bin/console lint:container
docker compose exec php php bin/console lint:twig templates/
docker compose exec php php bin/console lint:yaml config/
```
