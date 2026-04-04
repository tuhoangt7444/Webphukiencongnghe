<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Post;

final class BlogController extends Controller
{
    public function show(string $slug): void
    {
        $post = Post::findPublishedBySlug($slug);
        if (!$post) {
            $this->response->send('Bài viết không tồn tại', 404);
            return;
        }

        $relatedProducts = Post::relatedProducts((int)($post['id'] ?? 0), 4);

        $this->view('blog/show', [
            'title' => (string)$post['title'],
            'post' => $post,
            'relatedProducts' => $relatedProducts,
        ]);
    }
}
