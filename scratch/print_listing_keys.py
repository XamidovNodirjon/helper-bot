import json

with open("scratch/uybor_api_response.json", "r", encoding="utf-8") as f:
    data = json.load(f)

results = data.get('results', [])
if results:
    item = results[0]
    print("Keys in listing item:")
    for k in sorted(item.keys()):
        val = item[k]
        # print type and first few chars
        if isinstance(val, (dict, list)):
            print(f"  {k}: type={type(val)}, len={len(val)}")
        else:
            print(f"  {k}: type={type(val)}, value={repr(val)[:60]}")
