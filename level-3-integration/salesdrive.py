import os
from typing import Any

import requests

from telegram_notify import notify_api_failure


def send_lead(payload: dict[str, str]) -> dict[str, Any]:
    base_url = os.getenv("SALESDRIVE_BASE_URL", "").rstrip("/")
    api_key = os.getenv("SALESDRIVE_API_KEY", "")
    form_id = os.getenv("SALESDRIVE_FORM_ID", api_key)

    if not base_url or not api_key:
        return {
            "ok": True,
            "demo": True,
            "order_id": "demo-order",
            "message": "SalesDrive keys not set (demo mode)",
        }

    data = {
        "form": form_id,
        "fName": payload.get("first_name", ""),
        "lName": payload.get("last_name", ""),
        "phone": payload.get("phone", ""),
        "email": payload.get("email", ""),
        "comment": payload.get("message", ""),
    }

    try:
        response = requests.post(
            f"{base_url}/handler/",
            data=data,
            headers={"X-Api-Key": api_key},
            timeout=20,
        )
    except requests.RequestException as error:
        notify_api_failure("SalesDrive", str(error))
        return {"ok": False, "error": str(error)}

    if not response.ok:
        notify_api_failure("SalesDrive", f"status {response.status_code}: {response.text[:200]}")

    order_id = None
    try:
        body = response.json()
        order_id = body.get("id") or body.get("orderId")
    except ValueError:
        body = {"raw": response.text[:300]}

    return {
        "ok": response.ok,
        "status_code": response.status_code,
        "order_id": order_id,
        "body": body if isinstance(body, dict) else {"raw": response.text[:300]},
    }
