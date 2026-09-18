# scan_past_messages.py - Scan Past Group Messages for Testbook Queries & Reach Out
import asyncio
import random
import os
import sys
from datetime import datetime

from telethon import TelegramClient, errors
from telethon.tl.types import User

import config
import tracker

LOG_FILE = os.path.join(os.path.dirname(__file__), "activity.log")

def log(msg):
    timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    entry = f"[{timestamp}] {msg}"
    print(entry)
    try:
        with open(LOG_FILE, "a", encoding="utf-8") as f:
            f.write(entry + "\n")
    except Exception:
        pass

async def scan_group_history(client, group_entity, keywords, limit_per_keyword=100):
    print("\n" + "=" * 65)
    print(f"🔍 SEARCHING PAST MESSAGES in: {getattr(group_entity, 'title', 'Target Group')}")
    print(f"📋 Searching keywords: {', '.join(keywords[:6])}...")
    print("=" * 65)

    me = await client.get_me()
    my_id = me.id
    found_users = {}  # user_id -> dict

    for kw in keywords:
        print(f"[*] Searching for '{kw}'...")
        count = 0
        try:
            async for msg in client.iter_messages(group_entity, search=kw, limit=limit_per_keyword):
                count += 1
                sender = await msg.get_sender()

                # Skip invalid senders, bots, channels, and ourselves
                if not sender or not isinstance(sender, User) or sender.id == my_id or sender.bot:
                    continue

                user_id = sender.id
                if user_id not in found_users:
                    found_users[user_id] = {
                        "user_id": user_id,
                        "sender_entity": sender,
                        "first_name": sender.first_name or "मित्र",
                        "username": sender.username or "",
                        "message": msg.text or "",
                        "date": msg.date.strftime("%Y-%m-%d %H:%M"),
                        "keyword": kw
                    }
        except Exception as e:
            print(f"[-] Search error for keyword '{kw}': {e}")

    return found_users

async def main():
    print("""
============================================================
🎯 MPSC Telegram History Scanner & Targeted Outreach
============================================================
""")
    session_path = os.path.join(os.path.dirname(__file__), config.SESSION_NAME)
    if not os.path.exists(session_path + ".session") and not os.path.exists(session_path):
        print("[-] Session not found. Please run main.py / run.bat first to log in.")
        return

    client = TelegramClient(session_path, config.API_ID, config.API_HASH)
    await client.start()

    me = await client.get_me()
    print(f"[+] Logged in as: {me.first_name} (@{me.username or me.phone})")

    # Connect to target group
    target_id = getattr(config, 'TARGET_CHAT_ID', -1001778514859)
    try:
        group_entity = await client.get_entity(target_id)
        print(f"[+] Connected to group: {getattr(group_entity, 'title', target_id)}")
    except Exception as e:
        print(f"[-] Could not connect to group ID {target_id}: {e}")
        return

    # Scan history for target keywords
    search_keywords = ["testbook", "टेस्टबुक", "test series", "टेस्ट सिरीज", "mock test", "मॉक टेस्ट"]
    found_users = await scan_group_history(client, group_entity, search_keywords, limit_per_keyword=150)

    if not found_users:
        print("\n[-] No past messages found matching these keywords.")
        return

    print(f"\n[+] Found {len(found_users)} unique users who previously posted about Testbook / Test Series:")
    print("-" * 65)

    eligible_users = []
    for idx, (uid, u) in enumerate(found_users.items(), 1):
        already = tracker.is_already_contacted(uid)
        status_tag = " [ALREADY CONTACTED]" if already else " [NEW]"
        uname = f"@{u['username']}" if u['username'] else "No username"
        print(f"  {idx}. {u['first_name']} ({uname}) - Date: {u['date']}{status_tag}")
        snippet = u['message'].replace("\n", " ")[:70]
        print(f"     Msg: \"{snippet}...\" (Keyword: '{u['keyword']}')")

        if not already:
            eligible_users.append(u)

    print("-" * 65)
    print(f"Total Eligible Users to message: {len(eligible_users)} (Already contacted: {len(found_users) - len(eligible_users)})")

    if not eligible_users:
        print("\n[+] All discovered users have already been messaged. Nothing to do!")
        return

    # Prompt user for outreach
    confirm = input(f"\nDo you want to send the practice link to these new users? (y/n): ").strip().lower()
    if confirm not in ['y', 'yes']:
        print("[+] Operation cancelled. No messages were sent.")
        return

    # Safety limit prompt
    today_sent = tracker.get_today_count()
    remaining_today = max(0, config.MAX_DAILY_MESSAGES - today_sent)
    default_batch = min(15, len(eligible_users), remaining_today)

    if remaining_today <= 0:
        print(f"\n⚠️ Daily safe limit reached ({today_sent}/{config.MAX_DAILY_MESSAGES} DMs sent today).")
        print("Please run this again tomorrow to keep your Telegram account safe.")
        return

    limit_input = input(f"How many users to message now? (1-{min(len(eligible_users), remaining_today)}, default: {default_batch}): ").strip()
    try:
        send_count = int(limit_input) if limit_input else default_batch
        send_count = min(send_count, len(eligible_users), remaining_today)
    except ValueError:
        send_count = default_batch

    print(f"\n🚀 Starting outreach to {send_count} users...")
    print(f"⏱️ Delay between messages: 45 - 75 seconds (Anti-Spam protection)")
    print("Press Ctrl + C to stop at any time.\n")

    successful_sends = 0
    for idx, u in enumerate(eligible_users[:send_count], 1):
        uid = u['user_id']
        uname = u['username']
        fname = u['first_name']
        kw = u['keyword']
        display_name = f"{fname} (@{uname})" if uname else f"{fname} (ID: {uid})"

        # Double check contact state
        if tracker.is_already_contacted(uid):
            continue

        msg_text = config.MESSAGE_TEMPLATE.format(name=fname, keyword=kw)

        if config.DRY_RUN:
            print(f"[{idx}/{send_count}] [DRY RUN] Would send to {display_name}:\n{msg_text[:120]}...\n")
            tracker.record_contact(uid, uname, fname, kw)
            successful_sends += 1
            continue

        try:
            print(f"[{idx}/{send_count}] Sending DM to {display_name}...")
            await client.send_message(u['sender_entity'], msg_text, link_preview=True)
            tracker.record_contact(uid, uname, fname, kw)
            successful_sends += 1
            log(f"✅ Sent past-history outreach to {display_name} (Msg snippet: {u['message'][:40]})")
            print(f"  --> Delivered successfully!")

        except errors.PeerFloodError:
            log("❌ Telegram PeerFloodError: Telegram has temporarily restricted sending DMs to non-contacts. Stopping outreach for today.")
            print("\n[!] Telegram limit reached for today. Stopping now to protect your account.")
            break
        except errors.UserPrivacyRestrictedError:
            log(f"⚠️ User {display_name} has privacy restrictions. Skipping.")
            print(f"  --> Skipped (user privacy restrictions).")
            tracker.record_contact(uid, uname, fname, kw)
        except errors.FloodWaitError as e:
            print(f"  --> FloodWait: Sleeping {e.seconds} seconds...")
            await asyncio.sleep(e.seconds)
        except Exception as e:
            log(f"[-] Error sending DM to {display_name}: {e}")
            print(f"  --> Error: {e}")

        # Sleep before next user if there are more to message
        if idx < send_count:
            delay = random.randint(45, 75)
            print(f"⏳ Waiting {delay} seconds before next DM to stay 100% safe...\n")
            await asyncio.sleep(delay)

    print("\n" + "=" * 65)
    print(f"🏁 COMPLETED! Successfully contacted {successful_sends} students.")
    print(f"📊 Total contacted today: {tracker.get_today_count()}/{config.MAX_DAILY_MESSAGES}")
    print("=" * 65)

if __name__ == "__main__":
    try:
        asyncio.run(main())
    except KeyboardInterrupt:
        print("\n[+] Script stopped by user.")
