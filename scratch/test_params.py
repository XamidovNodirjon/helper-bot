import requests
import json
import re
import sys

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

headers = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Accept-Language': 'uz-UZ,uz;q=0.9,ru;q=0.8,en;q=0.7'
}

url = "https://www.olx.uz/nedvizhimost/kvartiry/arenda-dolgosrochnaya/tashkent/?search%5Bdistrict_id%5D=23&search%5Bfilter_float_price%3Afrom%5D=5985000&search%5Bfilter_float_price%3Ato%5D=11970000"

res = requests.get(url, headers=headers)
print("Status Code:", res.status_code)

match = re.search(r'window\.__PRERENDERED_STATE__\s*=\s*"(.*?)";', res.text, re.DOTALL)
if match:
    rhs = match.group(0).split('=', 1)[1].strip().rstrip(';')
    state_dict = json.loads(json.loads(rhs))
    inner = state_dict.get('listing', {}).get('listing', {})
    ads = inner.get('ads', [])
    print("Total ads found:", len(ads))
    print("totalElements:", inner.get('totalElements'))
    for idx, ad in enumerate(ads[:5]):
        print(f"  {idx+1}. {ad.get('title')} | Price: {ad.get('price', {}).get('displayValue')}")
else:
    print("Prerendered state not found")
