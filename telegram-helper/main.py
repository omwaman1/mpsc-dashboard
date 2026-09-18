# main.py - Automated MPSC Telegram Outreach Assistant
import asyncio
import random
import sys
import os
import re
from datetime import datetime

from telethon import TelegramClient, events, errors
from telethon.tl.types import User, Channel, Chat

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

def find_matched_keyword(text):
    if not text:
        return None
    lower_text = text.lower()
    for kw in config.TARGET_KEYWORDS:
        # Check whole words or phrases
        if kw.lower() in lower_text:
            return kw
    return None

async def select_target_group(client):
    print("\n" + "=" * 60)
    print("🔍 Fetching discussion groups from your Telegram account...")
    print("=" * 60)

    dialogs = []
    async for dialog in client.iter_dialogs():
        if dialog.is_group or dialog.is_channel:
            dialogs.append(dialog)

    if not dialogs:
        print("[-] No groups found. Make sure you have joined the discussion group.")
        target = input("\nEnter group username or invite link (e.g. @group_name): ").strip()
        return target

    print("\nSelect the discussion group you want to monitor:")
    for idx, d in enumerate(dialogs[:25], 1):
        username = f" (@{d.entity.username})" if getattr(d.entity, 'username', None) else ""
        print(f"  [{idx}] {d.name}{username}")

    print("  [0] Enter another group username / link manually")

    while True:
        try:
            choice = input(f"\nSelect group (1-{min(25, len(dialogs))}) or 0: ").strip()
            if choice == "0":
                return input("Enter group @username or invite link: ").strip()
            choice_num = int(choice)
            if 1 <= choice_num <= len(dialogs):
                selected = dialogs[choice_num - 1]
                print(f"[+] Selected Group: {selected.name} (ID: {selected.id})")
                return selected.entity
        except ValueError:
            pass
        print("[-] Invalid selection, please enter a valid number.")

async def main():
    print("""
============================================================
🎯 MPSC Telegram Targeted Auto-Outreach Assistant
============================================================
""")
    session_path = os.path.join(os.path.dirname(__file__), config.SESSION_NAME)
    client = TelegramClient(session_path, config.API_ID, config.API_HASH)

    await client.start()
    me = await client.get_me()
    print(f"[+] Successfully logged in as: {me.first_name} (@{me.username or me.phone})")

    target_id = getattr(config, 'TARGET_CHAT_ID', None)
    group_entity = None
    if target_id:
        try:
            group_entity = await client.get_entity(target_id)
            print(f"[+] Automatically connected to: {getattr(group_entity, 'title', target_id)} (ID: {target_id})")
        except Exception as e:
            print(f"[-] Could not auto-connect to configured group ({target_id}): {e}")
            group_entity = None

    if not group_entity:
        target_group = await select_target_group(client)
        group_entity = await client.get_entity(target_group)

    group_title = getattr(group_entity, 'title', getattr(config, 'TARGET_CHAT_TITLE', 'Configured Group'))

    print("\n" + "=" * 60)
    print(f"🚀 NOW MONITORING: {group_title}")
    print(f"📋 Target Keywords: {', '.join(config.TARGET_KEYWORDS[:6])}...")
    print(f"🛡️  Daily Limit: {config.MAX_DAILY_MESSAGES} DMs/day (Today sent: {tracker.get_today_count()})")
    print(f"⏱️  Random Delay: {config.MIN_DELAY_SECONDS} - {config.MAX_DELAY_SECONDS} seconds")
    print(f"🧪 Dry Run Mode: {'ENABLED (No real DMs sent)' if config.DRY_RUN else 'LIVE'}")
    print("=" * 60 + "\n")
    log(f"Started monitoring group: {group_title}")

    @client.on(events.NewMessage(chats=group_entity))
    async def handler(event):
        try:
            message_text = event.raw_text
            sender = await event.get_sender()

            # Ignore messages sent by ourselves or bots
            if not sender or not isinstance(sender, User) or sender.is_self or sender.bot:
                return

            matched_kw = find_matched_keyword(message_text)
            if not matched_kw:
                return

            user_id = sender.id
            username = sender.username or ""
            first_name = sender.first_name or "मित्र"
            user_display = f"{first_name} (@{username})" if username else f"{first_name} (ID: {user_id})"

            log(f"🔔 MATCH DETECTED! User {user_display} mentioned keyword '{matched_kw}'")
            log(f"💬 Message snippet: \"{message_text[:80]}...\"")

            # Check if user was already contacted
            if tracker.is_already_contacted(user_id):
                log(f"⏭️ Skipping {user_display}: already contacted previously.")
                return

            # Check daily rate limit
            today_count = tracker.get_today_count()
            if today_count >= config.MAX_DAILY_MESSAGES:
                log(f"⚠️ Daily limit reached ({today_count}/{config.MAX_DAILY_MESSAGES}). Skipping DM to prevent spam flags.")
                return

            # Prepare personalized message
            outreach_msg = config.MESSAGE_TEMPLATE.format(
                name=first_name,
                keyword=matched_kw
            )

            # Random natural delay to keep account completely safe
            delay = random.randint(config.MIN_DELAY_SECONDS, config.MAX_DELAY_SECONDS)
            log(f"⏳ Waiting {delay} seconds before sending DM to appear natural...")
            await asyncio.sleep(delay)

            if config.DRY_RUN:
                log(f"[DRY-RUN] Would have sent DM to {user_display}:\n{outreach_msg}")
                tracker.record_contact(user_id, username, first_name, matched_kw)
                return

            # Send private message
            try:
                await client.send_message(sender, outreach_msg, link_preview=True)
                tracker.record_contact(user_id, username, first_name, matched_kw)
                log(f"✅ SUCCESS! Private message sent to {user_display} (Today total: {tracker.get_today_count()})")

            except errors.PeerFloodError:
                log("❌ Telegram PeerFloodError: Telegram has temporarily restricted sending DMs to non-contacts. Please pause outreach for 12-24 hours.")
            except errors.UserPrivacyRestrictedError:
                log(f"⚠️ Could not DM {user_display}: User privacy settings do not accept messages from non-contacts.")
                tracker.record_contact(user_id, username, first_name, matched_kw)
            except errors.FloodWaitError as e:
                log(f"⚠️ Telegram FloodWait: Must wait {e.seconds} seconds before next action.")
                await asyncio.sleep(e.seconds)
            except Exception as e:
                log(f"[-] Error sending DM to {user_display}: {e}")

        except Exception as e:
            log(f"[-] Error handling incoming message: {e}")

    print("🟢 Assistant is running! Listening for incoming messages in real-time...")
    print("Press Ctrl + C in the terminal to stop at any time.\n")
    await client.run_until_disconnected()

if __name__ == "__main__":
    try:
        asyncio.run(main())
    except KeyboardInterrupt:
        print("\n[+] Assistant stopped cleanly.")
