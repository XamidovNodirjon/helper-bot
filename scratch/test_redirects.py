import requests

urls = [
    "https://uybor.uz",
    "https://uybor.uz/ru",
    "https://www.uybor.uz",
    "https://www.uybor.uz/ru",
    "https://www.uybor.uz/ru/",
]

for url in urls:
    try:
        r = requests.get(url, verify=False, allow_redirects=True, timeout=10)
        print(f"URL: {url}")
        print(f"  Final URL: {r.url}")
        print(f"  Status Code: {r.status_code}")
        print(f"  History: {r.history}")
        print(f"  Length: {len(r.text)}")
    except Exception as e:
        print(f"URL: {url} -> Error: {e}")
