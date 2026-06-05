import requests
import json
import re

url = "https://www.olx.uz/nedvizhimost/kommercheskie-pomeshcheniya/arenda/tashkent/"
headers = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Accept-Language': 'uz-UZ,uz;q=0.9,ru;q=0.8,en;q=0.7'
}

response = requests.get(url, headers=headers)
html = response.text

match = re.search(r'window\.__PRERENDERED_STATE__\s*=\s*"(.*?)";', html, re.DOTALL)
if match:
    rhs = match.group(0).split('=', 1)[1].strip().rstrip(';')
    state_dict = json.loads(json.loads(rhs))
    filters = state_dict.get('listing', {}).get('filters', {})
    data = filters.get('data', {})
    premise_type = data.get('filter_enum_premise_type')
    with open("c:\\Users\\ndt\\Desktop\\Projects\\TelegramBot\\scratch\\premise_type.json", "w", encoding="utf-8") as f:
        json.dump(premise_type, f, indent=2, ensure_ascii=False)
    print("Saved to scratch/premise_type.json")
else:
    print("Prerendered state not found")
