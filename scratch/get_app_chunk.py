import requests
import re

url = "https://uybor.uz/_next/static/chunks/pages/_app-796bc209224acfd1.js"
headers = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
}

try:
    response = requests.get(url, headers=headers, verify=False, timeout=15)
    print("Status:", response.status_code)
    print("Length:", len(response.text))
    
    # Save the file
    with open("scratch/app_chunk.js", "w", encoding="utf-8") as f:
        f.write(response.text)
        
    # Search for URL domains or baseURL configurations
    urls = re.findall(r'https?://[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}(?:/[^\s\'"]*)?', response.text)
    print("Domains/URLs found:")
    for u in set(urls):
        print(" -", u)
        
    # Search for baseURL or axios/fetch configs
    for line in response.text.split('\n'):
        if 'baseURL' in line or 'baseURI' in line or 'apiUrl' in line:
            print("Line containing baseURL/apiUrl:")
            print("  ", line[:300])

except Exception as e:
    print("Error:", e)
