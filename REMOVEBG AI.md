# Remove Background AI — Local Setup Guide

This guide explains how to run the **Remove background** AI feature locally on Windows using **rembg** (self-hosted, no paid API key). It covers Python installation, PATH setup, Laravel configuration, queue workers, and troubleshooting.

---

## How it works (quick overview)

```
Media library (Next.js)
  → POST /api/v1/tenant/media/{id}/remove-background
  → Laravel queues RemoveMediaBackgroundJob
  → Job downloads image from tenant storage (local disk or S3)
  → rembg runs on the server (local temp files)
  → New PNG saved to storage + new media row created
  → Original file is kept; new file is named like product-no-bg.png
```

**Important:** If a brand or category already uses the original media ID, it is **not** updated automatically. The new file appears in the media library only. You can manually pick it on the brand, or we can add an “update all usages” feature later.

---

## Prerequisites

| Requirement | Notes |
|-------------|--------|
| **Windows 10/11** | This guide is written for Windows |
| **Laravel Herd** | API running at e.g. `*.multi-tenants-api.test` |
| **Python 3.10+** | Required by rembg |
| **pip** | Comes with Python |
| **Queue worker** | Required when `QUEUE_CONNECTION=database` |
| **Next.js app** | `multi-tenants-app` for the admin UI |

---

## Step 1 — Install Python

1. Download Python from [https://www.python.org/downloads/](https://www.python.org/downloads/)
2. Run the installer
3. **Check “Add python.exe to PATH”** at the bottom of the first screen (recommended)
4. Choose **Install Now** (or Customize and ensure pip is included)
5. Close and reopen PowerShell, then verify:

```powershell
python --version
pip --version
```

You should see something like `Python 3.12.x` and `pip 24.x`.

---

## Step 2 — Install rembg

Your Python is at **`C:\Python312`** (system-wide install). That often causes **Access is denied** if you run `pip install` without `--user`. Use the commands below instead.

Open **PowerShell** (no admin required):

```powershell
python -m pip install --user "rembg[cpu]"
```

| Flag / package | Why |
|----------------|-----|
| `python -m pip` | Uses the correct pip for your Python |
| `--user` | Installs into your user folder — avoids `WinError 5 Access is denied` on `C:\Python312` |
| `rembg[cpu]` | Includes **onnxruntime** (required). Plain `rembg[cli]` alone may show “No onnxruntime backend found” |

**Do not** upgrade pip globally unless you run PowerShell as Administrator. It is optional — rembg works with pip 23.x.

First background-removal run downloads an AI model (~150–180 MB). That is normal.

### Where rembg is installed (`--user` install)

Scripts land here (not on PATH by default):

```
C:\Users\IT\AppData\Roaming\Python\Python312\Scripts\rembg.exe
```

Find it anytime:

```powershell
Get-ChildItem -Path "$env:APPDATA\Python" -Recurse -Filter "rembg.exe" -ErrorAction SilentlyContinue
```

Verify with the **full path**:

```powershell
& "$env:APPDATA\Python\Python312\Scripts\rembg.exe" --help
```

If that works, set in your API `.env`:

```env
REMBG_BINARY=C:\Users\IT\AppData\Roaming\Python\Python312\Scripts\rembg.exe
```

Then skip to **Step 4** (or add that folder to PATH in Step 3 Option B).

---

### If you already tried `pip install` and got Access denied

```
ERROR: Could not install packages due to an OSError: [WinError 5] Access is denied:
'c:\\python312\\lib\\site-packages\\...'
```

**Fix:** Always use `--user`:

```powershell
python -m pip install --user "rembg[cpu]"
```

You do **not** need to fix pip first.

---

### Legacy note: `rembg[cli]` only

If you installed `rembg[cli]` and see:

```
No onnxruntime backend found.
Please install rembg with CPU or GPU support
```

Run:

```powershell
python -m pip install --user "rembg[cpu]"
```

---

Verify the CLI works (after PATH setup **or** using full path above):

```powershell
rembg --help
```

If you see help text, rembg is on your PATH. Skip to **Step 4**.

---

## Step 3 — If `rembg` is not recognized (not on PATH)

This happens when Python was installed **without** “Add to PATH”, or the terminal was open before installation.

### Option A — Find the full path to `rembg.exe`

```powershell
where.exe rembg
```

If that finds nothing, search in Python’s Scripts folder:

```powershell
# Common locations — adjust version number if needed
dir "$env:APPDATA\Python\Python312\Scripts\rembg.exe"
dir "$env:LOCALAPPDATA\Programs\Python\Python312\Scripts\rembg.exe"
dir "C:\Python312\Scripts\rembg.exe"
```

Or search under Roaming Python (typical for `--user` installs):

```powershell
Get-ChildItem -Path "$env:APPDATA\Python" -Recurse -Filter "rembg.exe" -ErrorAction SilentlyContinue
```

Example result:

```
C:\Users\IT\AppData\Roaming\Python\Python312\Scripts\rembg.exe
```

**Use this path in `.env`** (no PATH change needed):

```env
REMBG_BINARY=C:\Users\IT\AppData\Roaming\Python\Python312\Scripts\rembg.exe
```

Use your actual username and Python version in the path.

Test it directly:

```powershell
& "C:\Users\IT\AppData\Local\Programs\Python\Python312\Scripts\rembg.exe" --help
```

### Option B — Add Python Scripts folder to PATH (permanent)

1. Press **Win + R**, type `sysdm.cpl`, Enter
2. **Advanced** tab → **Environment Variables**
3. Under **User variables**, select **Path** → **Edit**
4. **New** → add your Scripts folder, e.g.:

   ```
   C:\Users\IT\AppData\Roaming\Python\Python312\Scripts
   ```

5. Also add Python root if missing:

   ```
   C:\Users\IT\AppData\Local\Programs\Python\Python312
   ```

6. OK all dialogs
7. **Close and reopen** PowerShell (and Cursor/terminal tabs)
8. Verify:

```powershell
where.exe rembg
rembg --help
```

### Option C — Add to PATH for current session only (quick test)

```powershell
$env:Path += ";C:\Users\IT\AppData\Local\Programs\Python\Python312\Scripts"
rembg --help
```

This lasts until you close that terminal.

---

## Step 4 — Test rembg on a real image

Before touching Laravel, confirm rembg produces a transparent PNG:

```powershell
rembg i "C:\path\to\your\photo.jpg" "C:\path\to\photo-no-bg.png"
```

Or with full binary path:

```powershell
& "C:\Users\IT\AppData\Local\Programs\Python\Python312\Scripts\rembg.exe" i "C:\path\to\photo.jpg" "C:\path\to\photo-no-bg.png"
```

Open `photo-no-bg.png` — background should be removed (checkerboard / transparent in most viewers).

---

## Step 5 — Configure Laravel API (`.env`)

In `multi-tenants-api`, copy from `.env.example` or add:

```env
# Background removal (rembg)
BACKGROUND_REMOVAL_DRIVER=rembg
REMBG_BINARY=rembg
REMBG_TIMEOUT=300
```

| Variable | Meaning |
|----------|---------|
| `BACKGROUND_REMOVAL_DRIVER` | Only `rembg` for now |
| `REMBG_BINARY` | Command name **or full path** to `rembg.exe` |
| `REMBG_TIMEOUT` | Max seconds per image (default 120) |

If rembg is **not** on PATH, use the full path:

```env
REMBG_BINARY=C:\Users\IT\AppData\Local\Programs\Python\Python312\Scripts\rembg.exe
```

Optional size limit (bytes, default 10 MB):

```env
BACKGROUND_REMOVAL_MAX_FILE_SIZE=10485760
```

---

## Step 6 — Queue configuration

Background removal runs in a **queue job**. Choose one mode:

### Mode A — Database queue (like production)

```env
QUEUE_CONNECTION=database
```

Ensure the jobs table exists:

```powershell
cd C:\Users\IT\Herd\multi-tenants-api
php artisan queue:table
php artisan migrate
```

Run a worker in a **separate terminal** (leave it running):

```powershell
cd C:\Users\IT\Herd\multi-tenants-api
php artisan queue:work
```

The API returns **202 Queued**; the new file appears after the worker finishes.

### Mode B — Sync queue (easiest for local dev)

```env
QUEUE_CONNECTION=sync
```

No queue worker needed. Processing runs immediately in the HTTP request and returns **201** with the new media item.

Good for debugging rembg; can feel slow on large images because the browser waits.

---

## Step 7 — Run the full stack

Open **three** terminals:

**Terminal 1 — Herd**  
API is usually already served by Herd at your tenant domain.

**Terminal 2 — Queue worker** (only if `QUEUE_CONNECTION=database`):

```powershell
cd C:\Users\IT\Herd\multi-tenants-api
php artisan queue:work
```

**Terminal 3 — Next.js frontend**:

```powershell
cd C:\development\multi-tenants-app
npm run dev
```

---

## Step 8 — Test in the admin UI

1. Log in to tenant admin
2. Open **Media**
3. Upload a JPG or PNG
4. **Right-click** the image → **AI Features** → **Remove background**
5. Wait for toast:
   - **Sync queue:** “Background removed successfully”
   - **Database queue:** “Background removal started…” then refresh / wait for polling
6. A new PNG should appear in the same folder (e.g. `product-no-bg.png`)

---

## Step 9 — Test the API directly (optional)

Replace `{domain}`, `{token}`, and `{media_id}`:

```powershell
curl -X POST "http://{tenant}.multi-tenants-api.test/api/v1/tenant/media/{media_id}/remove-background" `
  -H "Authorization: Bearer {token}" `
  -H "Accept: application/json"
```

Or run Pest tests (uses a fake remover, no rembg required):

```powershell
cd C:\Users\IT\Herd\multi-tenants-api
php artisan test --filter=TenantMediaTest
```

---

## Troubleshooting

### `rembg` is not recognized

- Use full path in `REMBG_BINARY` (Step 3 Option A)
- Or add Scripts folder to PATH (Step 3 Option B)
- Restart terminal after PATH changes

### Job never runs / nothing happens

- Is `php artisan queue:work` running?
- Is `QUEUE_CONNECTION=database` and `jobs` table migrated?
- Check `storage/logs/laravel.log`

### API error: “Background removal failed…”

- Run rembg manually on a test image (Step 4)
- Confirm `REMBG_BINARY` path in `.env` matches `where.exe rembg`
- Try: `pip install --upgrade rembg onnxruntime`

### API error: “Ensure rembg is installed”

PHP cannot execute the binary. Herd’s PHP runs as your user — test from PowerShell:

```powershell
& "C:\full\path\to\rembg.exe" --help
```

If that works but Laravel fails, fix `REMBG_BINARY` in `.env` and run:

```powershell
php artisan config:clear
```

### Slow first request

First rembg run downloads the model. Later runs are faster.

### `onnxruntime` / DLL errors on Windows

```powershell
pip uninstall onnxruntime onnxruntime-gpu -y
pip install onnxruntime
pip install --upgrade rembg
```

If it still fails, try **WSL2 + Ubuntu** with the same pip install (often more reliable for ML on Windows).

### Image too large

Increase `BACKGROUND_REMOVAL_MAX_FILE_SIZE` or use a smaller source image.

---

## AWS S3 storage (production note)

This approach **works with S3**:

1. Set `MEDIA_DISK=s3` and AWS credentials in `.env`
2. rembg still runs on your **app/worker server** (not inside S3)
3. Flow: download from S3 → process locally → upload PNG back to S3

You may need to enable `'s3'` under `filesystem.disks` in `config/tenancy.php` for tenant-prefixed paths.

The queue worker must have **rembg installed** and network access to S3.

---

## Environment reference (copy-paste)

```env
# Queue — pick one
QUEUE_CONNECTION=database
# QUEUE_CONNECTION=sync

# rembg
BACKGROUND_REMOVAL_DRIVER=rembg
REMBG_BINARY=rembg
# REMBG_BINARY=C:\Users\IT\AppData\Roaming\Python\Python312\Scripts\rembg.exe
REMBG_TIMEOUT=300
```

---

## Related code (for developers)

| Piece | Location |
|-------|----------|
| API route | `POST media/{media}/remove-background` in `routes/api/tenant.php` |
| Controller | `MediaController::removeBackground` |
| Service | `MediaService::removeBackground` |
| Queue job | `App\Jobs\Tenant\RemoveMediaBackgroundJob` |
| rembg adapter | `App\Services\Media\RembgBackgroundRemover` |
| Config | `config/background-removal.php` |
| Frontend menu | `media-ai-features-menu.tsx` in `multi-tenants-app` |

---

## Checklist

- [ ] Python 3.10+ installed
- [ ] `pip install "rembg[cli]"` succeeded
- [ ] `rembg i input.jpg output.png` works OR full path set in `REMBG_BINARY`
- [ ] `.env` has `BACKGROUND_REMOVAL_*` variables
- [ ] Queue: `sync` **or** `database` + `php artisan queue:work`
- [ ] Herd API + Next.js dev server running
- [ ] Test image → AI Features → Remove background → new PNG in library
