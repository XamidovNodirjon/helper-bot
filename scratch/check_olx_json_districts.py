import requests
import json
import re

url = "https://www.olx.uz/nedvizhimost/kvartiry/arenda-dolgosrochnaya/tashkent/"
# Let's filter by Chilanzar on OLX (district ID is 23)
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
    
    print("Adverts in JSON count:", len(adverts) if adverts else 0)
    if adverts:
        for idx, ad in enumerate(adverts):
            title = ad.get('title')
            loc = ad.get('location', {})
            district_id = loc.get('districtId')
            city_id = loc.get('cityId')
            print(f"Ad {idx}: title={title[:40]} | districtId={district_id} | cityId={city_id}")
except Exception as e:
    print("Error:", e)
