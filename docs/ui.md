# UI Design

## Theme: Warm Editorial
Cream background, white cards, dark ink accent palette.

## CSS Variables
```css
:root {
    --bg:        #F5F1EA;  /* warm cream page bg */
    --surface:   #FDFAF5;  /* white card surface */
    --border:    #DDD8CF;  /* subtle warm border */
    --ink:       #1A1714;  /* primary text */
    --ink-2:     #4A4540;  /* secondary text */
    --ink-3:     #8A8480;  /* muted/placeholder text */
    --accent:    #C4622D;  /* terracotta accent */
    --accent-lt: #F2E8E1;  /* light accent bg */
    --radius:    2px;      /* sharp corners */
}
```

## Typography
- **Headings**: DM Serif Display, 20-26px, letter-spacing -0.3px
- **Body**: DM Sans, 14px
- **Labels**: 10.5px, 600 weight, uppercase, 1.8px letter-spacing

## Buttons
- **.btn-primary**: `--accent` fill, white text, 2px radius
- **.btn-ghost**: transparent, `--border` outline
- **.btn-danger-ghost**: transparent, red on hover
- Font: DM Sans 12.5px, 500 weight
- Padding: 9px 18px

## Layout Pattern
- Sidebar: 260px fixed, dark ink bg
- Topbar: 72px sticky
- Content: 40px padding (20px mobile)

## Responsive
- **Desktop**: ≥992px - full sidebar
- **Tablet**: <992px - sidebar slides in as drawer
- **Mobile**: <600px - stacked, full-width

## Components
- `.card` - white surface, 1px border, 2px radius
- `.form-control` - cream bg, warm border
- `.alert` - bordered boxes (success/danger)
- `.table` - uppercase headers, hover rows

## Related Docs
- `docs/security.md` - Security headers (X-Frame-Options, etc.)
- `docs/troubleshooting.md` - UI/display issues