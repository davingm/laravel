# davingm-welcome

Animated Laravel starter page with a WebGL particle mountain range.
Drop `<x-welcomepage>` in your Blade view and delete it when you're ready to build.

## Usage

```bash
npm install davingm-welcome
```

Import in your application entry JS (e.g. `src/assets/js/app.js`):

```js
import 'davingm-welcome';
```

In your Blade page (`src/pages/index.blade.php`):

```blade
@extends('layouts.app')

@section('content')
<x-welcomepage />
@endsection
```

When you are ready to build your own page, delete the `<x-welcomepage />` line.
Your application will have zero welcome styles or scripts — the CSS and JS are
bundled inside the package and not injected unless the component is present.

## How it works

- The package ships a self-contained JS bundle (`dist/welcomepage.js`) and a
  CSS file (`dist/welcomepage.css`).
- Importing `davingm-welcome` boots a WebGL2 particle mountain range bound to
  `[data-welcomepage-canvas]` and `[data-welcomepage-slot]` on the page.
- All styles are scoped to `[data-welcomepage]` — no global CSS pollution.
- If WebGL2 is unavailable the canvas is removed silently; the page still works.

## License

AGPL 3.0
