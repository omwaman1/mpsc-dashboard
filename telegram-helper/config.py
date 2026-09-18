# config.py - Configuration for MPSC Telegram Assistant

API_ID = 27644990
API_HASH = "0e5253a73a875242ce52b12a58d5ad49"
SESSION_NAME = "mpsc_userbot"

# Target Discussion Group ID: 『😈』╚❖ Techie Gamer Chat ❖╝『🇮🇳』
TARGET_CHAT_ID = -1001778514859
TARGET_CHAT_TITLE = "『😈』╚❖ Techie Gamer Chat ❖╝『🇮🇳』"

# Target Keywords to trigger automated outreach (case-insensitive)
TARGET_KEYWORDS = [
    "testbook",
    "test book",
    "टेस्टबुक",
    "टेस्ट बुक",
    "test series",
    "testseries",
    "टेस्ट सिरीज",
    "टेस्टसीरिज",
    "mock test",
    "मॉक टेस्ट",
    "सराव पेपर",
    "सराव संच",
    "testbook pass",
    "testbook coupon",
    "test book pass"
]

# Personalized message sent to student's inbox
MESSAGE_TEMPLATE = """नमस्कार {name}! 👋

तुम्ही ग्रुपमध्ये Testbook / MPSC टेस्ट सिरीजबद्दल मेसेज केला होता.

तुम्हाला MPSC गट-क (Combined Group C 2026), राज्यसेवा व सर्व परीक्षांचे Testbook व आयोगाचे मागील सर्व प्रश्न, विषयवार सराव संच आणि सविस्तर मराठी स्पष्टीकरणे येथे थेट सोडवता येतील:

👉 https://search.mpscabhyas.in/mpsc/practice.php

🎯 वैशिष्ट्ये:
• सर्व १४,४००+ प्रश्न घटकनिहाय उपलब्ध
• प्रत्येक प्रश्नाचे मराठीत सविस्तर स्पष्टीकरण
• पहिले १ तास पूर्णपणे मोफत सराव
• आवडल्यास फक्त ₹१० मध्ये संपूर्ण दिवसभराचा पास

अभ्यासासाठी खूप खूप शुभेच्छा! 📚✨"""

# Safety & Anti-Spam Rate Limits
MIN_DELAY_SECONDS = 30       # Minimum wait before sending DM (to appear natural)
MAX_DELAY_SECONDS = 60       # Maximum wait before sending DM
MAX_DAILY_MESSAGES = 25      # Safe maximum DMs per 24 hours to avoid Telegram spam limits
DRY_RUN = False              # Set True to test in console without sending real messages
