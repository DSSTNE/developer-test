import time
from collections import defaultdict

MIN_FORM_DELAY_SEC = 3
MAX_REQUESTS_PER_MINUTE = 5

_hits: dict[str, list[float]] = defaultdict(list)


def is_spam(data: dict, client_ip: str) -> str | None:
    if str(data.get("website", "")).strip():
        return "spam detected"

    loaded_at = int(data.get("form_loaded_at", 0) or 0)
    if loaded_at <= 0:
        return "invalid form token"
    if time.time() - loaded_at < MIN_FORM_DELAY_SEC:
        return "too fast"

    now = time.time()
    history = [stamp for stamp in _hits[client_ip] if now - stamp < 60]
    _hits[client_ip] = history
    if len(history) >= MAX_REQUESTS_PER_MINUTE:
        return "rate limit"
    history.append(now)

    return None
