import requests

headers = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Accept-Language': 'uz-UZ,uz;q=0.9,ru;q=0.8,en;q=0.7'
}

regions = [
    'tashkent',
    'samarkand',
    'fergana',
    'andijon',
    'buxoro',
    'namangan',
    'navoi',
    'karshi',
    'termez',
    'gulistan',
    'dzhizak',
    'urgench',
    'nukus'
]

for r in regions:
    url = f"https://www.olx.uz/nedvizhimost/kvartiry/arenda-dolgosrochnaya/{r}/"
    res = requests.get(url, headers=headers)
    print(f"Region: {r} -> Status: {res.status_code}")
