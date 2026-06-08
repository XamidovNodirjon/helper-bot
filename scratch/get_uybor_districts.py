import requests
import json

url = "https://api.uybor.uz/api/v1/listings/locations"
# Tashkent is region 13. Let's query locations with parentId__eq=13, type__eq=district
params = {
    'parentId__eq': 13,
    'type__eq': 'district',
    'limit': 100
}

try:
    r = requests.get(url, params=params, verify=False, timeout=10)
    if r.status_code == 200:
        data = r.json()
        results = data.get('results', [])
        print("Districts found under Tashkent:", len(results))
        for item in results:
            print(f"ID: {item.get('id')} | Name (ru): {item.get('name', {}).get('ru')} | Name (uz): {item.get('name', {}).get('uz')} | Name (en): {item.get('name', {}).get('en')}")
except Exception as e:
    print("Error:", e)
