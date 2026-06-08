import re

with open("scratch/uybor_real_200.html", "r", encoding="utf-8") as f:
    html = f.read()

# Check if there are any price numbers or keywords like "y.e." or "сум" in the text
prices_found = re.findall(r'\b\d+[\s\d]*\s*(?:y\.e\.|сум|usd|\$)\b', html, re.IGNORECASE)
print(f"Prices found in HTML: {len(prices_found)}")
for p in prices_found[:20]:
    print(" - Price:", p)

# Search for any listings-related terms, or let's search for typical structure like a list of links
# Let's find all hrefs in the page
from parsel import Selector
sel = Selector(html)
hrefs = sel.xpath("//a/@href").getall()
print(f"Total hrefs: {len(hrefs)}")
listing_hrefs = [h for h in hrefs if '/listing/' in h or 'detail' in h or '/ru/' in h]
print(f"Listing/detail hrefs: {len(listing_hrefs)}")
for lh in listing_hrefs[:20]:
    print(" - Href:", lh)
