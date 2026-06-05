import requests
import re
import urllib.parse

url = "https://www.olx.uz/nedvizhimost/arenda-kvartir/tashkent/"
headers = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Accept-Language': 'uz-UZ,uz;q=0.9,ru;q=0.8,en;q=0.7'
}

response = requests.get(url, headers=headers)
html = response.text

matches = re.findall(r'href=["\']([^"\']*?district[^"\']*?)["\']', html)
for m in matches[:20]:
    print(m)
