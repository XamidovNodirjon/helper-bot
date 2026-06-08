import requests
import json

url = "https://api.uybor.uz/api/v1/listings/categories"
params = {'limit': 100}
try:
    r = requests.get(url, params=params, verify=False, timeout=10)
    if r.status_code == 200:
        data = r.json()
        results = data.get('results', [])
        print("Total categories:", len(results))
        for item in results:
            print(f"ID: {item.get('id')} | ParentID: {item.get('parentId')} | Name (en): {item.get('name', {}).get('en')} | Name (uz): {item.get('name', {}).get('uz')} | Name (ru): {item.get('name', {}).get('ru')} | operationTypes: {item.get('operationTypes')}")
except Exception as e:
    print("Error:", e)
