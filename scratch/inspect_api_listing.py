import json

with open("scratch/uybor_api_response.json", "r", encoding="utf-8") as f:
    data = json.load(f)

results = data.get('results', [])
print("Total results in file:", len(results))

if len(results) > 0:
    first_item = results[0]
    print("\n--- FIRST ITEM DETAIL ---")
    print("id:", first_item.get('id'))
    print("operationType:", first_item.get('operationType'))
    print("categoryId:", first_item.get('categoryId'))
    print("category name (en):", first_item.get('category', {}).get('name', {}).get('en'))
    print("subCategoryId:", first_item.get('subCategoryId'))
    print("subCategory name (en):", first_item.get('subCategory', {}).get('name', {}).get('en'))
    print("description (first 100 chars):", first_item.get('description', '')[:100].replace('\n', ' '))
    print("price:", first_item.get('price'))
    print("priceCurrency:", first_item.get('priceCurrency'))
    print("region name (en):", first_item.get('region', {}).get('name', {}).get('en'))
    print("district name (en):", first_item.get('district', {}).get('name', {}).get('en'))
    print("media structure:", type(first_item.get('media')))
    
    media = first_item.get('media', [])
    if isinstance(media, list) and len(media) > 0:
        print("First media item detail:")
        for k, v in media[0].items():
            print(f"  {k}: {v}")
            
    # Let's check how the URL on the website is formed for a listing
    # For example, on the main website, a listing page is: https://uybor.uz/ru/listing/1315639
    # Wait, let's verify if that URL works or if it is https://uybor.uz/ru/listing/{id}
    # Yes, we saw chrome URL in the screenshot was: https://www.uybor.uz/listing/100002 or https://uybor.uz/ru/listing/{id}
