import requests
import json
import re
import sys

# Ensure stdout uses UTF-8 to prevent charmap encoding errors on Windows
if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

headers = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Accept-Language': 'uz-UZ,uz;q=0.9,ru;q=0.8,en;q=0.7'
}

def analyze_url(url, label):
    response = requests.get(url, headers=headers)
    html = response.text
    print(f"\n=== Analyzing {label} ===")
    print("URL:", url)
    
    match = re.search(r'window\.__PRERENDERED_STATE__\s*=\s*"(.*?)";', html, re.DOTALL)
    if match:
        rhs = match.group(0).split('=', 1)[1].strip().rstrip(';')
        state_dict = json.loads(json.loads(rhs))
        if 'listing' in state_dict and 'listing' in state_dict['listing']:
            inner = state_dict['listing']['listing']
            ads = inner.get('ads', [])
            print(f"Total ads: {len(ads)}")
            print("totalElements:", inner.get('totalElements'))
            for idx, ad in enumerate(ads[:5]):
                print(f"  {idx+1}. {ad.get('title')} | Price: {ad.get('price', {}).get('displayValue')}")
    else:
        print("Prerendered state not found")

# Test 1: Using search[q]=ofis (Current text search)
analyze_url(
    "https://www.olx.uz/nedvizhimost/kommercheskie-pomeshcheniya/arenda/tashkent/?search%5Bq%5D=ofis",
    "Text search search[q]=ofis"
)

# Test 2: Using search[filter_enum_premise_type][0]=4 (Enum filter)
analyze_url(
    "https://www.olx.uz/nedvizhimost/kommercheskie-pomeshcheniya/arenda/tashkent/?search%5Bfilter_enum_premise_type%5D%5B0%5D=4",
    "Enum filter search[filter_enum_premise_type][0]=4"
)
