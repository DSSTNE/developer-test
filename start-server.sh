#!/usr/bin/env bash
cd "$(dirname "$0")/level-1-frontend"
echo "Сайт: http://localhost:8080"
echo "Contact: http://localhost:8080/contact.html"
echo "Натисни Ctrl+C щоб зупинити"
python3 -m http.server 8080
