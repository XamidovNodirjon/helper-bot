import requests
import json

url = "https://api.uybor.uz/api/v1/listings"
headers = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Accept': 'application/json, text/plain, */*',
    'Origin': 'https://uybor.uz',
    'Referer': 'https://uybor.uz/'
}

# Let's start with basic query parameters
# We saw: limit: c.UZ (which is probably 20), embed: category,subCategory,residentialComplex,region,city,district,zone,street,metro,media,user,user.avatar,user.organization,user.organization.logo
params = {
    'limit': 10,
    'embed': 'category,subCategory,region,city,district,media',
}

try:
    response = requests.get(url, params=params, headers=headers, verify=False, timeout=15)
    print("Status:", response.status_code)
    print("Content-Type:", response.headers.get('Content-Type'))
    
    if response.status_code == 200:
        data = response.json()
        print("Data keys:", data.keys())
        if 'data' in data:
            print("Total listings fetched in data array:", len(data['data']))
            if len(data['data']) > 0:
                # Let's save the first listing to inspect its fields
                print("First listing fields:")
                for k, v in data['data'][0].items():
                    # If it's a dict or list, print structure
                    if isinstance(v, (dict, list)):
                        print(f"  {k}: type={type(v)}, len/keys={len(v)}")
                    else:
                        print(f"  {k}: {v}")
                
                # Save full json to scratch
                with open("scratch/uybor_api_response.json", "w", encoding="utf-8") as f:
                    json.dump(data, f, indent=2, ensure_ascii=False)
        else:
            print("Response text preview:")
            print(response.text[:1000])
    else:
        print("Response text:", response.text)

except Exception as e:
    print("Error:", e)
