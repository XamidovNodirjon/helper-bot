import requests
from parsel import Selector

url = "https://uybor.uz/ru/arenda-kvartir/kvartiry-v-tashkente"
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
    
    # Save the response
    with open("scratch/uybor_real_200.html", "w", encoding="utf-8") as f:
        f.write(response.text)
        
    selector = Selector(response.text)
    
    # Let's look for link hrefs containing "/ru/listing/"
    # Normally, real estate card links look like: /ru/listing/12345
    links = selector.xpath("//a[contains(@href, '/ru/listing/')]/@href").getall()
    print("Listing links found:", len(links))
    for link in links[:15]:
        print("Link:", link)
        
    # Let's inspect the cards
    # On many sites, each listing is inside an article, li or div with a specific class.
    # Let's print unique class names of div/li/article elements to see
    classes = set()
    for el in selector.css('div, li, article, a'):
        cls = el.root.get('class')
        if cls:
            for c in cls.split():
                classes.add(c)
    print("Some classes on the page:")
    print(list(classes)[:50])

except Exception as e:
    print("Error:", e)
