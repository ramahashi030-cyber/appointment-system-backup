# Admin Panel — client code conventions

Rules for code added under `resources/views/admin/`. They exist because anything
rendered into the page is readable through DevTools, so the *placement* of logic
is what determines how much is exposed — not how the code is written.

## 1. Decide placement first

| Kind of code | Where it goes |
|---|---|
| Authorization, permissions, ownership, role checks | Server-side only (controller / policy / middleware). Never a gate in JS. |
| Business rules, pricing, quotas, state transitions, anything that must hold | Server-side. Blade/JS may mirror it for UX, never as the authority. |
| Validation | Server-side is authoritative. A client mirror is a convenience only — assume it is bypassed. |
| Database reads/writes, deletes, status changes | Server-side, behind POST + CSRF. |
| UI behavior (modals, filters, paging, spinners, toasts) | Client JS is fine. |

A hidden or obfuscated JS check is **not** a security boundary. If removing the
check from DevTools would grant access, the check is in the wrong place.

## 2. Send the browser only what the interface needs

- Do not embed passwords, API keys, private keys, credentials, session secrets,
  or any token not intended for client use. `csrf_token()` *is* intended for the
  client and is required — it is not a secret to hide.
- Do not dump a whole collection when the view needs a few fields or a count.
- Prefer `textContent` over `innerHTML`. When building HTML from server data,
  pass every dynamic value through an `escapeHtml()` helper first.
- Do not record internal structure (controller/model/route names, file paths,
  rule constants) in anything the page renders.

## 3. Comment convention

- Inside `<script>`: **no comments.** `//` and `/* */` are shipped to the
  browser verbatim.
- To document a block, use a Blade comment `{{-- ... --}}`. It is stripped during
  compilation and never reaches the client.
- PHP `//` comments in Blade are server-side and safe.

## 4. Never do this

DevTools/F12/Ctrl+U blocking, right-click blocking, DevTools detection, alert
loops, redirects on inspect, disabling selection or copy/paste as "security".
They do not hide source and they break normal use.

## 5. Before committing client code

1. Is any security or business decision relying on this? Move it server-side.
2. Does it expose anything the interface does not need? Remove it.
3. Does it render server data as HTML? Escape it.
4. Is it commented inside `<script>`? Convert to `{{-- --}}`.
5. Does it break when a user disables JS? The server path must still hold.
