# Contributing to PHPNuxBill Ultimate

Thanks for helping. This page says how to set up, what a good change looks like, and where the longer guides are.

## Set up

PHP 8.2 or newer, MySQL, and the PDO, MySQLi, GD, cURL, ZIP and mbstring extensions. The repository folder is the web root.
Point a local PHP server or a virtual host at it, open `/install`, and sign in. There is no build step, no test suite and no
linter, so a change is checked by using the page it touches.

## Making a change

- Keep one concern per pull request, and open it against `main`.
- A visible change needs the controller edit and the matching `.tpl` template.
- Wrap every visible string in `Lang::T('English text')`.
- Read user input with `_post()`, `_get()` or `_req()`, which only trim: escape what you print and bind what you query.
- A new table or column goes in `install/phpnuxbill.sql` and in a new version key in `system/updates.json`, written so that
  running it twice does nothing.
- New PHP files carry the project header comment.
- Add a line to `CHANGELOG.md` under *Unreleased*.

## Changing how it looks

Read [CSS and UI conventions](https://phpnuxbill.org/docs/extending/css-and-ui/) first. In short:

- Scope rules by the skin's own body class, never by excluding a named theme.
- Use colour tokens, not fixed colours, and check **light and dark mode** and a **390px** wide screen.
- Build list pages from the shared `ls-*` list kit.
- After editing a stylesheet, bump its `?version` in the header template that loads it.

## Extensions

Plugins, payment gateways, themes and device drivers are built as extensions, so they update without touching core. See
`PLUGINS.md` and the [extending guides](https://phpnuxbill.org/docs/extending/overview/).

## Pull request

Describe what changed and why, and add a screenshot in each mode for a visual change. Say how you checked it, and what you did
not check.
