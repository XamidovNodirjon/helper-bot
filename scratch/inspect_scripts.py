from parsel import Selector

with open("scratch/uybor_real_200.html", "r", encoding="utf-8") as f:
    html = f.read()

selector = Selector(html)

# Let's find all script tags
scripts = selector.xpath("//script")
print("Total script tags:", len(scripts))

for i, script in enumerate(scripts):
    src = script.xpath("@src").get()
    text = script.xpath("text()").get() or ""
    print(f"Script {i}: src={src}, text_length={len(text)}")
    if text:
        # Check if it contains interesting keywords
        for keyword in ["__NEXT_DATA__", "window.", "listings", "ads", "data", "state", "props"]:
            if keyword in text:
                print(f"  -> Contains keyword: {keyword}")
                # Print first 200 chars of text
                print(f"     Preview: {text[:200]}")
