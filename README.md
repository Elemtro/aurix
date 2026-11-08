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

Frazy enrollmentu (stałe, wspólne dla kont):

1. `kawa most zima wiatr`
2. `ryba okno las chmura`
3. `torba lato droga gwiazda`

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
    │   ├── Entity/           # User, VoiceSample
    │   ├── Form/             # RegistrationFormType
    │   ├── Repository/
    │   ├── Security/         # guard, szyfrowanie szablonów
    │   └── Voice/            # frazy, matcher, klient ML
    ├── templates/
    └── tests/
```

## Szybki start

```bash
# 1. Zbuduj i uruchom web + bazę
docker compose up -d --build

# 2. Zależności (jeśli budujesz od zera)
docker compose exec php composer install

# 3. Migracje
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction

# 4. Tailwind
docker compose exec php php bin/console tailwind:build

# 5. Otwórz
#    http://localhost:8000
```

### Z prawdziwym ML

Wymaga sąsiedniego repo `../aurix-ml`.

```bash
VOICE_ENGINE=http docker compose --profile ml up -d
docker compose restart web   # nginx musi przemapować IP php po recreate
```

Pierwsze nagranie pobiera modele (Whisper, Silero, ECAPA, AASIST) do wolumenu `ml_models`.
PAD jest diagnostyczny (`PAD_ENFORCED=false`) — nie blokuje logowania do czasu kalibracji.

Zatrzymanie:

```bash
docker compose --profile ml down      # zachowuje dane
docker compose --profile ml down -v   # usuwa też wolumeny
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
| `POST` | `/api/challenge` | losowa fraza (przyszły tryb) |
| `POST` | `/api/verify` | weryfikacja losowej frazy (przyszły tryb) |

Pole pliku: `audio` (multipart/form-data). Endpointy wymagają `X-CSRF-Token`.

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
