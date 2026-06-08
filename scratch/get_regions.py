import requests

url = "https://api.uybor.uz/api/v1/listings/locations"
params = {
    'type': 'region',
    'limit': 100
}
headers = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
}

try:
    r = requests.get(url, params=params, headers=headers, verify=False, timeout=10)
    if r.status_code == 200:
        data = r.json()
        results = data.get('results', [])
        print("Total regions found:", len(results))
        for item in results:
            print(f"ID: {item.get('id')} | Name (uz): {item.get('name', {}).get('uz')} | Name (ru): {item.get('name', {}).get('ru')} | Name (en): {item.get('name', {}).get('en')}")
except Exception as e:
    print("Error:", e)
