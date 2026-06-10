# Developer Test Task

Тестове завдання: 4 рівні складності.

## Структура

```
developer-test/
├── level-1-frontend/    # Верстка за Figma
├── level-2-data/          # Excel + XML
├── level-3-integration/   # Форма + SalesDrive + Діловод
└── level-4-cms/           # WooCommerce плагін
```

## Рівень 1 — Frontend

- Макет: [Figma](https://www.figma.com/design/fxz6GkQVW9BwrCOcn9EvGX/Test-Task?node-id=0-1)
- Шрифт: Inter
- Сторінки: Landing, Contact Us

## Рівень 1 — Запуск локально

```bash
cd level-1-frontend
python3 -m http.server 8080
# Відкрити http://localhost:8080
```

## Рівень 1 — Деплой на тестовий домен

1. Залити репозиторій на GitHub
2. Підключити до **Netlify**, **Vercel** або **GitHub Pages**
3. Root directory: `level-1-frontend`
4. Отримати URL типу `https://your-name.netlify.app`

## Статус

- [x] Рівень 1 — Frontend (Landing + Contact Us)
- [ ] Рівень 2 — Data
- [ ] Рівень 3 — Integration
- [ ] Рівень 4 — CMS
