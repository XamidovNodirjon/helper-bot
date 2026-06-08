import requests
import re

url = "https://uybor.uz/_next/static/chunks/pages/listings-d9261cd53ada5c70.js"
headers = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Accept': '*/*',
    'Connection': 'keep-alive'
}

try:
    response = requests.get(url, headers=headers, verify=False, timeout=15)
    print("Status:", response.status_code)
    print("Length:", len(response.text))
    
    # Let's save it to inspect
    with open("scratch/listings_chunk.js", "w", encoding="utf-8") as f:
        f.write(response.text)
        
    # Find all API-like paths (e.g. starting with /api/ or having http/https in it)
    paths = re.findall(r'"(/api/[^"]+)"|\'(/api/[^\']+)\'', response.text)
    print("API-like paths found:", len(paths))
    for p in paths[:20]:
        print(" - Path:", p)
        
    # Find any references to diru
    diru_refs = re.findall(r'[^\'"]*diru[^\'"]*', response.text)
    print("References to diru:", len(diru_refs))
    for r in diru_refs[:10]:
        print(" - Ref:", r)
        
    # Let's find any URL domains or endpoints
    urls = re.findall(r'https?://[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}(?:/[^\s\'"]*)?', response.text)
    print("URLs found:", len(urls))
    for u in urls[:10]:
        print(" - URL:", u)

except Exception as e:
    print("Error:", e)
