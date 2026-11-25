<?php
declare(strict_types=1);

use FlatfileCms\ContentRepository;
use FlatfileCms\Markdown;
use FlatfileCms\PageAdminController;

require dirname(__DIR__) . '/app/ContentRepository.php';
require dirname(__DIR__) . '/app/Markdown.php';

$site = json_decode((string) file_get_contents(dirname(__DIR__) . '/config/site.json'), true, 512, JSON_THROW_ON_ERROR);
if (PHP_SAPI === 'cli-server') {
    $site['base_path'] = '';
}
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$basePath = rtrim((string) ($site['base_path'] ?? ''), '/');
if ($basePath !== '' && str_starts_with($requestPath, $basePath)) {
    $requestPath = substr($requestPath, strlen($basePath)) ?: '/';
}
$slug = trim($requestPath, '/') ?: 'index';

$pages = new ContentRepository(dirname(__DIR__) . '/content/pages');
if (in_array($slug, ['admin', 'admin/pages'], true)) {
    $localHost = preg_match('/\A(?:localhost|127\.0\.0\.1|\[::1\])(?::[0-9]+)?\z/i', $_SERVER['HTTP_HOST'] ?? '');
    if (PHP_SAPI !== 'cli-server' || !$localHost || !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
        http_response_code(404);
        exit('Page not found');
    }
    if ($slug === 'admin') {
        header('Location: /admin/pages', true, 303);
        return;
    }
    require dirname(__DIR__) . '/app/PageAdminController.php';
    (new PageAdminController($pages))->handle();
    return;
}
$page = $pages->find($slug);
if ($page === null || !$page['visible']) {
    http_response_code(404);
    $page = ['title' => 'Page not found', 'description' => '', 'body' => '# Page not found', 'template' => 'default'];
}

$content = Markdown::render($page['body']);
$navigation = [];
foreach ($pages->slugs() as $menuSlug) {
    if ($menuSlug === 'admin') {
        continue;
    }
    try {
        $menuPage = $menuSlug === $slug ? $page : $pages->find($menuSlug);
    } catch (RuntimeException $exception) {
        error_log($exception->getMessage());
        continue;
    }
    if (!($menuPage['visible'] ?? false)) {
        continue;
    }
    $navigation[$menuSlug] = [
        'label' => $menuSlug === 'index' ? 'Home' : $menuPage['title'],
        'url' => $basePath . ($menuSlug === 'index' ? '/' : '/' . $menuSlug),
    ];
}
if (isset($navigation['index'])) {
    $navigation = ['index' => $navigation['index']] + $navigation;
}

$template = preg_replace('/[^a-z0-9_-]/i', '', (string) $page['template']) ?: 'default';
$templatePath = dirname(__DIR__) . '/templates/' . $template . '.php';
if (!is_file($templatePath)) {
    throw new RuntimeException('Requested template does not exist.');
}
require $templatePath;
