# dr_cheung_2026

A full copy of the site's `twentysixteen` fork with a reworked masthead. Every
template, script, and image came across unchanged, so pages behave exactly as
they did before. Only these differ from `twentysixteen`:

| file | what changed |
| --- | --- |
| `header.php` | new masthead markup: contact details, brand lockup, nav states, banner |
| `css/header.css` | all of the new styling; loaded last so it wins on source order |
| `css/style.css`, `css/mobile.css` | unchanged, still loaded — the old `.site-top-contact` rules in them are now dead, since that bar is gone |
| `style.css` | theme header block only (name, description); the 3,900 lines of CSS below it are byte identical |
| `images/paper-script.png` | new, derived from `images/med_top.png` (see below) |

The dark contact bar that used to sit above the header is gone. The address and
phone moved into the masthead itself — address left, brand centred, phone right,
wrapping to two rows under 768px with the contact details staying on top.

## images/paper-script.png

`images/med_top.png` is the site's original watermark: a 590x117 sheet of faded
prescription script. It is a palette PNG with no alpha, and its characters sit at
luminance 229-255 — a 10% delta against paper white. Tiled on `<body>` it read as
a plain cream strip that ran out 117px down the page.

`paper-script.png` re-maps that 10% delta into a real alpha channel: how far each
pixel falls below white becomes how opaque a warm sepia ink is. That makes the
script tunable with plain `opacity`, with no blend-mode tricks needed.

It was generated with GD from the WordPress container, and can be regenerated
with a different gain or ink colour by re-running this from the theme directory:

    docker run --rm -v "$PWD/images:/img" deyutcm-wordpress php -r '
    $src = imagecreatefrompng("/img/med_top.png");
    $w = imagesx($src); $h = imagesy($src);
    $out = imagecreatetruecolor($w, $h);
    imagealphablending($out, false);
    imagesavealpha($out, true);
    $gain = 9.0;         // 26 levels of delta -> a usable alpha range
    $ink = [90, 68, 19]; // warm sepia, in the family of the gold mark
    for ($y = 0; $y < $h; $y++) {
      for ($x = 0; $x < $w; $x++) {
        $c = imagecolorsforindex($src, imagecolorat($src, $x, $y));
        $l = 0.299*$c["red"] + 0.587*$c["green"] + 0.114*$c["blue"];
        $a = min(255, max(0, (255 - $l) * $gain));
        $gd = 127 - (int) round($a * 127 / 255);  // GD: 0 opaque, 127 clear
        imagesetpixel($out, $x, $y, imagecolorallocatealpha($out, $ink[0], $ink[1], $ink[2], $gd));
      }
    }
    imagepng($out, "/img/paper-script.png", 9);'

Raising `$gain` darkens the strokes; the two layers in `css/header.css`
(`.dy-masthead::before` and `::after`) then set how much of it shows, at what
scale, and how it fades at the edges.

## Notes

- Everything new is `dy-` prefixed. The parent Twenty Sixteen stylesheet already
  owns `.site-header` and friends, and it is still loaded here, so unprefixed
  names collide — that is what put a 42px gap above the nav during development.
- `header.php` pins `lang="zh-CN"`. The WordPress locale is `en-US` while every
  word on the page is Chinese, so `language_attributes()` announced the wrong
  language to screen readers and font pickers.
- The IE-only stylesheets (`css/ie*.css`) and `js/html5.js` are still in the
  theme but no longer enqueued: `wp_style_add_data( ..., 'conditional', ... )` is
  deprecated as of WP 6.9 and no supported browser reads conditional comments.
- Activating this theme is a database setting, so a `npm run pull:db` reverts
  localhost to `twentysixteen`. Re-run `npm run wp -- theme activate
  dr_cheung_2026` afterwards.
