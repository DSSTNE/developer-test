import json
import os
from typing import Any

import requests

from telegram_notify import notify_api_failure


def create_client_from_lead(payload: dict[str, str], salesdrive_order_id: str | None) -> dict[str, Any]:
    api_url = os.getenv("DILOVOD_API_URL", "https://api.dilovod.ua").rstrip("/")
    api_key = os.getenv("DILOVOD_API_KEY", "")
    client_parent = os.getenv("DILOVOD_CLIENT_PARENT_ID", "")

    if not api_key:
        return {
            "ok": True,
            "demo": True,
            "message": "Dilovod key not set (demo mode)",
        }

    full_name = f"{payload.get('first_name', '')} {payload.get('last_name', '')}".strip()
    details = {
        "phone": payload.get("phone", ""),
        "email": payload.get("email", ""),
        "comment": payload.get("message", ""),
        "salesdrive_order_id": salesdrive_order_id,
        "category": "Клієнт",
    }

    header: dict[str, Any] = {
        "id": "catalogs.persons",
        "name": {"uk": full_name},
        "details": json.dumps(details, ensure_ascii=False),
    }

    if client_parent:
        header["parent"] = int(client_parent)

    packet = {
        "version": "0.25",
        "key": api_key,
        "action": "saveObject",
        "params": {"header": header},
    }

    try:
        response = requests.post(
            api_url,
            data={"packet": json.dumps(packet, ensure_ascii=False)},
            headers={"Content-Type": "application/x-www-form-urlencoded"},
            timeout=20,
        )
    except requests.RequestException as error:
        notify_api_failure("Dilovod", str(error))
        return {"ok": False, "error": str(error)}

    if not response.ok:
        notify_api_failure("Dilovod", f"status {response.status_code}: {response.text[:200]}")

    try:
        body = response.json()
    except ValueError:
        body = {"raw": response.text[:300]}

    api_ok = (
        response.ok
        and isinstance(body, dict)
        and not body.get("error")
        and (body.get("result") == "ok" or body.get("id"))
    )

    if not api_ok and isinstance(body, dict) and body.get("error"):
        notify_api_failure("Dilovod", str(body.get("error")))

    return {
        "ok": api_ok,
        "status_code": response.status_code,
        "body": body,
    }
