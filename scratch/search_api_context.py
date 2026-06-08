with open("scratch/app_chunk.js", "r", encoding="utf-8") as f:
    content = f.read()

# Let's search for "api.uybor.uz" and print around it
import re
matches = [m.start() for m in re.finditer("api.uybor.uz", content)]
print("Matches found:", len(matches))
for idx in matches:
    start = max(0, idx - 150)
    end = min(len(content), idx + 250)
    print("MATCH CONTEXT:")
    print(content[start:end])
    print("-" * 50)
