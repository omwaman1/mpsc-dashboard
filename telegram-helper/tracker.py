# tracker.py - Persistent Contact Tracking & Rate Limit Manager
import json
import os
from datetime import datetime

TRACKER_FILE = os.path.join(os.path.dirname(__file__), "contacted_users.json")

def load_data():
    if not os.path.exists(TRACKER_FILE):
        return {"contacted": {}, "daily_history": {}}
    try:
        with open(TRACKER_FILE, "r", encoding="utf-8") as f:
            return json.load(f)
    except Exception:
        return {"contacted": {}, "daily_history": {}}

def save_data(data):
    try:
        with open(TRACKER_FILE, "w", encoding="utf-8") as f:
            json.dump(data, f, indent=2, ensure_ascii=False)
    except Exception as e:
        print(f"[-] Error saving tracker data: {e}")

def is_already_contacted(user_id):
    data = load_data()
    return str(user_id) in data.get("contacted", {})

def get_today_count():
    data = load_data()
    today_str = datetime.now().strftime("%Y-%m-%d")
    return len(data.get("daily_history", {}).get(today_str, []))

def record_contact(user_id, username, first_name, matched_keyword):
    data = load_data()
    now_str = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    today_str = datetime.now().strftime("%Y-%m-%d")

    # Record permanent contact
    if "contacted" not in data:
        data["contacted"] = {}
    data["contacted"][str(user_id)] = {
        "username": username or "",
        "first_name": first_name or "",
        "timestamp": now_str,
        "keyword": matched_keyword
    }

    # Record daily count
    if "daily_history" not in data:
        data["daily_history"] = {}
    if today_str not in data["daily_history"]:
        data["daily_history"][today_str] = []
    data["daily_history"][today_str].append(str(user_id))

    save_data(data)
