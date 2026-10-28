# Themes Folder

Folder name must only alphanumeric characters and underscores _**my_theme**_ will show as _**My Theme**_

## Restyling the customer portal

The customer header, menu, mobile bar and footer are styled by `ui/ui/styles/customer-modern.css`, which uses `--cm-*`
colour tokens for light mode and `body.dark-mode` for dark mode (the switch in the header). A theme re-skins them without
copying any template: add `styles/customer.css` to the theme folder. The core customer header loads it after the core
styles (a parent theme's file first, so a child wins) and adds the file time to the address, so a change shows at once.

```css
body.cm-chrome.modern-skin-dark            { --cm-accent: #2563eb; --cm-accent-soft: #eff6ff; --cm-accent-text: #1d4ed8; }
body.cm-chrome.modern-skin-dark.dark-mode  { --cm-accent: #60a5fa; --cm-accent-soft: rgba(96,165,250,.14); --cm-accent-text: #93c5fd; }
```

Tokens: `--cm-bar` (frosted bar), `--cm-solid` (menu / cards), `--cm-border`, `--cm-text`, `--cm-muted`, `--cm-hover`,
`--cm-accent` (logo mark, Buy button), `--cm-accent-soft` / `--cm-accent-text` (active item, balance pill), `--cm-fab-fg` /
`--cm-fab-shadow` (Buy button), `--cm-radius`, `--cm-nav-radius`, `--cm-nav-text`, `--cm-nav-active`, `--cm-shadow`,
`--cm-nav-shadow`. See `ui/themes/shadcn/styles/customer.css` for a full example.
