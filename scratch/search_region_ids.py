import requests
import json

url = "https://api.uybor.uz/api/v1/listings/locations"
# We want to fetch all regions (type=region) and find their IDs by checking their names.
# Wait, let's query limit=100 or limit=500
params = {
    'type': 'region',
    'limit': 200
}

try:
    r = requests.get(url, params=params, verify=False, timeout=10)
    if r.status_code == 200:
        results = r.json().get('results', [])
        print("Total regions found:", len(results))
        
        region_map = {}
        for item in results:
            name_en = str(item.get('name', {}).get('en') or '').lower()
            name_ru = str(item.get('name', {}).get('ru') or '').lower()
            name_uz = str(item.get('name', {}).get('uz') or '').lower()
            
            # Print if it matches some keywords
            keywords = ["tashkent", "bukhara", "buxoro", "andizhan", "andijon", "samarkand", "samarqand", "namangan", "fergana", "fargona", "toshkent"]
            if any(k in name_en or k in name_ru or k in name_uz for k in keywords):
                print(f"Region MATCH: ID={item.get('id')} | en={item.get('name', {}).get('en')} | uz={item.get('name', {}).get('uz')} | ru={item.get('name', {}).get('ru')}")
except Exception as e:
    print("Error:", e)
