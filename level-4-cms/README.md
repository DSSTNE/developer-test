# level-4 — WooCommerce

Варіант B. Плагін `dts-custom-shipping`.

## Що всередині

Shipping method **Custom delivery** + поля в checkout: місто, відділення, коментар. Валідація на сервері, дані в замовленні. Сторінка налаштувань — WooCommerce → DTS Delivery.

Ще є: логи, Nova Poshta API (якщо вставити ключ), webhook, кеш міст/відділень.

## Як ставив у себе

1. LocalWP, сайт `developer-test-wp`
2. WooCommerce + тестовий товар
3. Плагін в `wp-content/plugins/` — у мене через symlink:

```bash
ln -s /home/denys/development/developer-test/level-4-cms/dts-custom-shipping \
  "/home/denys/Local Sites/developer-test-wp/app/public/wp-content/plugins/dts-custom-shipping"
```

4. Activate плагін
5. WooCommerce → Settings → Shipping → Ukraine → Add method → **DTS Delivery**, cost 50
6. Payments → Cash on delivery — enable (для тесту)
7. Checkout → заповнити поля → Place order

На Fedora LocalWP просив `sudo dnf install ncurses-compat-libs` — без цього MySQL не стартував.

## Перевірка

Замовлення #21 — city kiev, branch 123, comment 12345. Скріни для здачі: shipping zones, checkout з полями, order в адмінці.
