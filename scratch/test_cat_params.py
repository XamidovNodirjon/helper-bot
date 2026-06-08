import requests

url = "https://api.uybor.uz/api/v1/listings"
tests = [
    {"params": {"categoryId__eq": 7, "limit": 1}, "name": "categoryId__eq=7"},
    {"params": {"category__eq": 7, "limit": 1}, "name": "category__eq=7"},
    {"params": {"subCategoryId__eq": 12, "limit": 1}, "name": "subCategoryId__eq=12"},
    {"params": {"subCategory__eq": 12, "limit": 1}, "name": "subCategory__eq=12"},
]

for test in tests:
    try:
        r = requests.get(url, params=test["params"], verify=False, timeout=10)
        print(f"Test: {test['name']} -> Status: {r.status_code}, total={r.json().get('total') if r.status_code == 200 else None}")
    except Exception as e:
        print(f"Test: {test['name']} -> Error: {e}")
