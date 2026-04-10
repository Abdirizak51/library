@extends('layouts.app')
@section('title', 'Edit Book')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-12">
    <div class="mb-8">
        <a href="{{ route('admin.books.index') }}" class="text-xs text-stone-400 hover:text-amber-600 mb-1 block">← All Books</a>
        <h1 class="section-title">Edit: {{ $book->title }}</h1>
    </div>

    @if(session('success'))
    <div class="alert-success mb-6">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.books.update', $book) }}"
          class="space-y-6 bg-white dark:bg-stone-900 rounded-2xl border border-stone-200 dark:border-stone-800 p-8">
        @csrf
        @method('PATCH')

        <div>
            <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1">Title *</label>
            <input type="text" name="title" value="{{ old('title', $book->title) }}"
                   class="search-input w-full" required>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1">Category *</label>
                <select name="category_id" class="search-input w-full" required>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ old('category_id', $book->category_id) == $cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1">Status</label>
                <select name="status" class="search-input w-full">
                    @foreach(['active'=>'Active','draft'=>'Draft','hidden'=>'Hidden'] as $val=>$label)
                    <option value="{{ $val }}" {{ old('status', $book->status) === $val ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1">Description</label>
            <textarea name="description" rows="4" class="search-input w-full resize-y">{{ old('description', $book->description) }}</textarea>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1">Published Year</label>
                <input type="number" name="published_year" value="{{ old('published_year', $book->published_year) }}"
                       class="search-input w-full">
            </div>
            <div>
                <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1">Cover Image URL</label>
                <input type="text" name="cover_image" value="{{ old('cover_image', $book->cover_image) }}"
                       class="search-input w-full">
            </div>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="btn-primary">Save Changes</button>
            <a href="{{ route('admin.books.index') }}" class="btn-outline">Cancel</a>
            <form method="POST" action="{{ route('admin.books.destroy', $book) }}" class="ml-auto"
                  onsubmit="return confirm('Delete this book?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn-outline text-red-500 border-red-300">Delete</button>
            </form>
        </div>
    </form>
</div>
@endsection