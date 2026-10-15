</main>
<footer class="bg-white border-t mt-8">
  <div class="max-w-6xl mx-auto px-4 py-6 text-sm text-gray-600 flex flex-wrap gap-4">
    <span>&copy; <?= date('Y') ?> <?= \App\Core\View::e($config['app']['name'] ?? 'TokoMVP') ?></span>
    <span class="flex-1"></span>
    <a href="/products">Katalog</a>
    <a href="/cart">Keranjang</a>
  </div>
</footer>
<script src="/assets/js/app.js"></script>
</body>
</html>
