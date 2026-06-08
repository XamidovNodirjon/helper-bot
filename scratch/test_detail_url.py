import requests

urls = [
    "https://uybor.uz/ru/listings/1315639",
    "https://uybor.uz/ru/listing/1315639",
]

for url in urls:
    try:
        r = requests.get(url, verify=False, allow_redirects=True, timeout=5)
        print(f"URL: {url} -> Status: {r.status_code}, Final URL: {r.url}")
    except Exception as e:
        print(f"Error for {url}: {e}")
