#!/usr/bin/env python3
"""
Standalone preview of the RobotsThink AI News plugin pipeline.
Fetches news → generates think piece via Claude → prints the article.

Usage:
  NEWSAPI_KEY=xxx CLAUDE_API_KEY=xxx python3 preview_article.py
  NEWSAPI_KEY=xxx CLAUDE_API_KEY=xxx python3 preview_article.py --topic "machine learning" --words "900-1200"
"""

import os
import sys
import json
import argparse
import urllib.request
import urllib.parse
import textwrap

# ── CLI args ────────────────────────────────────────────────────────────────
parser = argparse.ArgumentParser()
parser.add_argument("--topic",  default="artificial intelligence")
parser.add_argument("--words",  default="900-1200")
parser.add_argument("--count",  type=int, default=5, help="Number of news articles to fetch")
args = parser.parse_args()

NEWSAPI_KEY = os.environ.get("NEWSAPI_KEY", "")
CLAUDE_KEY  = os.environ.get("CLAUDE_API_KEY", "")

if not NEWSAPI_KEY:
    sys.exit("ERROR: NEWSAPI_KEY environment variable is not set.")
if not CLAUDE_KEY:
    sys.exit("ERROR: CLAUDE_API_KEY environment variable is not set.")


# ── Step 1: Fetch news ───────────────────────────────────────────────────────
print(f"\n[1/2] Fetching top {args.count} articles about '{args.topic}' from NewsAPI...")

params = urllib.parse.urlencode({
    "q":        args.topic,
    "language": "en",
    "sortBy":   "publishedAt",
    "pageSize": args.count,
    "apiKey":   NEWSAPI_KEY,
})
req = urllib.request.Request(f"https://newsapi.org/v2/everything?{params}")
with urllib.request.urlopen(req, timeout=15) as resp:
    news_data = json.loads(resp.read())

articles = [
    {
        "title":       a["title"],
        "description": a.get("description", ""),
        "url":         a["url"],
        "source":      a["source"]["name"],
        "published":   a.get("publishedAt", ""),
    }
    for a in news_data.get("articles", [])
    if a.get("title") and a["title"] != "[Removed]"
]

if not articles:
    sys.exit("ERROR: No articles returned from NewsAPI.")

print("  Headlines found:")
for i, a in enumerate(articles, 1):
    print(f"    {i}. {a['title']} ({a['source']})")


# ── Step 2: Generate think piece with Claude ─────────────────────────────────
print(f"\n[2/2] Generating think piece ({args.words} words) with Claude...")

headlines = "\n".join(
    f"{i+1}. {a['title']} ({a['source']})" for i, a in enumerate(articles)
)

prompt = (
    "You are a sharp, original technology writer for RobotsThink, an AI news and commentary website.\n\n"
    f"Based on these recent AI headlines:\n{headlines}\n"
    "Write an original think piece article with these STRICT requirements:\n\n"
    f"**Length:** {args.words} words. Hit this target — not shorter, not longer.\n\n"
    "**Originality:** Do NOT summarize the news. Develop a unique, opinionated thesis that the headlines only hint at. "
    "Write from first principles. Surprise the reader with a perspective they haven't considered. "
    "Avoid clichés like 'In a world where...' or 'As AI continues to...'. "
    "Every paragraph should earn its place with a specific insight or argument, not filler.\n\n"
    "**Structure:**\n"
    "1. Hook opening (1 paragraph) — a bold claim, surprising fact, or provocative question that pulls the reader in immediately\n"
    "2. Thesis paragraph — state your specific, non-obvious argument clearly\n"
    "3. Body (3–4 sections, each with an <h2> subheading) — each section develops one aspect of the argument with concrete examples, historical parallels, or tight reasoning\n"
    "4. Conclusion (1–2 paragraphs) — crystallize the argument, don't just restate it; end with a memorable, forward-looking final line\n"
    "5. Sign off with: <p><em>— RobotsThink</em></p>\n\n"
    "**Voice:** Confident, direct, occasionally irreverent. Write for an educated general audience, not AI insiders. "
    "Define jargon when you use it. Short sentences land harder than long ones.\n\n"
    "**Tags:** Generate 3–5 specific, relevant tags for this article (e.g. 'OpenAI', 'AGI', 'AI regulation', "
    "'Large Language Models', 'AI ethics'). Be specific — 'AI' alone is too broad.\n\n"
    "**Featured Image:** Provide a short, descriptive search query (3–5 words) that would find a compelling, "
    "relevant stock photo for this article on Unsplash. Think visually — what image would make someone stop scrolling?\n\n"
    'Respond ONLY with valid JSON in this exact format:\n'
    '{"title": "Your headline here", "content": "Full article HTML using <p>, <h2>, <h3> tags", '
    '"tags": ["tag1", "tag2", "tag3"], "featured_image_query": "descriptive search query"}'
)

payload = json.dumps({
    "model":      "claude-sonnet-4-6",
    "max_tokens": 3500,
    "messages":   [{"role": "user", "content": prompt}],
}).encode()

req = urllib.request.Request(
    "https://api.anthropic.com/v1/messages",
    data=payload,
    headers={
        "x-api-key":         CLAUDE_KEY,
        "anthropic-version": "2023-06-01",
        "content-type":      "application/json",
    },
    method="POST",
)
with urllib.request.urlopen(req, timeout=90) as resp:
    claude_data = json.loads(resp.read())

raw_text = claude_data["content"][0]["text"].strip()
# Strip optional ```json fences
if raw_text.startswith("```"):
    raw_text = raw_text.split("\n", 1)[1]
if raw_text.endswith("```"):
    raw_text = raw_text.rsplit("```", 1)[0]

article = json.loads(raw_text)

# ── Print output ─────────────────────────────────────────────────────────────
SEP = "=" * 72
print(f"\n{SEP}")
print(f"TITLE:  {article['title']}")
print(f"TAGS:   {', '.join(article.get('tags', []))}")
print(f"PHOTO:  {article.get('featured_image_query', '')}")
print(SEP)

# Strip HTML tags for terminal display
import re
text = re.sub(r"<h[23][^>]*>", "\n## ", article["content"])
text = re.sub(r"</h[23]>", "\n", text)
text = re.sub(r"<p[^>]*>", "\n", text)
text = re.sub(r"</p>", "\n", text)
text = re.sub(r"<em>(.*?)</em>", r"_\1_", text)
text = re.sub(r"<[^>]+>", "", text)
text = re.sub(r"\n{3,}", "\n\n", text).strip()

for line in text.splitlines():
    if line.startswith("## "):
        print(f"\n{'─'*60}\n{line}\n{'─'*60}")
    else:
        print(textwrap.fill(line, width=72) if line.strip() else "")

print(f"\n{SEP}\n")
