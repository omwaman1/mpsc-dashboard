# MPSC Telegram Targeted Auto-Outreach Assistant 🎯

An automated Telegram personal client assistant that monitors any public discussion group you belong to, detects when members ask for **Testbook / MPSC Test Series / Mock Tests**, and automatically reaches out to them with your practice website link in a polite, personalized private message (DM).

---

## 🌟 Features
- **Zero Admin Rights Required**: Works as a regular group member in any discussion group.
- **Automatic Keyword Matching**: Listens for keywords like `testbook`, `test series`, `टेस्टबुक`, `टेस्ट सिरीज`, `मॉक टेस्ट`, `सराव पेपर`, etc.
- **Smart Anti-Spam Safety**:
  - Automatically randomizes delays (30 to 60 seconds) between DMs.
  - Limits maximum DMs per day (default 25 DMs/day) to keep your Telegram account safe from `SpamBot` flags.
  - Never contacts the same user twice (`contacted_users.json`).
- **Interactive First Login**: Uses Telegram's official MTProto API. On the very first run, you enter your phone number and login code. It saves your session file locally so you never need to log in again.
- **Dry-Run Mode**: Test in console without sending real messages by setting `DRY_RUN = True` in `config.py`.

---

## 🚀 How to Run

1. Open folder: `c:\Users\Laptop\Desktop\PROJECT MPSC\telegram-helper\`
2. Double-click **`run.bat`** (or open terminal and run `python main.py`).
3. **First-time Login**:
   - Enter your Telegram phone number with country code (e.g. `+919876543210`).
   - Enter the login code Telegram sends to your Telegram app.
   - If you have Two-Step Verification (cloud password) enabled, enter your password.
4. **Select Group**:
   - The script will display a list of your joined groups.
   - Type the number of your target discussion group (or type `0` to enter `@group_username` manually).
5. Done! The script will quietly listen in the background and notify you in the terminal whenever a student asks for Testbook/Test Series!
