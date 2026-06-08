import requests

url = "https://api.uybor.uz/api/v1/listings/locations"
params = {
    'parentId': 1,
    'limit': 100
}

try:
    r = requests.get(url, params=params, verify=False, timeout=10)
    if r.status_code == 200:
        results = r.json().get('results', [])
        print("Locations under parentId 1:", len(results))
        for item in results:
            print(f"ID: {item.get('id')} | type: {item.get('type')} | en: {item.get('name', {}).get('en')} | uz: {item.get('name', {}).get('uz')}")
except Exception as e:
    print("Error:", e)
