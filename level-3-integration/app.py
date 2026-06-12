import os

from dotenv import load_dotenv
from flask import Flask, jsonify, request
from flask_cors import CORS

from dilovod import create_client_from_lead
from monitor import start_monitor
from salesdrive import send_lead
from spam_guard import is_spam
from validators import validate_contact_payload

load_dotenv()

app = Flask(__name__)

origins = os.getenv(
    "ALLOWED_ORIGINS",
    "http://localhost:8080,https://jocular-dodol-fa0b4b.netlify.app",
)
CORS(app, resources={r"/api/*": {"origins": [item.strip() for item in origins.split(",")]}})

start_monitor()


@app.get("/api/health")
def health():
    return jsonify({"status": "ok"})


@app.post("/api/contact")
def contact():
    data = request.get_json(silent=True) or {}

    spam_reason = is_spam(data, request.remote_addr or "unknown")
    if spam_reason:
        return jsonify({"ok": False, "error": "request blocked"}), 429

    payload, error = validate_contact_payload(data)
    if error:
        return jsonify({"ok": False, "error": error}), 400

    salesdrive_result = send_lead(payload)
    if not salesdrive_result.get("ok"):
        return jsonify({"ok": False, "salesdrive": salesdrive_result}), 502

    order_id = salesdrive_result.get("order_id")
    dilovod_result = create_client_from_lead(payload, order_id)

    if not dilovod_result.get("ok"):
        return jsonify(
            {
                "ok": False,
                "salesdrive": salesdrive_result,
                "dilovod": dilovod_result,
            }
        ), 502

    return jsonify(
        {
            "ok": True,
            "salesdrive": salesdrive_result,
            "dilovod": dilovod_result,
        }
    )


if __name__ == "__main__":
    port = int(os.getenv("FLASK_PORT", "5000"))
    app.run(host="0.0.0.0", port=port, debug=True)
