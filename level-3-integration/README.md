# level-3 — API

Форма → SalesDrive → Dilovod.

## Запуск

```bash
pip install -r requirements.txt
cp .env.example .env
python3 app.py
```

Фронт шле на `http://localhost:5000` (або `window.CONTACT_API_URL` на проді).

## .env

```
SALESDRIVE_BASE_URL=https://твій-домен.salesdrive.me
SALESDRIVE_API_KEY=
SALESDRIVE_FORM_ID=

DILOVOD_API_KEY=
DILOVOD_CLIENT_PARENT_ID=

TELEGRAM_BOT_TOKEN=
TELEGRAM_CHAT_ID=
```

Без ключів працює demo mode — для перевірки UI.

## Що є

- ім'я тільки літери, телефон +380 з кодами операторів
- honeypot + мін 3 сек + rate limit
- SalesDrive `/handler/`
- Dilovod `saveObject`, категорія «Клієнт»
- Telegram при падінні API, health-check кожні 5 хв

Доки:
- https://salesdrive.ua/knowledge/api/
- https://help.dilovod.ua/uk/article/api-dilovod-1gwt3m0/
