import requests

endpoints = [
    "https://api.uybor.uz/api/v1/categories",
    "https://api.uybor.uz/api/v1/listings/categories",
    "https://api.uybor.uz/api/v1/listings/locations",
    "https://api.uybor.uz/api/v1/listings/residential-complexes"
]

for url in endpoints:
    try:
        r = requests.get(url, verify=False, timeout=5)
        print(f"URL: {url} -> Status: {r.status_code}")
        if r.status_code == 200:
            data = r.json()
            # If it's a list, print length, if dict, keys
            if isinstance(data, list):
                print(f"  List of len {len(data)}")
                print(f"  First item: {data[0]}")
            elif isinstance(data, dict):
                print(f"  Dict with keys {data.keys()}")
                for k in list(data.keys())[:3]:
                    val = data[k]
                    if isinstance(val, list):
                        print(f"    {k}: list of len {len(val)}, first: {val[0] if val else None}")
                    else:
                        print(f"    {k}: {val}")
    except Exception as e:
        print(f"URL: {url} -> Error: {e}")
