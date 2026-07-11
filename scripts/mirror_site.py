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
    return rewrite_css_urls(content, current_url)


def rewrite_css(content: str, current_url: str) -> str:
    return rewrite_css_urls(content, current_url)


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

    print(f"Mirrored site into {SITE_ROOT}")


if __name__ == "__main__":
    main()
