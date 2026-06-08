import requests

url = "https://api.uybor.uz/api/v1/listings"
headers = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
}

tests = [
    {"params": {"price__gte": 100, "price__lte": 300, "operationType__eq": "rent", "priceCurrency__eq": "usd"}, "name": "Rent 100-300 USD"},
    {"params": {"price__gte": 800, "price__lte": 900, "operationType__eq": "rent", "priceCurrency__eq": "usd"}, "name": "Rent 800-900 USD"},
    {"params": {"price__gte": 50000, "price__lte": 100000, "operationType__eq": "sale", "priceCurrency__eq": "usd"}, "name": "Sale 50K-100K USD"},
]

for test in tests:
    try:
        r = requests.get(url, params=test["params"], headers=headers, verify=False, timeout=10)
        if r.status_code == 200:
            print(f"Test: {test['name']} -> total={r.json().get('total')}")
    except Exception as e:
        print(f"Error for {test['name']}: {e}")
