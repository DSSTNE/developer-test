import os
from typing import Any

import requests


def send_alert(message: str) -> dict[str, Any]:
    token = os.getenv("TELEGRAM_BOT_TOKEN", "")
    chat_id = os.getenv("TELEGRAM_CHAT_ID", "")

    if not token or not chat_id:
        return {"ok": False, "skipped": True, "reason": "telegram not configured"}

    response = requests.post(
        f"https://api.telegram.org/bot{token}/sendMessage",
        json={"chat_id": chat_id, "text": message},
        timeout=15,
    )
    return {
        "ok": response.ok,
        "status_code": response.status_code,
        "body": response.text[:300],
    }


def notify_api_failure(service: str, details: str) -> None:
    send_alert(f"⚠️ API недоступний: {service}\n{details}")
