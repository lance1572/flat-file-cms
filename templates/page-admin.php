<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pages</title>
  <style>
    :root { color-scheme: dark; font: 16px/1.5 system-ui, sans-serif; background: #020617; color: #f1f5f9; }
    main { max-width: 44rem; margin: 3rem auto; padding: 0 1.25rem; }
    a { color: #67e8f9; }
    form { background: #0f172a; padding: clamp(1rem, 4vw, 2rem); border: 1px solid #334155; border-radius: 1rem; }
    label { display: block; margin-bottom: .35rem; font-weight: 600; }
    input, textarea { box-sizing: border-box; width: 100%; margin-bottom: 1rem; padding: .65rem; border: 1px solid #64748b; border-radius: .4rem; background: #020617; color: inherit; font: inherit; }
    textarea { resize: vertical; font-family: monospace; }
    small { color: #cbd5e1; }
    button { padding: .65rem 1rem; border: 0; border-radius: .4rem; background: #67e8f9; color: #082f49; font: inherit; font-weight: 700; cursor: pointer; }
    .pages { padding: 0; list-style: none; }
    .pages li { display: flex; flex-wrap: wrap; justify-content: space-between; gap: .5rem 1rem; padding: .75rem 0; border-bottom: 1px solid #334155; }
    .actions { display: flex; gap: 1rem; }
    .delete { background: #fecaca; color: #450a0a; margin-right: .75rem; }
    [role="alert"] { color: #fecaca; }
  </style>
</head>
<body>
  <main>
    <a href="/">Back to site</a>
    <h1>Pages</h1>
    <?php if ($notice !== ''): ?>
      <p role="status"><?= $escape($notice) ?></p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
      <p role="alert"><?= $escape($error) ?></p>
    <?php endif; ?>
    <?php if ($action === 'delete'): ?>
      <h2>Delete <?= $escape($fields['slug']) ?>?</h2>
      <form method="post" action="/admin/pages">
        <input type="hidden" name="token" value="<?= $escape($token) ?>">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="slug" value="<?= $escape($fields['slug']) ?>">
        <p>This permanently removes the Markdown file.</p>
        <button class="delete" type="submit">Delete page</button>
        <a href="/admin/pages">Cancel</a>
      </form>
    <?php else: ?>
    <?php if ($action === 'create'): ?>
      <?php if ($slugs === []): ?>
        <p>No pages yet.</p>
      <?php else: ?>
        <ul class="pages">
          <?php foreach ($slugs as $slug): ?>
            <?php $url = $slug === 'index' ? '/' : '/' . $slug; ?>
            <li>
              <span><?= $escape($slug) ?></span>
              <div class="actions">
                <a href="<?= $escape($url) ?>" aria-label="View <?= $escape($slug) ?>">View</a>
                <a href="/admin/pages?action=edit&amp;slug=<?= $escape($slug) ?>" aria-label="Edit <?= $escape($slug) ?>">Edit</a>
                <a href="/admin/pages?action=delete&amp;slug=<?= $escape($slug) ?>" aria-label="Delete <?= $escape($slug) ?>">Delete</a>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    <?php endif; ?>
    <h2><?= $action === 'edit' ? 'Edit ' . $escape($fields['slug']) : 'Create a page' ?></h2>
    <form method="post" action="/admin/pages">
      <input type="hidden" name="token" value="<?= $escape($token) ?>">
      <input type="hidden" name="action" value="<?= $escape($action) ?>">
      <label for="title">Title</label>
      <input id="title" name="title" value="<?= $escape($fields['title']) ?>" required>
      <?php if ($action === 'create'): ?>
        <label for="slug">Slug</label>
        <small id="slug-help">Use lowercase letters, numbers, and hyphens.</small>
        <input id="slug" name="slug" value="<?= $escape($fields['slug']) ?>" placeholder="about-us" pattern="[a-z0-9][a-z0-9\-]{0,79}" maxlength="80" aria-describedby="slug-help" required>
      <?php else: ?>
        <input type="hidden" name="slug" value="<?= $escape($fields['slug']) ?>">
      <?php endif; ?>
      <label for="description">Description <small>(optional)</small></label>
      <input id="description" name="description" value="<?= $escape($fields['description']) ?>">
      <label for="body">Markdown content</label>
      <textarea id="body" name="body" rows="12"><?= $escape($fields['body']) ?></textarea>
      <button type="submit"><?= $action === 'edit' ? 'Save changes' : 'Publish page' ?></button>
      <?php if ($action === 'edit'): ?>
        <a href="/admin/pages">Cancel</a>
      <?php endif; ?>
    </form>
    <?php endif; ?>
  </main>
</body>
</html>
