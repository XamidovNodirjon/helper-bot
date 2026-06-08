with open("scratch/app_chunk.js", "r", encoding="utf-8") as f:
    content = f.read()

import re
matches = [m.start() for m in re.finditer(r"district", content, re.IGNORECASE)]
print("Total occurrences of 'district':", len(matches))
for idx in matches[:10]:
    start = max(0, idx - 100)
    end = min(len(content), idx + 150)
    print("MATCH CONTEXT:")
    print(content[start:end])
    print("-" * 50)
