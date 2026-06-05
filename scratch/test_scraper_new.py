import subprocess
import json
import sys

# Ensure stdout uses UTF-8 to prevent charmap encoding errors on Windows
if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

cmd = [
    "python", 
    "C:\\Users\\ndt\\Desktop\\Projects\\TelegramBot\\app/Services/olx_scraper.py", 
    "--category=dokon", 
    "--region=tashkent", 
    "--district=23", 
    "--price_min=100", 
    "--price_max=2000", 
    "--currency=usd", 
    "--area_min=30", 
    "--area_max=150"
]

print("Running scraper command for Dokon commercial category...")
res = subprocess.run(cmd, capture_output=True, text=True, encoding="utf-8")

if res.returncode == 0:
    try:
        data = json.loads(res.stdout)
        if 'listings' in data:
            print("Total Listings Returned:", len(data['listings']))
            print("First 3 titles:")
            for ad in data['listings'][:3]:
                print(f" - {ad.get('title')} | {ad.get('price')} | Location: {ad.get('location')}")
        else:
            print("No listings found in output JSON:", data)
    except Exception as e:
        print("JSON parse error:", e)
        print("Raw Output:", res.stdout[:500])
else:
    print("Command failed with code:", res.returncode)
    print("Stderr:", res.stderr)
