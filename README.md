# developer-test

Тестове завдання на 4 рівні. Робив поступово, все в одному репо.

## Посилання

- Сайт: https://jocular-dodol-fa0b4b.netlify.app
- GitHub: https://github.com/DSSTNE/developer-test

## Папки

- `level-1-frontend/` — верстка
- `level-2-data/` — Excel + XML
- `level-3-integration/` — бекенд форми
- `level-4-cms/` — WooCommerce плагін

---

## Рівень 1

Макет з Figma: https://www.figma.com/design/fxz6GkQVW9BwrCOcn9EvGX/Test-Task

Landing + Contact Us, шрифт Inter. Hover на картках, бургер на мобілці, тінь на `#` посиланнях.

```bash
cd level-1-frontend
python3 -m http.server 8080
```

На Netlify викладено з `level-1-frontend`. Є `netlify.toml` в корені — без нього був 404.

---

## Рівень 2

Скрипт підтягує ціни з прайсу в Import, рахує old_price (+10%), фарбує змінені рядки, генерує catalog.xml.

```bash
cd level-2-data
pip install -r requirements.txt
python3 process.py
```

Результат — `level-2-data/output/`.

---

## Рівень 3

Форма на contact.html → Flask API → SalesDrive (заявка) → Dilovod (клієнт).

Валідація імені/телефону, антиспам без капчі, Telegram якщо API лежить.

```bash
cd level-3-integration
pip install -r requirements.txt
cp .env.example .env
python3 app.py
```

Потрібні тестові акаунти SalesDrive і Dilovod — логін/пароль передам для перевірки.
Бекенд для продакшну треба задеплоїти окремо, в `.env` прописати ключі.

---

## Рівень 4

Варіант B — WooCommerce. Плагін `dts-custom-shipping`: свій shipping method, поля місто/відділення/коментар, налаштування в адмінці.

Тестив локально через LocalWP (`developer-test-wp.local`). Як ставити — в `level-4-cms/README.md`.

---

## Статус

- [x] 1 — frontend
- [x] 2 — data
- [x] 3 — integration (код готовий, акаунти/деплой ще дороблю)
- [x] 4 — WooCommerce (перевірив на order #21)
