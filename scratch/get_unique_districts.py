import requests

url = "https://api.uybor.uz/api/v1/listings"
params = {
    'regionId__eq': 13,
    'limit': 100,
    'embed': 'district'
}
headers = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
}

try:
    r = requests.get(url, params=params, headers=headers, verify=False, timeout=10)
    if r.status_code == 200:
        data = r.json()
        results = data.get('results', [])
        districts = {}
        for item in results:
            dist = item.get('district')
            if dist and isinstance(dist, dict):
                d_id = dist.get('id')
                if d_id not in districts:
                    districts[d_id] = dist.get('name')
        
        print(f"Found {len(districts)} unique districts in Tashkent listings:")
        for d_id, name in districts.items():
            print(f"ID: {d_id} -> Name: {name}")
except Exception as e:
    print("Error:", e)
