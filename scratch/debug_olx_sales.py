import requests
import json
import re
from parsel import Selector

url = "https://www.olx.uz/nedvizhimost/kvartiry/prodazha/tashkent/"
params = {
    'search[district_id]': 23
}
headers = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
}

def extract_json_state(html):
    match = re.search(r'window\.__PRERENDERED_STATE__\s*=\s*"(.*?)";', html, re.DOTALL)
    if match:
        try:
            raw_str = match.group(0)
            rhs = raw_str.split('=', 1)[1].strip().rstrip(';')
            decoded_once = json.loads(rhs)
            return json.loads(decoded_once)
        except:
            pass
    match = re.search(r'window\.__PRERENDERED_STATE__\s*=\s*(\{.*?\});', html, re.DOTALL)
    if not match:
        match = re.search(r'window\.__PRERENDERED_STATE__\s*=\s*(\{.*?\})', html, re.DOTALL)
    if match:
        try:
            return json.loads(match.group(1))
        except:
            pass
    return None

def find_listings_in_json(data):
    if isinstance(data, dict):
        for k, v in data.items():
            if k == 'ads' and isinstance(v, list) and len(v) > 0 and isinstance(v[0], dict) and 'title' in v[0]:
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
    r = requests.get(url, params=params, headers=headers, timeout=15)
    html = r.text
    state_data = extract_json_state(html)
    adverts = find_listings_in_json(state_data) if state_data else None
    
    print("Has JSON state data:", state_data is not None)
    print("Adverts in JSON count:", len(adverts) if adverts else 0)
    
    # Let's see if any ads were skipped by district Id filtering
    if adverts:
        matched_count = 0
        for idx, ad in enumerate(adverts):
            title = ad.get('title')
            loc = ad.get('location', {})
            district_id = str(loc.get('districtId') or '')
            if district_id == '23':
                matched_count += 1
                photos = ad.get('photos', [])
                print(f"JSON MATCH {idx}: title={title[:30]}... | districtId={district_id} | photos count={len(photos)}")
        print("Total JSON matches:", matched_count)
        
    # Let's run fallback
    selector = Selector(html)
    cards = selector.xpath('//div[@data-testid="l-card"] | //a[@data-testid="card-link"]')
    print("\nFallback Cards found:", len(cards))
    for i, card in enumerate(cards[:15]):
        title = card.css('h6::text, h4::text').get()
        img = card.css('img::attr(src), img::attr(data-src)').get()
        print(f"Fallback Card {i}: title={title[:30] if title else None}... | img={img}")

except Exception as e:
    print("Error:", e)
