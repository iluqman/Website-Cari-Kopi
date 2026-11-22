<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FiltersLists;
use App\Models\Post;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    use FiltersLists;

    private const RATING_SORTS = ['rating_desc', 'rating_asc'];

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search'));
        $sort = $this->ratingSort($request);

        $posts = Post::query()
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->when($search !== '', function ($query) use ($search) {
                $like = $this->like($search);

                $query->where(function ($query) use ($like) {
                    $query->where('title', 'like', $like)
                        ->orWhere('body', 'like', $like)
                        ->orWhere('alamat', 'like', $like)
                        ->orWhere('kota', 'like', $like);
                });
            })
            ->sortByRating($sort)
            ->latest()
            ->paginate(9)
            ->withQueryString();

        return view('cafes.index', [
            'posts' => $posts,
            'search' => $search,
            'sort' => $sort,
        ]);
    }

    public function show(Request $request, Post $post)
    {
        $search = trim((string) $request->query('search'));
        $sort = $this->ratingSort($request);

        $post->loadAvg('reviews', 'rating')->loadCount('reviews');

        $products = $post->products()
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->when($search !== '', function ($query) use ($search) {
                $like = $this->like($search);

                $query->where(function ($query) use ($like) {
                    $query->where('title', 'like', $like)->orWhere('body', 'like', $like);
                });
            })
            ->sortByRating($sort)
            ->orderBy('id')
            ->get();

        return view('cafes.show', [
            'post' => $post,
            'products' => $products,
            'productsTotal' => $post->products()->count(),
            'search' => $search,
            'sort' => $sort,
            'reviews' => $post->reviews()->with('user')->latest()->get(),
        ]);
    }

    /** Hanya nilai yang dikenal; selain itu urutan default (terbaru). */
    private function ratingSort(Request $request): string
    {
        $sort = (string) $request->query('sort');

        return in_array($sort, self::RATING_SORTS, true) ? $sort : '';
    }
}
