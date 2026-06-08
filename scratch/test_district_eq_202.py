import requests

url = "https://api.uybor.uz/api/v1/listings"
params = {
    'district__eq': 202,  # Chilanzar
    'limit': 5,
    'embed': 'district'
}
headers = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
}

try:
    r = requests.get(url, params=params, verify=False, timeout=10)
    print("Status:", r.status_code)
    if r.status_code == 200:
        data = r.json()
        print("Total count with district__eq=202:", data.get('total'))
        results = data.get('results', [])
        for idx, item in enumerate(results):
            dist = item.get('district', {})
            print(f"Listing {idx}: id={item.get('id')}, district={dist.get('id')} ({dist.get('name', {}).get('en')})")
except Exception as e:
    print("Error:", e)
