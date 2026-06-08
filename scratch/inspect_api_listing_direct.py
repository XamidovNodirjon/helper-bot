import requests
import json

url = "https://api.uybor.uz/api/v1/listings"
headers = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Accept': 'application/json, text/plain, */*',
    'Origin': 'https://uybor.uz',
    'Referer': 'https://uybor.uz/'
}

params = {
    'limit': 5,
    'embed': 'category,subCategory,region,city,district,media',
}

try:
    response = requests.get(url, params=params, headers=headers, verify=False, timeout=15)
    data = response.json()
    
    with open("scratch/uybor_api_response.json", "w", encoding="utf-8") as f:
        json.dump(data, f, indent=2, ensure_ascii=False)
        
    results = data.get('results', [])
    print("Total results in file:", len(results))

    if len(results) > 0:
        first_item = results[0]
        print("\n--- FIRST ITEM DETAIL ---")
        print("id:", first_item.get('id'))
        print("operationType:", first_item.get('operationType'))
        print("categoryId:", first_item.get('categoryId'))
        print("category name:", first_item.get('category'))
        print("subCategoryId:", first_item.get('subCategoryId'))
        print("subCategory name:", first_item.get('subCategory'))
        print("description (first 150 chars):", first_item.get('description', '')[:150].replace('\n', ' '))
        print("price:", first_item.get('price'))
        print("priceCurrency:", first_item.get('priceCurrency'))
        print("region name:", first_item.get('region'))
        print("district name:", first_item.get('district'))
        print("media structure:", type(first_item.get('media')))
        
        media = first_item.get('media', [])
        if isinstance(media, list) and len(media) > 0:
            print("First media item keys:", media[0].keys())
            for k, v in media[0].items():
                print(f"  {k}: {v}")

except Exception as e:
    print("Error:", e)
