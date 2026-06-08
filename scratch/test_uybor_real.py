import requests
from parsel import Selector

url = "https://www.uybor.uz/ru/arenda-kvartir/kvartiry-v-tashkente"
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
    
    # Let's save a part of the HTML to inspect, or parse it right here.
    selector = Selector(response.text)
    
    # We want to find elements that represent cards/listings.
    # In common bootstrap / real estate websites, they might be in divs with certain classes.
    # Let's print some of the common classes or structure.
    # We can search for links containing "/ru/listing/" or similar.
    links = selector.xpath("//a[contains(@href, '/ru/listing/')]/@href").getall()
    print("Listing links found:", len(links))
    for link in links[:10]:
        print("Link:", link)
        
    # Let's find images
    images = selector.xpath("//img[contains(@src, 'cdnbor.uz') or contains(@data-src, 'cdnbor.uz')]/@src | //img[contains(@src, 'cdnbor.uz') or contains(@data-src, 'cdnbor.uz')]/@data-src").getall()
    print("Images found:", len(images))
    for img in images[:5]:
        print("Image:", img)

    # Let's write the response HTML to a file so we can view it
    with open("scratch/uybor_real_response.html", "w", encoding="utf-8") as f:
        f.write(response.text)
        
except Exception as e:
    print("Error:", e)
