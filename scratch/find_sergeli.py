import requests

url = "https://api.uybor.uz/api/v1/listings/locations"
# Let's request type=district with a large limit, say 200, and look for Tashkent districts.
# Standard Tashkent districts might have parentId under some specific node, or regionId, or we can just filter by their English/Russian names.
params = {
    'type': 'district',
    'limit': 1000
}

try:
    r = requests.get(url, params=params, verify=False, timeout=15)
    if r.status_code == 200:
        data = r.json()
        results = data.get('results', [])
        print("Total fetched locations:", len(results))
        
        keywords = ["sergeli", "olmazor", "almazar", "yashnabad", "yashnobod", "sergeli", "сергели", "алмазар", "яшнабад"]
        for item in results:
            name_ru = str(item.get('name', {}).get('ru') or '').lower()
            name_uz = str(item.get('name', {}).get('uz') or '').lower()
            name_en = str(item.get('name', {}).get('en') or '').lower()
            
            # Check if any keyword matches
            if any(k in name_ru or k in name_uz or k in name_en for k in keywords):
                print(f"MATCH: ID={item.get('id')} | parentId={item.get('parentId')} | ru={item.get('name', {}).get('ru')} | uz={item.get('name', {}).get('uz')} | en={item.get('name', {}).get('en')}")
except Exception as e:
    print("Error:", e)
