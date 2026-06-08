import requests
import json

url = "https://api.uybor.uz/api/v1/listings/locations"
params = {
    'type': 'district',
    'parentId': 13,
    'limit': 100
}

try:
    r = requests.get(url, params=params, verify=False, timeout=10)
    if r.status_code == 200:
        data = r.json()
        results = data.get('results', [])
        print("Tashkent districts found:", len(results))
        districts_list = []
        for item in results:
            d_info = {
                'id': item.get('id'),
                'name_ru': item.get('name', {}).get('ru'),
                'name_uz': item.get('name', {}).get('uz'),
                'name_en': item.get('name', {}).get('en'),
            }
            districts_list.append(d_info)
            print(f"ID: {d_info['id']} | ru: {d_info['name_ru']} | uz: {d_info['name_uz']} | en: {d_info['name_en']}")
            
        # Let's save them to a json file
        with open("scratch/tashkent_districts.json", "w", encoding="utf-8") as f:
            json.dump(districts_list, f, indent=2, ensure_ascii=False)
except Exception as e:
    print("Error:", e)
