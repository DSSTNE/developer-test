import json
import os
import threading
import time

import requests

from telegram_notify import notify_api_failure

CHECK_INTERVAL_SEC = 300


def _ping_salesdrive() -> bool:
    base_url = os.getenv("SALESDRIVE_BASE_URL", "").rstrip("/")
    api_key = os.getenv("SALESDRIVE_API_KEY", "")
    if not base_url or not api_key:
        return True

    try:
        response = requests.get(
            f"{base_url}/api/statuses/",
            headers={"X-Api-Key": api_key},
            timeout=15,
        )
        return response.ok
    except requests.RequestException:
        return False


def _ping_dilovod() -> bool:
    api_key = os.getenv("DILOVOD_API_KEY", "")
    api_url = os.getenv("DILOVOD_API_URL", "https://api.dilovod.ua").rstrip("/")
    if not api_key:
        return True

    packet = {
        "version": "0.25",
        "key": api_key,
        "action": "request",
        "params": {
            "from": "catalogs.persons",
            "fields": {"id": "id"},
            "limit": 1,
        },
    }

    try:
        response = requests.post(
            api_url,
            data={"packet": json.dumps(packet, ensure_ascii=False)},
            headers={"Content-Type": "application/x-www-form-urlencoded"},
            timeout=15,
        )
        return response.status_code < 500
    except requests.RequestException:
        return False


def run_health_checks() -> None:
    if not _ping_salesdrive():
        notify_api_failure("SalesDrive", "health-check failed")
    if not _ping_dilovod():
        notify_api_failure("Dilovod", "health-check failed")


def start_monitor() -> None:
    if os.getenv("ENABLE_HEALTH_MONITOR", "1") != "1":
        return

    def loop() -> None:
        while True:
            run_health_checks()
            time.sleep(CHECK_INTERVAL_SEC)

    thread = threading.Thread(target=loop, daemon=True)
    thread.start()
