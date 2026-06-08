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

# Let's search regions and cities
for page in [1, 2, 3, 4, 5]:
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
                
                # We only want type 'region' or 'city'
                if item.get('type') not in ['region', 'city']:
                    continue
                    
                for slug, keywords in regions_to_find.items():
                    if any(k in name_en or k in name_ru or k in name_uz for k in keywords):
                        # Avoid duplicates
                        existing = found_map.get(slug, [])
                        # We want the highest-level region/city or specific match.
                        # Let's save all matches first.
                        item_info = {
                            'id': item.get('id'),
                            'type': item.get('type'),
                            'parentId': item.get('parentId'),
                            'en': item.get('name', {}).get('en'),
                            'uz': item.get('name', {}).get('uz'),
                            'ru': item.get('name', {}).get('ru')
                        }
                        if item_info not in existing:
                            existing.append(item_info)
                        found_map[slug] = existing
    except Exception as e:
        print(f"Error on page {page}: {e}")

# Let's print the matches
print("MATCHES:")
for slug, items in found_map.items():
    print(f"\n--- {slug.upper()} ---")
    for item in items:
         print(f"  ID: {item['id']} | type: {item['type']} | parentId: {item['parentId']} | en: {item['en']} | uz: {item['uz']} | ru: {item['ru']}")
