import requests
from parsel import Selector

url = "https://www.olx.uz/nedvizhimost/kvartiry/arenda-dolgosrochnaya/tashkent/"
headers = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
}

try:
    r = requests.get(url, headers=headers, timeout=15)
    if r.status_code == 200:
        selector = Selector(r.text)
        cards = selector.xpath('//div[@data-testid="l-card"] | //a[@data-testid="card-link"]')
        print("Cards found:", len(cards))
        
        # Let's check card 5 or later
        for idx, card in enumerate(cards[5:10], start=5):
            print(f"\n--- Card {idx} noscript check ---")
            noscript_content = card.css('noscript').get()
            print("  Noscript tag present:", noscript_content is not None)
            if noscript_content:
                print("  Noscript content:", noscript_content[:200])
                # Parse noscript content as Selector
                ns_sel = Selector(noscript_content)
                img_src = ns_sel.css('img::attr(src)').get()
                print("  Image src inside noscript:", img_src)
except Exception as e:
    print("Error:", e)
