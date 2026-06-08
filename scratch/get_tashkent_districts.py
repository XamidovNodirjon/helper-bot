import requests

url = "https://api.uybor.uz/api/v1/listings/locations"
params = {
    'parentId__eq': 13,
    'type__eq': 'district',
    'limit': 100
}

try:
    r = requests.get(url, params=params, verify=False, timeout=10)
    print("Status:", r.status_code)
    if r.status_code == 200:
        data = r.json()
        print("Total Tashkent districts:", data.get('total'))
        for item in data.get('results', []):
            print(f"ID: {item.get('id')} | Name (ru): {item.get('name', {}).get('ru')} | Name (uz): {item.get('name', {}).get('uz')} | Name (en): {item.get('name', {}).get('en')}")
except Exception as e:
    print("Error:", e)
