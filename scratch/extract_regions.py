import json

with open("scratch/regions_mapped.json", "r", encoding="utf-8") as f:
    data = json.load(f)

for slug, items in data.items():
    print(f"--- {slug} ---")
    # Filter for items with small IDs or parentId=1 or None
    filtered = [item for item in items if item.get('id', 0) < 1000]
    if not filtered:
        # Fallback to items where type is 'region' and parentId is 1 or None
        filtered = [item for item in items if item.get('type') == 'region' and item.get('parentId') in [1, None]]
        
    for item in filtered[:5]:
        print(f"  ID: {item.get('id')} | parentId: {item.get('parentId')} | type: {item.get('type')} | en: {item.get('en')} | uz: {item.get('uz')}")
