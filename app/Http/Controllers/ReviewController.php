<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $request->validate([
            'comment' => 'required|string|max:500',
            'rating' => 'required|integer|min:1|max:5',
        ]);
        
        $review = new Review([
            'comment' => $request->comment,
            'rating' => $request->rating,
            'user_id' => Auth::id(),
        ]);
        
        $product->reviews()->save($review);
        
        return back()->with('success', 'Review submitted successfully!');
    }
}