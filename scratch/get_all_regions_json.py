import requests
import json

url = "https://api.uybor.uz/api/v1/listings/locations"

regions_to_find = {
    'tashkent': ['tashkent', 'тошкент', 'ташкент'],
    'samarkand': ['samarkand', 'samarqand', 'самарканд'],
    'fergana': ['fergana', 'fargona', 'фергана'],
    'andijon': ['andijan', 'andijon', 'андижан'],
    'buxoro': ['bukhara', 'buxoro', 'бухара'],
    'namangan': ['namangan', 'наманган'],
    'navoi': ['navoi', 'navoiy', 'навои'],
    'karshi': ['qashqadaryo', 'kashkadarya', 'karshi', 'qarshi', 'карши'],
    'termez': ['surxondaryo', 'surkhandarya', 'termez', 'термез'],
    'gulistan': ['sirdaryo', 'syrdarya', 'gulistan', 'гулистан'],
    'dzhizak': ['jizzax', 'djizzak', 'jizzakh', 'джизак'],
    'urgench': ['xorazm', 'khorezm', 'urgench', 'ургенч'],
    'nukus': ['karakalpakstan', 'qoraqalpogiston', 'nukus', 'нукус']
}

found_map = {}

for page in range(1, 10):
    try:
        params = {
            'limit': 500,
            'page': page
        }
        r = requests.get(url, params=params, verify=False, timeout=10)
        if r.status_code == 200:
            results = r.json().get('results', [])
            if not results:
                break
            for item in results:
                name_en = str(item.get('name', {}).get('en') or '').lower()
                name_ru = str(item.get('name', {}).get('ru') or '').lower()
                name_uz = str(item.get('name', {}).get('uz') or '').lower()
                
                # Check for regions/cities
                if item.get('type') not in ['region', 'city']:
                    continue
                    
                for slug, keywords in regions_to_find.items():
                    if any(k in name_en or k in name_ru or k in name_uz for k in keywords):
                        # Save
                        if slug not in found_map:
                            found_map[slug] = []
                        found_map[slug].append({
                            'id': item.get('id'),
                            'type': item.get('type'),
                            'parentId': item.get('parentId'),
                            'en': item.get('name', {}).get('en'),
                            'uz': item.get('name', {}).get('uz'),
                            'ru': item.get('name', {}).get('ru')
                        })
    except Exception as e:
        print(f"Error on page {page}: {e}")

# Save to scratch/regions_mapped.json
with open("scratch/regions_mapped.json", "w", encoding="utf-8") as f:
    json.dump(found_map, f, indent=2, ensure_ascii=False)

print("Saved mapped regions to scratch/regions_mapped.json")
