import requests
import re
import json

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

for page in [1, 2, 3]:
    url = f"https://www.olx.uz/nedvizhimost/kvartiry/arenda-dolgosrochnaya/tashkent/?page={page}"
    try:
        r = requests.get(url, headers=headers, timeout=10)
        state_data = extract_json_state(r.text)
        print(f"Page {page} -> Succeeded: {state_data is not None}")
    except Exception as e:
        print(f"Page {page} -> Error: {e}")
