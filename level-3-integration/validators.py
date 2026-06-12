import re

NAME_RE = re.compile(r"^[A-Za-zА-Яа-яІіЇїЄєҐґ'\-\s]{2,50}$")
EMAIL_RE = re.compile(r"^[^@\s]+@[^@\s]+\.[^@\s]+$")

UA_OPERATOR_CODES = (
    "50", "63", "66", "67", "68", "73",
    "91", "92", "93", "94", "95", "96", "97", "98", "99",
)


def normalize_phone(value: str) -> str:
    digits = re.sub(r"\D", "", value)
    if digits.startswith("380"):
        return digits
    if digits.startswith("0") and len(digits) == 10:
        return "38" + digits
    if len(digits) == 9:
        return "380" + digits
    return digits


def is_valid_ua_phone(value: str) -> bool:
    digits = normalize_phone(value)
    if len(digits) != 12 or not digits.startswith("380"):
        return False
    operator = digits[3:5]
    return operator in UA_OPERATOR_CODES


def validate_contact_payload(data: dict) -> tuple[dict | None, str | None]:
    first_name = str(data.get("first_name", "")).strip()
    last_name = str(data.get("last_name", "")).strip()
    email = str(data.get("email", "")).strip()
    phone = str(data.get("phone", "")).strip()
    message = str(data.get("message", "")).strip()

    if not first_name:
        return None, "Вкажіть ім'я"
    if not NAME_RE.match(first_name):
        return None, "Ім'я — тільки літери"
    if last_name and not NAME_RE.match(last_name):
        return None, "Прізвище — тільки літери"
    if not email:
        return None, "Вкажіть email"
    if not EMAIL_RE.match(email):
        return None, "Некоректний email"
    if not phone:
        return None, "Вкажіть телефон"
    if not is_valid_ua_phone(phone):
        return None, "Телефон має бути у форматі українського оператора (+380...)"

    return {
        "first_name": first_name,
        "last_name": last_name,
        "email": email,
        "phone": normalize_phone(phone),
        "message": message,
    }, None
