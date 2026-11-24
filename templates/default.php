<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="<?= htmlspecialchars((string) $page['description'], ENT_QUOTES, 'UTF-8') ?>">
  <title><?= htmlspecialchars((string) $page['title'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars((string) $site['name'], ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="<?= htmlspecialchars((string) $site['base_path'], ENT_QUOTES, 'UTF-8') ?>/assets/app.css">
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased selection:bg-cyan-300 selection:text-slate-950">
  <header class="border-b border-white/10">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-5 lg:px-8">
      <a class="group flex items-center gap-3 text-base font-semibold tracking-tight text-white no-underline" href="<?= htmlspecialchars((string) $site['base_path'], ENT_QUOTES, 'UTF-8') ?: '/' ?>">
        <span class="grid size-8 place-items-center rounded-lg bg-cyan-300 text-sm font-black text-slate-950 transition-transform group-hover:-rotate-6">F</span>
        <?= htmlspecialchars((string) $site['name'], ENT_QUOTES, 'UTF-8') ?>
      </a>
      <span class="hidden text-sm font-medium text-slate-400 sm:block">A simpler way to publish</span>
    </div>
  </header>
  <main class="relative overflow-hidden">
    <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-96 bg-[radial-gradient(ellipse_at_top,rgba(34,211,238,0.18),transparent_65%)]"></div>
    <article class="mx-auto max-w-4xl px-6 py-20 sm:py-28 lg:px-8">
      <div class="mb-7 flex items-center gap-3 text-sm font-semibold uppercase tracking-[0.18em] text-cyan-300">
        <span class="h-px w-8 bg-cyan-300"></span> Flat-file CMS
      </div>
      <h1 class="max-w-3xl text-5xl font-bold tracking-[-0.045em] text-white sm:text-7xl"><?= htmlspecialchars((string) $page['title'], ENT_QUOTES, 'UTF-8') ?></h1>
      <?php if ($page['description'] !== ''): ?>
        <p class="mt-7 max-w-2xl text-lg leading-8 text-slate-300 sm:text-xl"><?= htmlspecialchars((string) $page['description'], ENT_QUOTES, 'UTF-8') ?></p>
      <?php endif; ?>
      <div class="mt-14 rounded-2xl border border-white/10 bg-white/[0.04] p-8 shadow-2xl shadow-cyan-950/20 backdrop-blur sm:p-12">
        <div class="max-w-none">
          <?= $content ?>
        </div>
      </div>
    </article>
  </main>
  <footer class="border-t border-white/10 px-6 py-8 text-center text-sm text-slate-500">Built with PHP, Markdown, and static CSS.</footer>
</body>
</html>
