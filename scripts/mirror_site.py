import re
import ssl
import urllib.parse
import urllib.request
from pathlib import Path
from typing import Iterable, Set

BASE_URL = "https://usaxy.org"
SITE_ROOT = Path(__file__).resolve().parents[1] / "usaxy" / "html"
MAX_PAGES = 50
PAGE_PATHS = [
    "/",
    "/events/",
    "/photos/",
    "/posts/",
    "/join/",
    "/contacts/",
]
PAGE_FILE_NAMES = {
    "/": "index.php",
    "/events/": "events.php",
    "/photos/": "photos.php",
    "/posts/": "posts.php",
    "/join/": "join.php",
    "/contacts/": "contacts.php",
}
HIDDEN_MENU_IDS = ("103", "112", "113")
RESOURCE_EXTENSIONS = (
    ".jpg",
    ".jpeg",
    ".png",
    ".gif",
    ".svg",
    ".webp",
    ".css",
    ".js",
    ".ico",
    ".xml",
    ".json",
    ".woff",
    ".woff2",
    ".ttf",
    ".eot",
    ".mp4",
    ".webm",
    ".pdf",
)
SKIP_PREFIXES = (
    "/wp-json/",
    "/xmlrpc.php",
)

USER_AGENT = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36"


def fetch(url: str) -> bytes:
    req = urllib.request.Request(url, headers={"User-Agent": USER_AGENT})
    context = ssl._create_unverified_context()
    with urllib.request.urlopen(req, context=context, timeout=20) as response:
        return response.read()


def ensure_parent(path: Path) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)


def path_for_url(url: str, is_page: bool) -> str:
    parsed = urllib.parse.urlparse(url)
    path = parsed.path or "/"
    suffix = ""
    if parsed.query:
        safe_query = re.sub(r"[^A-Za-z0-9._-]+", "-", parsed.query).strip("-")
        if safe_query:
            suffix = f"__{safe_query}"

    if path == "/":
        return "/index.html"
    if is_page:
        if path.endswith("/"):
            return path + "index.html"
        if "." not in Path(path).name:
            return path + "/index.html"
        return path
    if suffix:
        path_obj = Path(path)
        if "." in path_obj.name:
            return str(path_obj.with_name(f"{path_obj.stem}{suffix}{path_obj.suffix}"))
        return path.rstrip("/") + suffix
    return path


def local_path_for_url(url: str, is_page: bool) -> str:
    return path_for_url(url, is_page)


def save_bytes(file_path: Path, data: bytes) -> None:
    ensure_parent(file_path)
    file_path.write_bytes(data)


def should_skip_url(url: str) -> bool:
    parsed = urllib.parse.urlparse(url)
    return any(parsed.path.startswith(prefix) for prefix in SKIP_PREFIXES)


def build_local_url(raw: str, current_url: str, is_page: bool | None = None) -> str:
    if not raw or raw.startswith(("#", "data:", "mailto:", "tel:", "javascript:")):
        return raw
    if raw.startswith("//"):
        return raw

    parsed = urllib.parse.urlparse(raw)
    if parsed.scheme or parsed.netloc:
        if parsed.netloc and parsed.netloc != "usaxy.org":
            return raw
        absolute_url = urllib.parse.urlunparse(("https", "usaxy.org", parsed.path or "/", "", parsed.query, parsed.fragment))
    else:
        absolute_url = urllib.parse.urljoin(current_url, raw)

    if should_skip_url(absolute_url):
        return raw

    absolute_parsed = urllib.parse.urlparse(absolute_url)
    if is_page is None:
        is_page = "." not in Path(absolute_parsed.path).name

    if is_page:
        clean_path = absolute_parsed.path or "/"
        if clean_path == "/":
            local_path = "/"
        elif clean_path.endswith("/"):
            local_path = clean_path
        else:
            local_path = clean_path + "/"
    else:
        local_path = local_path_for_url(absolute_url, is_page)
        if not local_path.startswith("/"):
            local_path = "/" + local_path
    if absolute_parsed.fragment:
        local_path += "#" + absolute_parsed.fragment
    return local_path


def rewrite_srcset(content: str, current_url: str) -> str:
    def repl(match: re.Match) -> str:
        prefix = match.group("prefix")
        body = match.group("body")
        quote = match.group("quote")
        rewritten_items = []
        for item in body.split(","):
            candidate = item.strip()
            if not candidate:
                continue
            parts = candidate.split()
            url = parts[0]
            descriptor = " ".join(parts[1:])
            local_url = build_local_url(url, current_url, is_page=False)
            rewritten_items.append(f"{local_url} {descriptor}".strip())
        return f"{prefix}{', '.join(rewritten_items)}{quote}"

    return re.sub(r'(?P<prefix>srcset\s*=\s*["\'])(?P<body>[^"\']+)(?P<quote>["\'])', repl, content)


def rewrite_css_urls(content: str, current_url: str) -> str:
    def repl(match: re.Match) -> str:
        quote = match.group("quote") or ""
        raw = match.group("url").strip()
        local_url = build_local_url(raw, current_url, is_page=False)
        return f"url({quote}{local_url}{quote})"

    return re.sub(r"url\((?P<quote>['\"]?)(?P<url>[^)'\"]+)(?P=quote)\)", repl, content)


def rewrite_html(content: str, current_url: str) -> str:
    def repl(match: re.Match) -> str:
        attr = match.group("attr")
        raw = match.group("url")
        quote = match.group("quote")
        local_url = build_local_url(raw, current_url)
        return f"{attr}{local_url}{quote}"

    content = re.sub(r'''(href|src)\s*=\s*(["'])(.*?)\2''', lambda m: m.group(0), content)
    content = re.sub(r'''(?P<attr>(?:href|src)\s*=\s*["'])(?P<url>[^"']+)(?P<quote>["'])''', repl, content)
    content = rewrite_srcset(content, current_url)
    content = rewrite_css_urls(content, current_url)
    return inject_hidden_nav_css(content)


def inject_hidden_nav_css(content: str) -> str:
    selectors = ",".join(
        [
            *(f"#menu-item-{menu_id}" for menu_id in HIDDEN_MENU_IDS),
            *(f".menu-item-{menu_id}" for menu_id in HIDDEN_MENU_IDS),
        ]
    )
    style_block = (
        "\n<style id=\"local-hidden-nav-items\">\n"
        f"{selectors} {{ display: none !important; }}\n"
        "</style>\n"
    )
    if "local-hidden-nav-items" in content:
        return content
    return content.replace("</head>", f"{style_block}</head>", 1)


def rewrite_css(content: str, current_url: str) -> str:
    return rewrite_css_urls(content, current_url)


def extract_first_match(pattern: str, text: str, fallback: str = "") -> str:
    match = re.search(pattern, text, re.DOTALL)
    return match.group(1).strip() if match else fallback


def extract_header_fragment(html: str) -> str:
    desktop_index = html.find('<div class="desktop-simple-header">')
    if desktop_index == -1:
        raise ValueError("Could not locate shared desktop header")

    header_style_start = html.rfind("<style>", 0, desktop_index)
    header_script_end = html.find("</script>", desktop_index)
    if header_style_start == -1 or header_script_end == -1:
        raise ValueError("Could not locate complete shared header block")

    fragment = html[header_style_start : header_script_end + len("</script>")]
    fragment = re.sub(r"</body>\s*</html>\s*", "", fragment, count=1, flags=re.DOTALL)
    fragment = fragment.replace("https://www.ycbeautycenter.com/", "/")
    fragment = fragment.replace(' aria-current="page"', "")
    fragment = re.sub(r"\s+current-menu-item\b", "", fragment)
    fragment = re.sub(r"\s+current_page_item\b", "", fragment)
    fragment = re.sub(r"\s+page_item\b", "", fragment)
    fragment = re.sub(r"\s+page-id-\d+\b", "", fragment)
    fragment = re.sub(r"\s+page-item-\d+\b", "", fragment)
    return fragment.strip()


def extract_page_content(html: str) -> str:
    start = html.find('<div class="intro">')
    end = html.find('<style>\n    .footer {', start)
    if start == -1 or end == -1:
        raise ValueError("Could not isolate page content block")
    content = html[start:end].strip()
    return re.sub(r"</div>\s*</div>\s*$", "</div>", content, flags=re.DOTALL)


def extract_footer_fragment(html: str) -> str:
    start = html.find('<style>\n    .footer {')
    end = html.rfind("</body>")
    if start == -1 or end == -1:
        raise ValueError("Could not isolate footer block")
    fragment = html[start:end].strip()
    return re.sub(r"</head>\s*<body>\s*", "", fragment, count=1, flags=re.DOTALL)


def build_header_include(index_html: str) -> str:
    outer_head = extract_first_match(r"<head>(.*?)</head>", index_html)
    outer_head = re.sub(r"<title>.*?</title>\s*", "", outer_head, count=1, flags=re.DOTALL)
    outer_head = re.sub(r'<link rel="alternate"[^>]+>\s*', "", outer_head)
    outer_head = re.sub(r"<link rel='shortlink'[^>]+>\s*", "", outer_head)
    outer_head = re.sub(r'<link rel="canonical"[^>]+>\s*', "", outer_head)
    outer_head = re.sub(r'<link rel="https://api\.w\.org/"[^>]+>\s*', "", outer_head)
    outer_head = re.sub(r'<link rel="EditURI"[^>]+>\s*', "", outer_head)
    outer_head = re.sub(r'<meta name="generator"[^>]+>\s*', "", outer_head)
    outer_head = re.sub(r'<meta charset="utf-8">\s*', "", outer_head)
    outer_head = re.sub(r'<meta name="viewport" content="width=device-width, initial-scale=1">\s*', "", outer_head)
    outer_head = outer_head.strip()

    header_fragment = extract_header_fragment(index_html)

    return f"""<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($pageTitle ?? '美國信陽同鄉會', ENT_QUOTES, 'UTF-8'); ?></title>
    {outer_head}
    <link rel="stylesheet" href="/wp-content/themes/yc-colin/global.css" />
</head>
<body>
{header_fragment}
    <div class="content">
"""


def build_footer_include(index_html: str) -> str:
    footer_fragment = extract_footer_fragment(index_html)
    return f"""    </div>
{footer_fragment}
</body>
</html>
"""


def build_page_php(html: str, route: str) -> str:
    raw_title = extract_first_match(r"<title>(.*?)</title>", html, "美國信陽同鄉會")
    page_title = raw_title.replace(" &#8211; 美國信陽同鄉會", "").replace(" – 美國信陽同鄉會", "")
    content = extract_page_content(html)
    return f"""<?php
$pageTitle = {page_title!r};
include __DIR__ . '/includes/header.php';
?>
{content}
<?php include __DIR__ . '/includes/footer.php'; ?>
"""


def build_php_site() -> None:
    index_html_path = SITE_ROOT / "index.html"
    if not index_html_path.exists():
        return

    includes_dir = SITE_ROOT / "includes"
    includes_dir.mkdir(parents=True, exist_ok=True)

    index_html = index_html_path.read_text()
    (includes_dir / "header.php").write_text(build_header_include(index_html))
    (includes_dir / "footer.php").write_text(build_footer_include(index_html))

    for route, php_name in PAGE_FILE_NAMES.items():
        html_path = SITE_ROOT / path_for_url(urllib.parse.urljoin(BASE_URL, route), True).lstrip("/")
        if not html_path.exists():
            continue
        php_path = SITE_ROOT / php_name
        php_path.write_text(build_page_php(html_path.read_text(), route))

    for route in PAGE_FILE_NAMES:
        html_path = SITE_ROOT / path_for_url(urllib.parse.urljoin(BASE_URL, route), True).lstrip("/")
        if html_path.exists():
            html_path.unlink()
        if route != "/":
            page_dir = SITE_ROOT / route.strip("/")
            if page_dir.exists():
                page_dir.rmdir()


def iter_resource_urls(content: str, current_url: str) -> Iterable[str]:
    for match in re.finditer(r'''(?:href|src)\s*=\s*["']([^"']+)["']''', content):
        yield urllib.parse.urljoin(current_url, match.group(1))
    for match in re.finditer(r'''srcset\s*=\s*["']([^"']+)["']''', content):
        for item in match.group(1).split(","):
            url = item.strip().split()[0]
            if url:
                yield urllib.parse.urljoin(current_url, url)
    for match in re.finditer(r'''url\((['"]?)([^)'"]+)\1\)''', content):
        yield urllib.parse.urljoin(current_url, match.group(2))


def is_resource_url(url: str) -> bool:
    parsed = urllib.parse.urlparse(url)
    return parsed.path.endswith(RESOURCE_EXTENSIONS)


def should_enqueue_page(url: str) -> bool:
    parsed = urllib.parse.urlparse(url)
    if parsed.netloc and parsed.netloc != "usaxy.org":
        return False
    if should_skip_url(url):
        return False
    if parsed.query:
        return False
    return "." not in Path(parsed.path).name


def download_page(url: str, seen: Set[str], queue: list[str]) -> None:
    if url in seen:
        return
    seen.add(url)

    try:
        data = fetch(url)
    except Exception as exc:
        print(f"Skip {url}: {exc}")
        return

    parsed = urllib.parse.urlparse(url)
    is_page = True
    local_file = SITE_ROOT / path_for_url(url, is_page).lstrip("/")

    try:
        text = data.decode("utf-8", errors="ignore")
    except Exception:
        text = data.decode("latin-1", errors="ignore")

    if "<!DOCTYPE html" in text.lower() or "<html" in text.lower():
        rewritten = rewrite_html(text, url)
        save_bytes(local_file, rewritten.encode("utf-8"))
        for target_url in iter_resource_urls(text, url):
            if target_url.startswith(BASE_URL):
                if should_enqueue_page(target_url):
                    queue.append(target_url)
                elif is_resource_url(target_url):
                    download_resource(target_url)
    else:
        save_bytes(local_file, data)


def download_resource(url: str) -> None:
    if should_skip_url(url):
        return

    output_path = SITE_ROOT / path_for_url(url, False).lstrip("/")
    if output_path.exists():
        return

    try:
        data = fetch(url)
    except Exception as exc:
        print(f"Skip resource {url}: {exc}")
        return

    if urllib.parse.urlparse(url).path.endswith(".css"):
        try:
            text = data.decode("utf-8", errors="ignore")
        except Exception:
            text = data.decode("latin-1", errors="ignore")
        rewritten = rewrite_css(text, url)
        save_bytes(output_path, rewritten.encode("utf-8"))
        for nested_url in iter_resource_urls(rewritten, url):
            if nested_url.startswith(BASE_URL) and is_resource_url(nested_url):
                download_resource(nested_url)
        return

    save_bytes(output_path, data)


def main() -> None:
    SITE_ROOT.mkdir(parents=True, exist_ok=True)
    queue = [urllib.parse.urljoin(BASE_URL, path) for path in PAGE_PATHS]
    seen: Set[str] = set()

    while queue and len(seen) < MAX_PAGES:
        url = queue.pop(0)
        if url in seen:
            continue
        download_page(url, seen, queue)

    build_php_site()

    print(f"Mirrored site into {SITE_ROOT}")


if __name__ == "__main__":
    main()
