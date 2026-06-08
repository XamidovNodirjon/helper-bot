import requests

domains = [
    "https://uybor.uz",
    "https://api.uybor.uz",
    "https://www.uybor.uz"
]

for dom in domains:
    try:
        r = requests.get(dom, timeout=5)
        print(f"{dom} -> Success! Status: {r.status_code}")
    except Exception as e:
        print(f"{dom} -> Error: {e}")
