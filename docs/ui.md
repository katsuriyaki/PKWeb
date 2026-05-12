# UI Design

## Theme: Minimal Monochrome
- Black background (#000)
- White content cards with 1px solid black border
- Sharp corners (no border-radius)
- Bootstrap 5.3 for components

## CSS Variables
```css
:root {
    --black: #000;
    --dark: #333;
    --muted: #777;
    --light: #999;
    --lightest: #eee;
}
```

## Typography
- **Base**: -apple-system, BlinkMacSystemFont, "Helvetica Neue", Arial
- **Scale**: 11px → 12px → 13-14px → 16-20px
- **Labels**: uppercase, 1-1.5px letter-spacing, 600 weight

## Buttons
- **.btn-dark**: filled black, white text
- **.btn-outline-dark**: outline black, black text
- Padding: 10-14px 20px
- Font: 12px, uppercase, 1px letter-spacing

## Responsive Breakpoints
- **Desktop**: ≥992px - full sidebar visible
- **Tablet**: <992px - sidebar becomes slide-in drawer
- **Mobile**: <768px - stacked layout, full-width buttons

## Sidebar (Dashboard/Crud Pages)
- Width: 260px fixed
- Logo: 18px, 700 weight
- Nav links: 14px, 14px 28px padding
- Active state: 3px left border black

## Spacing
- Sidebar padding: 32px
- Content padding: 40px (20px on mobile)
- Table cells: 18px 24px
- Form inputs: 12px 16px

## Components
- .card - white with black border
- .form-control - black border, no radius
- .alert - bordered, no radius
- .table - bordered header, hover rows