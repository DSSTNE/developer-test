# developer-test

Тестове завдання, 4 рівні.

## Посилання

- Сайт: https://jocular-dodol-fa0b4b.netlify.app
- Репо: https://github.com/DSSTNE/developer-test

## Що де лежить

- `level-1-frontend/` — верстка (Landing + Contact)
- `level-2-data/` — скрипт для Excel і XML
- `level-3-integration/` — ще не робив
- `level-4-cms/` — ще не робив

---

## Рівень 1

Макет: https://www.figma.com/design/fxz6GkQVW9BwrCOcn9EvGX/Test-Task

Зробив Landing і Contact Us, шрифт Inter. Є hover на картках, бургер-меню на мобілці, тінь на посиланнях з `#`.

Локально:
```bash
cd level-1-frontend
python3 -m http.server 8080
```

На Netlify задеплоєно з папки `level-1-frontend`. В корені є `netlify.toml` — без нього був 404.

---

## Рівень 2

Скрипт оновлює ціни в Import.xlsx по прайсу, рахує стару ціну (+10%), фарбує рядки і генерує catalog.xml.

```bash
cd level-2-data
pip install -r requirements.txt
python3 process.py
```

Результат в `level-2-data/output/`.

Детальніше — в README всередині level-2-data.

---

## Статус

- [x] рівень 1
- [x] рівень 2
- [ ] рівень 3
- [ ] рівень 4
