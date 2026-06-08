import requests

url = "https://api.uybor.uz/api/v1/listings/locations"
params = {'limit': 100}
try:
    r = requests.get(url, params=params, verify=False, timeout=10)
    if r.status_code == 200:
        results = r.json().get('results', [])
        for item in results:
            if item.get('id') == 13:
                print("FOUND ID 13:")
                for k, v in item.items():
                    print(f"  {k}: {v}")
except Exception as e:
    print("Error:", e)
