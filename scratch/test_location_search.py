import requests

url = "https://api.uybor.uz/api/v1/listings/locations"
search_params = [
    {"q": "Sergeli"},
    {"search": "Sergeli"},
    {"name": "Sergeli"},
    {"name__contains": "Sergeli"},
    {"name__icontains": "Sergeli"},
]

for p in search_params:
    try:
        p["type"] = "district"
        p["limit"] = 5
        r = requests.get(url, params=p, verify=False, timeout=5)
        if r.status_code == 200:
            data = r.json()
            print(f"Params: {p} -> total={data.get('total')}")
            results = data.get('results', [])
            if results:
                print(f"  First result: id={results[0].get('id')}, name={results[0].get('name')}")
    except Exception as e:
        print(f"Error with {p}: {e}")
