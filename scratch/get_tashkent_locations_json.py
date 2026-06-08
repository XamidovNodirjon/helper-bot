import requests
import json

url = "https://api.uybor.uz/api/v1/listings/locations"
params = {
    'parentId': 13,
    'limit': 100
}

try:
    r = requests.get(url, params=params, verify=False, timeout=10)
    if r.status_code == 200:
        data = r.json()
        results = data.get('results', [])
        
        # Save as JSON with ensure_ascii=True to see unicode escapes
        with open("scratch/tashkent_locations_escaped.json", "w", encoding="utf-8") as f:
            json.dump(results, f, indent=2, ensure_ascii=True)
            
        print("Successfully saved Tashkent locations. Total:", len(results))
        for item in results:
            name_en = item.get('name', {}).get('en')
            name_uz = item.get('name', {}).get('uz')
            print(f"ID: {item.get('id')} | type: {item.get('type')} | en: {name_en} | uz: {name_uz}")
except Exception as e:
    print("Error:", e)
