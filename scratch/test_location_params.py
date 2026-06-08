import requests

url = "https://api.uybor.uz/api/v1/listings/locations"
tests = [
    {"params": {"type": "district", "limit": 5}, "name": "type=district"},
    {"params": {"type__eq": "district", "limit": 5}, "name": "type__eq=district"},
    {"params": {"parentId": 13, "limit": 5}, "name": "parentId=13"},
    {"params": {"parentId__eq": 13, "limit": 5}, "name": "parentId__eq=13"},
]

for test in tests:
    try:
        r = requests.get(url, params=test["params"], verify=False, timeout=5)
        if r.status_code == 200:
            data = r.json()
            print(f"Test: {test['name']} -> total={data.get('total')}")
            results = data.get('results', [])
            if results:
                print(f"  First: id={results[0].get('id')}, type={results[0].get('type')}, parentId={results[0].get('parentId')}, name={results[0].get('name', {}).get('ru') or results[0].get('name', {}).get('en')}")
    except Exception as e:
        print(f"Test: {test['name']} -> Error: {e}")
