<?php
declare(strict_types=1);

namespace FlatfileCms;

use InvalidArgumentException;
use RuntimeException;

final class PageAdminController
{
    private const EMPTY_FIELDS = ['title' => '', 'slug' => '', 'description' => '', 'body' => ''];
    private const SAVE_STATUS = ['create' => 'created', 'edit' => 'updated', 'delete' => 'deleted'];

    public function __construct(private readonly ContentRepository $pages)
    {
    }

    public function handle(): void
    {
        header('Cache-Control: no-store');
        $token = $this->startSession();
        if ($token === null) {
            http_response_code(500);
            echo 'Unable to start the admin session. Check that the storage directory is writable.';
            return;
        }

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method === 'POST') {
            $this->submit($token);
            return;
        }
        if (!in_array($method, ['GET', 'HEAD'], true)) {
            header('Allow: GET, HEAD, POST');
            http_response_code(405);
            return;
        }
        $this->show($token);
    }

    private function startSession(): ?string
    {
        $path = dirname(__DIR__) . '/storage/admin-sessions';
        if (!is_dir($path) && !@mkdir($path, 0700) && !is_dir($path)) {
            return null;
        }
        if (!@session_start([
            'save_path' => $path,
            'use_strict_mode' => 1,
            'cookie_httponly' => true,
            'cookie_samesite' => 'Strict',
        ])) {
            return null;
        }
        return $_SESSION['page_admin_token'] ??= bin2hex(random_bytes(32));
    }

    private function submit(string $token): void
    {
        $submittedToken = $_POST['token'] ?? null;
        if (!is_string($submittedToken) || !hash_equals($token, $submittedToken)) {
            http_response_code(403);
            echo 'Form expired. Reload /admin/pages and try again.';
            return;
        }

        $action = $_POST['action'] ?? '';
        if (!is_string($action) || !isset(self::SAVE_STATUS[$action])) {
            http_response_code(400);
            echo 'Invalid page action.';
            return;
        }

        $fields = self::EMPTY_FIELDS;
        foreach (array_keys($fields) as $name) {
            $value = $_POST[$name] ?? '';
            $fields[$name] = is_string($value) ? $value : '';
        }
        $slug = trim($fields['slug']);
        $title = trim($fields['title']);
        $description = trim($fields['description']);

        try {
            $saved = match ($action) {
                'create' => $this->pages->create($slug, $title, $description, $fields['body']),
                'edit' => $this->pages->update($slug, $title, $description, $fields['body']),
                'delete' => $this->pages->delete($slug),
            };
            if ($saved) {
                header('Location: /admin/pages?status=' . self::SAVE_STATUS[$action], true, 303);
                return;
            }
            http_response_code($action === 'create' ? 409 : 404);
            $error = $action === 'create' ? 'A page already uses that slug.' : 'Page not found.';
        } catch (InvalidArgumentException $exception) {
            http_response_code(422);
            $error = $exception->getMessage();
        } catch (RuntimeException $exception) {
            http_response_code(500);
            $error = $exception->getMessage();
        }

        $this->render($token, $action, $fields, $error);
    }

    private function show(string $token): void
    {
        $action = $_GET['action'] ?? '';
        if (!in_array($action, ['edit', 'delete'], true)) {
            $this->render($token);
            return;
        }

        $slug = is_string($_GET['slug'] ?? null) ? $_GET['slug'] : '';
        if ($action === 'delete' && in_array($slug, $this->pages->slugs(), true)) {
            $this->render($token, 'delete', ['slug' => $slug]);
            return;
        }

        try {
            $page = $action === 'edit' ? $this->pages->find($slug) : null;
        } catch (RuntimeException $exception) {
            http_response_code(422);
            $this->render($token, error: $exception->getMessage());
            return;
        }

        if ($page === null) {
            http_response_code(404);
            $this->render($token, error: 'Page not found.');
            return;
        }

        $page['slug'] = $slug;
        $this->render($token, 'edit', $page);
    }

    private function render(string $token, string $action = 'create', array $fields = self::EMPTY_FIELDS, string $error = ''): void
    {
        $slugs = $action === 'create' ? $this->pages->slugs() : [];
        $notice = match ($_GET['status'] ?? '') {
            'created' => 'Page created.',
            'updated' => 'Page updated.',
            'deleted' => 'Page deleted.',
            default => '',
        };
        $escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        require dirname(__DIR__) . '/templates/page-admin.php';
    }
}
