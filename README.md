# Lyra Core (Claude)

پایهٔ **غیر اختصاصی** برای ساخت صفحه‌های داینامیک و قابل‌ویرایش **Elementor / Elementor Pro** از روی کد.
طراحی‌ها (HTML / Tailwind / Figma) را در `designs/` می‌گذارید، به ایجنت می‌دهید، و بخش یا صفحهٔ متناظر با ویجت‌ها و کانتینرهای
المنتور ساخته می‌شود؛ بعد با یک کلیک از پیشخوان وردپرس (**Lyra Core → Build**) ساخته و بعداً در Elementor ویرایش می‌شود.

A generic, non-branded foundation for building dynamic, editable Elementor (Pro) pages from code, with an agent-ready
workflow (skills, commands, scaffolding and lint scripts).

## ساختار / Layout

| مسیر | کاربرد |
|---|---|
| `lyra-core/` | خود پلاگین وردپرس (همین پوشه zip می‌شود) |
| `lyra-core/includes/class-lyra-page-builder.php` | تبدیل recipe به صفحه / تمپلیت Theme Builder، idempotent |
| `lyra-core/includes/recipes/` | یک فایل برای هر صفحه (PHP یا JSON) |
| `lyra-core/widgets/` | ویجت‌ها + manifest در `class-lyra-elementor.php` |
| `designs/` | طراحی‌های مرجع |
| `scripts/` | `analyze_html.py`، `new_widget.py`، `lint_plugin.py`، `build_zip.py` |
| `.claude/skills/` | ۷ skill: تبدیل طراحی، ویجت، recipe، محتوای داینامیک، ووکامرس، توکن/CSS، QA |
| `.claude/commands/` | `/build-section`، `/new-widget`، `/qa` |
| `CLAUDE.md` / `AGENTS.md` | قوانین و دستور کار ایجنت |
| `docs/` | معماری، workflow، قالب جدول mapping |

## شروع سریع

```bash
python scripts/lint_plugin.py lyra-core      # بررسی ایستا
python scripts/build_zip.py                  # dist/lyra-core-<version>.zip
```

نصب: Elementor → (WooCommerce در صورت نیاز) → پلاگین → منوی **Lyra Core** → **Build all**.
صفحهٔ نمونه (`example-landing`) به‌صورت پیش‌نویس ساخته می‌شود تا کل مسیر را ببینید.

## استفاده با ایجنت

1. طراحی را در `designs/<name>/index.html` بگذارید (اختیاری: `screenshot.png`، `notes.md`).
2. به ایجنت بگویید: «سکشن‌های hero و features را از `designs/home` بساز» یا `/build-section designs/home`.
3. ایجنت جدول mapping می‌دهد، ویجت‌های جدید را scaffold می‌کند، CSS و recipe را می‌نویسد، lint می‌گیرد و zip می‌دهد.
4. خطا / لاگ / اسکرین‌شات را برگردانید تا اصلاح شود.

## نکات مهم

- هیچ کلاس Elementor در زمان load پلاگین extend نمی‌شود (ویجت‌ها و tagها lazy-load هستند).
- محتوای Pro (Theme Builder، Loop Grid، dynamic tags) فقط با Elementor Pro فعال است؛ بدون آن فقط محتوای بدنه ساخته می‌شود.
- این نسخه فقط **ایستا** بررسی شده (lint)؛ اجرا روی سایت واقعی لازم است.

License: GPL-2.0-or-later.
