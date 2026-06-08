import requests

url = "https://api.uybor.uz/api/v1/listings"
headers = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
}

tests = [
    {"params": {"region__eq": 13, "limit": 1}, "name": "region__eq=13 (Tashkent city)"},
    {"params": {"region__eq": 12, "limit": 1}, "name": "region__eq=12 (Tashkent region)"},
]

for test in tests:
    try:
        r = requests.get(url, params=test["params"], headers=headers, verify=False, timeout=10)
        if r.status_code == 200:
            data = r.json()
            print(f"Test: {test['name']} -> total={data.get('total')}")
            results = data.get('results', [])
            if results:
                reg = results[0].get('region', {})
                print(f"  First item: id={results[0].get('id')}, region={reg.get('id')} ({reg.get('name', {}).get('en')})")
    except Exception as e:
        print(f"Error for {test['name']}: {e}")
