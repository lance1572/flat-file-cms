# Flat-file CMS

The web server document root is `public/`. Application data is intentionally outside that directory.

- Markdown pages: `content/pages/*.md`
- Site configuration: `config/site.json`
- Page metadata: a restricted, dependency-free YAML-style front-matter block
- Presentation: `templates/*.php`
- CSS source: `resources/app.css`; compiled output: `public/assets/app.css`

The public front controller permits only lower-case, hyphenated page slugs. The initial page is a clean migration target rather than a byte-for-byte copy of the legacy HTML.

## Local serving

Point Apache/Nginx at the `public/` directory, or use PHP's development server with `public/` as its document root. Do not expose `content/`, `config/`, or `storage/` directly.

Start the local server from the project folder:

```sh
php -S 127.0.0.1:8000 -t public
```

Open [Pages in the admin](http://127.0.0.1:8000/admin/pages) to create, edit, or delete pages. Each page is saved in `content/pages/` and is available at its slug URL. Editing keeps the page's existing slug. The admin is available only through PHP's development server over a loopback connection.

## Tailwind CSS

Install the development dependencies once, then compile the production stylesheet:

```sh
npm install
npm run build:css
```

Use `npm run watch:css` while changing templates. Node and Tailwind are build-time tools only; visitors receive the generated CSS and the CMS remains a plain PHP application at runtime.
