import subprocess
import json
import sys

# Ensure stdout uses UTF-8
if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

# Run command to fetch real estate sales in Tashkent, Chilanzar
cmd = [
    "python",
    "app/Services/olx_scraper.py",
    "--category=uy",
    "--region=tashkent",
    "--district=23", # Chilanzar
    "--price_min=50000",
    "--price_max=100000",
    "--currency=usd",
    "--deal_type=sotuv"
]

try:
    print("Running scraper command...")
    res = subprocess.run(cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, encoding='utf-8', timeout=30)
    print("Return code:", res.returncode)
    
    if res.returncode == 0:
        data = json.loads(res.stdout)
        listings = data.get('listings', [])
        print("Total listings retrieved:", len(listings))
        
        # Let's count sources
        sources = {}
        for item in listings:
            src = item.get('source')
            sources[src] = sources.get(src, 0) + 1
            
        print("Sources count:", sources)
        
        # Print first few listings from each source
        for src in sources:
            print(f"\n--- SAMPLE FROM SOURCE: {src} ---")
            sample_items = [x for x in listings if x.get('source') == src][:2]
            for item in sample_items:
                print(f"  Title: {item.get('title')}")
                print(f"  URL: {item.get('url')}")
                print(f"  Price: {item.get('price')}")
                print(f"  Location: {item.get('location')}")
                print(f"  Images count: {len(item.get('images', []))}")
                if item.get('images'):
                    print(f"    First Image: {item.get('images')[0]}")
                print()
    else:
        print("Scraper Error Output:")
        print(res.stderr)
        
except Exception as e:
    print("Error:", e)
