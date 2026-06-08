import requests
from parsel import Selector

url = "https://www.uybor.uz/ru"
headers = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
    'Accept-Language': 'uz-UZ,uz;q=0.9,ru;q=0.8,en;q=0.7',
    'Connection': 'keep-alive'
}

try:
    response = requests.get(url, headers=headers, verify=False, timeout=15)
    print("Status:", response.status_code)
    print("HTML Length:", len(response.text))
    
    # Save to file
    with open("scratch/uybor_home.html", "w", encoding="utf-8") as f:
        f.write(response.text)
        
except Exception as e:
    print("Error:", e)
