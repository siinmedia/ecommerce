<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Models\Product;

class StoreController
{
    private array $config;

    public function __construct()
    {
        $this->config = require Database::configPath();
    }

    private function base(string $title, string $desc = ''): array
    {
        return [
            'title' => $title,
            'meta_desc' => $desc ?: 'Belanja produk berkualitas dengan pengiriman ke seluruh Indonesia.',
            'canonical' => $this->config['app']['site_url'] . ($_SERVER['REQUEST_URI'] ?? '/'),
            'user' => Auth::user(),
            'config' => $this->config,
            'flash_success' => \App\Core\Helpers::flash('success'),
            'flash_error' => \App\Core\Helpers::flash('error'),
        ];
    }

    public function home(array $p = []): void
    {
        $latest = Product::list([], 1, 8);
        $cats = Product::categories();
        View::layout('store/home', $this->base('Belanja Online Mudah dan Aman') + [
            'products' => $latest['items'], 'categories' => $cats,
        ]);
    }

    public function catalog(array $p = []): void
    {
        $filters = [
            'q' => trim($_GET['q'] ?? ''),
            'category' => trim($_GET['category'] ?? ''),
            'min_price' => trim($_GET['min'] ?? ''),
            'max_price' => trim($_GET['max'] ?? ''),
            'sort' => trim($_GET['sort'] ?? 'newest'),
        ];
        $page = max(1, (int)($_GET['page'] ?? 1));
        $res = Product::list($filters, $page, 12);
        View::layout('store/catalog', $this->base('Katalog Produk') + [
            'products' => $res['items'], 'total' => $res['total'],
            'page' => $page, 'perPage' => 12, 'filters' => $filters,
            'categories' => Product::categories(),
        ]);
    }

    public function search(array $p = []): void
    {
        $_GET['q'] = $_GET['q'] ?? $_GET['query'] ?? '';
        $this->catalog();
    }

    public function category(array $p): void
    {
        $_GET['category'] = $p['slug'] ?? '';
        $this->catalog();
    }

    public function product(array $p): void
    {
        $product = Product::findBySlug($p['slug'] ?? '');
        if (!$product || $product['status'] !== 'published') {
            http_response_code(404);
            echo View::render('errors/404', ['title' => 'Produk tidak ditemukan']);
            return;
        }
        // Structured data (real data only)
        $reviews = Database::pdo()->prepare(
            'SELECT rating, COUNT(*) c FROM product_reviews WHERE product_id = ? AND status = "published" GROUP BY rating'
        );
        $reviews->execute([$product['id']]);
        View::layout('store/product', $this->base($product['name'], mb_substr(strip_tags($product['description']), 0, 160)) + [
            'product' => $product,
        ]);
    }

    public function sitemap(array $p = []): void
    {
        header('Content-Type: application/xml; charset=utf-8');
        $base = rtrim($this->config['app']['site_url'], '/');
        $rows = Database::pdo()->query(
            "SELECT slug, updated_at FROM products WHERE status = 'published' ORDER BY id DESC LIMIT 1000"
        )->fetchAll();
        $cats = Database::pdo()->query('SELECT slug FROM categories')->fetchAll();
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        echo "<url><loc>{$base}/</loc><changefreq>daily</changefreq></url>\n";
        echo "<url><loc>{$base}/products</loc><changefreq>daily</changefreq></url>\n";
        foreach ($cats as $c) {
            echo '<url><loc>' . $base . '/categories/' . htmlspecialchars($c['slug']) . '</loc></url>' . "\n";
        }
        foreach ($rows as $r) {
            echo '<url><loc>' . $base . '/products/' . htmlspecialchars($r['slug']) . '</loc><lastmod>' . date('Y-m-d', strtotime($r['updated_at'])) . '</lastmod></url>' . "\n";
        }
        echo '</urlset>';
    }

    public function robots(array $p = []): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        $base = rtrim($this->config['app']['site_url'], '/');
        echo "User-agent: *\nAllow: /\nDisallow: /cart\nDisallow: /checkout\nDisallow: /account\nDisallow: /admin\nDisallow: /dev\nSitemap: {$base}/sitemap.xml\n";
    }
}
