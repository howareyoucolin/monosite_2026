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

## Pink uniform video

The fictional adult therapist still (`assets/spa-pink-poster.jpg`) was generated
with the built-in image tool; the final prompt is `assets/spa-pink-loop-prompt.txt`.
`assets/spa-pink-loop.mp4` is a three-second silent camera-motion loop made from
that still, not independently animated massage action.
The loop has a pause/play button and starts paused for reduced-motion preferences.
