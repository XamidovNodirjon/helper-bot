import requests

url = "https://api.uybor.uz/api/v1/listings/locations/203"
try:
    r = requests.get(url, verify=False, timeout=10)
    print("Status:", r.status_code)
    if r.status_code == 200:
        data = r.json()
        print("Data:")
        for k, v in data.items():
            print(f"  {k}: {v}")
except Exception as e:
    print("Error:", e)
