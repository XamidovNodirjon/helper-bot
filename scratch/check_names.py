import requests

url = "https://api.uybor.uz/api/v1/listings/locations"
ids = [433, 434, 674731, 677167]

for i in ids:
    try:
        # We can query locations with parentId=13 or query them individually if we can.
        # But wait, we can just query the endpoint and filter by id on the client side!
        params = {'type': 'district', 'limit': 1000}
        r = requests.get(url, params=params, verify=False, timeout=10)
        if r.status_code == 200:
            for item in r.json().get('results', []):
                if item.get('id') == i:
                    print(f"ID: {i} -> name (en): {item.get('name', {}).get('en')}")
                    print(f"          name (uz): {item.get('name', {}).get('uz')}")
                    print(f"          name (ru): {item.get('name', {}).get('ru')}")
    except Exception as e:
        print(f"Error for {i}: {e}")
