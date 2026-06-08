import requests
from parsel import Selector

url = "https://www.olx.uz/nedvizhimost/kvartiry/arenda-dolgosrochnaya/tashkent/"
headers = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
    'Accept-Language': 'uz-UZ,uz;q=0.9,ru;q=0.8,en;q=0.7',
    'Connection': 'keep-alive'
}

try:
    r = requests.get(url, headers=headers, timeout=15)
    print("Status:", r.status_code)
    if r.status_code == 200:
        selector = Selector(r.text)
        cards = selector.xpath('//div[@data-testid="l-card"] | //a[@data-testid="card-link"]')
        print("Cards found:", len(cards))
        
        for idx, card in enumerate(cards[:15]):
            print(f"\n--- Card {idx} ---")
            imgs = card.css('img')
            for i, img_el in enumerate(imgs):
                attrs = img_el.root.attrib
                print(f"  Img {i} attributes:")
                for attr_name, attr_val in attrs.items():
                    print(f"    {attr_name}: {attr_val[:100]}")
except Exception as e:
    print("Error:", e)
