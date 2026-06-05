import requests

headers = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Accept-Language': 'uz-UZ,uz;q=0.9,ru;q=0.8,en;q=0.7'
}

base_url = "https://www.olx.uz/nedvizhimost/kvartiry/arenda-dolgosrochnaya/"

def test_url(slug):
    url = f"{base_url}{slug}/"
    res = requests.get(url, headers=headers)
    print(f"Slug: {slug} -> Status: {res.status_code}")

print("--- Testing Bukhara Slugs ---")
test_url("buxoro")
test_url("bukhara")
test_url("buhara")

print("\n--- Testing Andijan Slugs ---")
test_url("andijon")
test_url("andijan")
test_url("andizhan")
