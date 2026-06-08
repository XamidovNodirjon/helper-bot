with open("scratch/app_chunk.js", "r", encoding="utf-8") as f:
    content = f.read()

import re

# Find occurrences of Apartment and Commercial next to each other
matches = [m.start() for m in re.finditer(r"Apartment", content)]
print("Total 'Apartment' matches:", len(matches))
for idx in matches:
    start = max(0, idx - 50)
    end = min(len(content), idx + 200)
    snippet = content[start:end]
    if "Commercial" in snippet or "House" in snippet:
        print("MATCH CONTEXT:")
        print(snippet)
        print("-" * 50)
