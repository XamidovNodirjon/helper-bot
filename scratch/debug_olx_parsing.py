import requests
import json
import re
from parsel import Selector

url = "https://www.olx.uz/nedvizhimost/kvartiry/arenda-dolgosrochnaya/tashkent/"
headers = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
}

def extract_json_state(html):
    # Try to find the quoted string assignment
    match = re.search(r'window\.__PRERENDERED_STATE__\s*=\s*"(.*?)";', html, re.DOTALL)
    if match:
        try:
            raw_str = match.group(0)
            rhs = raw_str.split('=', 1)[1].strip().rstrip(';')
            decoded_once = json.loads(rhs)
            return json.loads(decoded_once)
        except Exception as e:
            print("Error parsing quoted PRERENDERED_STATE:", e)

    # Try the unquoted assignment fallback
    match = re.search(r'window\.__PRERENDERED_STATE__\s*=\s*(\{.*?\});', html, re.DOTALL)
    if not match:
        match = re.search(r'window\.__PRERENDERED_STATE__\s*=\s*(\{.*?\})', html, re.DOTALL)
    if match:
        try:
            return json.loads(match.group(1))
        except Exception as e:
            print("Error parsing unquoted PRERENDERED_STATE:", e)

    return None

def find_listings_in_json(data):
    if isinstance(data, dict):
        if "listing" in data and isinstance(data["listing"], dict) and "listing" in data["listing"] and isinstance(data["listing"]["listing"], dict) and "ads" in data["listing"]["listing"]:
            return data["listing"]["listing"]["ads"]
            
        for k, v in data.items():
            if k == 'ads' and isinstance(v, list) and len(v) > 0 and isinstance(v[0], dict) and 'title' in v[0]:
                return v
            if k == 'list' and isinstance(v, list) and len(v) > 0 and isinstance(v[0], dict) and 'title' in v[0]:
                return v
            if k == 'adverts' and isinstance(v, list) and len(v) > 0 and isinstance(v[0], dict) and 'title' in v[0]:
                return v
            res = find_listings_in_json(v)
            if res:
                return res
    elif isinstance(data, list):
        for item in data:
            res = find_listings_in_json(item)
            if res:
                return res
    return None

try:
    r = requests.get(url, headers=headers, timeout=15)
    html = r.text
    
    state_data = extract_json_state(html)
    print("Has JSON state data:", state_data is not None)
    
    if state_data:
        adverts = find_listings_in_json(state_data)
        print("Total adverts found in JSON:", len(adverts) if adverts else 0)
        if adverts:
            for i, ad in enumerate(adverts[:15]):
                title = ad.get('title')
                photos = ad.get('photos', [])
                print(f"JSON Card {i}: title={title[:40]}, photos count={len(photos) if photos else 0}")
                if photos:
                    print("  First photo:", photos[0])
    
    # Let's run fallback
    selector = Selector(html)
    cards = selector.xpath('//div[@data-testid="l-card"] | //a[@data-testid="card-link"]')
    print("\nFallback Cards found:", len(cards))
    for i, card in enumerate(cards[:15]):
        title = card.css('h6::text, h4::text').get()
        img = card.css('img::attr(src), img::attr(data-src)').get()
        print(f"Fallback Card {i}: title={title[:40] if title else None}, img={img}")

except Exception as e:
    print("Error:", e)
