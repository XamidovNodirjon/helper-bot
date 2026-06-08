from parsel import Selector

with open("scratch/uybor_real_200.html", "r", encoding="utf-8") as f:
    html = f.read()

sel = Selector(html)
# Extract all visible text
texts = sel.xpath("//text()[not(parent::script) and not(parent::style)]").getall()
clean_texts = [t.strip() for t in texts if t.strip()]
print("Total text blocks:", len(clean_texts))
for t in clean_texts[:50]:
    print(" -", t)
