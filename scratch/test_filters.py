import requests

url = "https://api.uybor.uz/api/v1/listings"
headers = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Accept': 'application/json, text/plain, */*',
}

tests = [
    # Test 1: Category Apartment (7), Rent
    {"params": {"categoryId__eq": 7, "operationType__eq": "rent", "limit": 1}, "name": "Category 7 (Apartment) + Rent"},
    # Test 2: Category House (8), Sale
    {"params": {"categoryId__eq": 8, "operationType__eq": "sale", "limit": 1}, "name": "Category 8 (House) + Sale"},
    # Test 3: Region Tashkent (13)
    {"params": {"regionId__eq": 13, "limit": 1}, "name": "Region 13 (Tashkent)"},
    # Test 4: District filter (let's use a district id like 203 which we saw)
    {"params": {"districtId__eq": 203, "limit": 1}, "name": "District 203 (Uchtepa)"},
    # Test 5: Price filtering (price__gte and price__lte)
    {"params": {"price__gte": 500, "price__lte": 1000, "operationType__eq": "rent", "priceCurrency__eq": "usd", "limit": 1}, "name": "Rent 500-1000 USD"},
]

for test in tests:
    try:
        r = requests.get(url, params=test["params"], headers=headers, verify=False, timeout=10)
        print(f"Test: {test['name']} -> Status: {r.status_code}")
        if r.status_code == 200:
            data = r.json()
            print(f"  Total count: {data.get('total')}")
            results = data.get('results', [])
            if results:
                item = results[0]
                print(f"  Sample item: id={item.get('id')}, title={item.get('description', '')[:50].replace('\n', ' ')}, price={item.get('price')} {item.get('priceCurrency')}")
    except Exception as e:
        print(f"Test: {test['name']} -> Error: {e}")
