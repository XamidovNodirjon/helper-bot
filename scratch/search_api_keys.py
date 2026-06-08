with open("scratch/app_chunk.js", "r", encoding="utf-8") as f:
    content = f.read()

import re

# Let's search for "Oht" or "Pbz" or "C8" in the file
for name in ["Oht", "Pbz", "C8", "fKT"]:
    matches = [m.start() for m in re.finditer(name, content)]
    print(f"Matches for {name}: {len(matches)}")
    for idx in matches[:5]:
        start = max(0, idx - 100)
        end = min(len(content), idx + 150)
        print(f"Context of {name}:")
        print(content[start:end])
        print("-" * 50)
