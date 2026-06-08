import json
from parsel import Selector

with open("scratch/uybor_real_200.html", "r", encoding="utf-8") as f:
    html = f.read()

selector = Selector(html)
next_data_script = selector.xpath("//script[@id='__NEXT_DATA__']/text()").get()

if next_data_script:
    print("Found __NEXT_DATA__!")
    data = json.loads(next_data_script)
    # Save parsed JSON to view its structure
    with open("scratch/uybor_next_data.json", "w", encoding="utf-8") as f:
        json.dump(data, f, indent=2, ensure_ascii=False)
        
    print("Keys in __NEXT_DATA__:", data.keys())
    if 'props' in data:
        print("Keys in props:", data['props'].keys())
        if 'pageProps' in data['props']:
            pageProps = data['props']['pageProps']
            print("Keys in pageProps:", pageProps.keys())
            # Let's print all keys at pageProps level to see if listings are present
            for k, v in pageProps.items():
                if isinstance(v, (list, dict)):
                    print(f"  {k}: type={type(v)}, size/len={len(v)}")
else:
    print("__NEXT_DATA__ not found by id.")
