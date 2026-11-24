<?php
declare(strict_types=1);

use FlatfileCms\ContentRepository;
use FlatfileCms\Markdown;

require dirname(__DIR__) . '/app/ContentRepository.php';
require dirname(__DIR__) . '/app/Markdown.php';

$site = json_decode((string) file_get_contents(dirname(__DIR__) . '/config/site.json'), true, 512, JSON_THROW_ON_ERROR);
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$basePath = rtrim((string) ($site['base_path'] ?? ''), '/');
if ($basePath !== '' && str_starts_with($requestPath, $basePath)) {
    $requestPath = substr($requestPath, strlen($basePath)) ?: '/';
}
$slug = trim($requestPath, '/') ?: 'index';

$pages = new ContentRepository(dirname(__DIR__) . '/content/pages');
$page = $pages->find($slug);
if ($page === null || !$page['visible']) {
    http_response_code(404);
    $page = ['title' => 'Page not found', 'description' => '', 'body' => '# Page not found', 'template' => 'default'];
}

$content = Markdown::render($page['body']);
$template = preg_replace('/[^a-z0-9_-]/i', '', (string) $page['template']) ?: 'default';
$templatePath = dirname(__DIR__) . '/templates/' . $template . '.php';
if (!is_file($templatePath)) {
    throw new RuntimeException('Requested template does not exist.');
}
require $templatePath;
