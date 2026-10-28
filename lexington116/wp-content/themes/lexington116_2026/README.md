# Lexington116 theme

A header and split hero inspired by the warm palette, serif typography, rounded
buttons, and arched photography at https://www.mymassage.ca/. Copy, layout details,
and branding are adapted for 116 Lexington. No other site's reviews or credentials
are used.

- `front-page.php`: landing page hero and short introduction.
- `header.php` / `footer.php`: shared header, navigation, footer, and booking dialog.
- `index.php`: fallback for WordPress pages and posts.
- `style.css`: responsive styling; `assets/site.js`: mobile menu and booking dialog.
- Fonts and photography are served from this theme, with no runtime CDN requests.

In **Appearance → Customize → Lexington116 · Booking & contact**, add a booking URL,
phone, and address. With no URL, booking buttons open a dialog with the phone if
provided, or a coming-soon message. No appointment is submitted by the theme.

Photography: https://images.unsplash.com/photo-1519823551278-64ac92734fb1,
a stock massage image;
replace `assets/massage.jpg` with your own photo when available.
Fonts: Cormorant Garamond and Outfit, from Google Fonts (SIL Open Font License).

## Hero image shapes

Shape A preserves the original arch. Shape B uses a rounded rectangle with a
20px corner radius, a subtle gold border, and a 650px desktop height. Both retain
the 620px maximum width and adapt to smaller screens. Shape B is the default.
Choose either in **Appearance → Customize → Lexington116 · Hero design**.
Preview without saving using `/?hero_shape=a` or `/?hero_shape=b`.

## Hero video

The active video is `assets/massage.mp4`, copied from the user's Desktop.
The supplied clip is approximately six seconds long. It loops silently, with
pause/play controls and reduced-motion support. `assets/massage-poster.jpg`
is extracted from the clip for the loading and paused fallback.

The earlier generated pink-uniform video and its prompt remain in `assets/`
as unused alternatives.

### Video loading

The active clip uses H.264 / yuv420p, retains the original 592 × 512 resolution
and 6.04-second duration, and removes audio and the embedded cover-art stream.
Fast-start MP4 metadata is placed before video data. The optimized file is about
369 KiB (down from 939 KiB). The poster is preloaded at high priority. Video
buffering starts automatically unless reduced motion is requested; the manual
play control still works. The video URL is versioned when the file changes.
The original supplied video remains on the Desktop.

## Separate pages

The header links to Home, About Lumi, Menu, and Contact Us using real WordPress
pages and full-page navigation. Missing pages are created once on theme activation
or the next administrator dashboard visit after an update. Existing page content
is preserved. After deploying this update, visit wp-admin once to create them.
The templates are `page-about-lumi.php`, `page-menu.php`, and `page-contact-us.php`.
Additional content saved in each page's WordPress editor appears below the layout.

The menu uses the supplied rate card: 20 minutes $39, 30 minutes $49, and 60
minutes $79. Business hours are 9:30 am–9:30 pm; the new-customer special window
is 9:30 am–2:30 pm (no discount amount was supplied). The Menu page suppresses
phone/address output in the shared footer and booking dialog as well.
Contact Us uses the supplied contact details unless overridden in the Customizer.

Contact details default to 646-298-5898, 116 Lexington Ave, 2nd Floor, New York,
NY 10016, and business hours 9:30 am–9:30 pm. All can be edited in the Customizer.
Old Visit Us links remain supported but are not in the navigation.

## About page imagery

`assets/about-pink-room.jpg` is an illustrative, AI-generated image of a modest
pink-lit massage room, created with the built-in image tool. It is not a photograph
of the actual premises. Exact prompt: `assets/about-room-prompt.txt`.
