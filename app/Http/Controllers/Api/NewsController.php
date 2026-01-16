<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NewsController extends Controller
{
    private function saveUploadedFile($file, $folder = 'news')
    {
        if (!$file) return null;

        $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        return $file->storeAs($folder, $fileName, 'public');
    }

    // GET /api/news (Public - chỉ lấy tin đã publish)
    public function index(Request $request)
    {
        return response()->json(
            News::where('status', 1)
                ->latest()
                ->paginate($request->per_page ?? 10)
        );
    }

    // GET /api/admin/news (Admin - lấy tất cả tin)
    public function adminIndex(Request $request)
    {
        return response()->json(
            News::latest()
                ->paginate($request->per_page ?? 10)
        );
    }

    // GET /api/news/{slug}
    public function show($slug)
    {
        return response()->json(
            News::where('slug', $slug)
                ->where('status', 1)
                ->firstOrFail()
        );
    }

    // POST /api/news
    public function store(Request $request)
    {
        $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'required',
            'images'  => 'nullable|image|max:2048',
        ]);

        $imagePath = $request->hasFile('images')
            ? $this->saveUploadedFile($request->file('images'), 'news')
            : null;

        // Tạo slug từ title
        $slug = Str::slug($request->title);
        
        // Đảm bảo slug là unique
        $originalSlug = $slug;
        $count = 1;
        while (News::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        $news = News::create([
            'title'   => $request->title,
            'slug'    => $slug, // ← THÊM SLUG
            'content' => $request->content,
            'images'  => $imagePath,
            'status'  => $request->status ?? 1,
        ]);

        return response()->json($news, 201);
    }

    // POST /api/news/{id} (form-data)
    public function update(Request $request, $id)
    {
        $news = News::findOrFail($id);

        $request->validate([
            'title'   => 'nullable|string|max:255',
            'content' => 'nullable',
            'status'  => 'nullable|in:0,1',
            'images'  => 'nullable|image|max:2048',
        ]);

        $data = $request->only(['title', 'content', 'status']);

        // Nếu có title mới, tạo slug mới
        if ($request->has('title') && $request->title !== $news->title) {
            $slug = Str::slug($request->title);
            
            // Đảm bảo slug unique (trừ bản ghi hiện tại)
            $originalSlug = $slug;
            $count = 1;
            while (News::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                $slug = $originalSlug . '-' . $count;
                $count++;
            }
            
            $data['slug'] = $slug;
        }

        // Xử lý upload ảnh mới
        if ($request->hasFile('images')) {
            if ($news->images) {
                Storage::disk('public')->delete($news->images);
            }

            $data['images'] = $this->saveUploadedFile(
                $request->file('images'),
                'news'
            );
        }

        $news->update($data);

        return response()->json($news);
    }

    // DELETE /api/news/{id}
    public function destroy($id)
    {
        $news = News::findOrFail($id);

        if ($news->images) {
            Storage::disk('public')->delete($news->images);
        }

        $news->delete();

        return response()->json([
            'message' => 'News deleted successfully'
        ]);
    }
}