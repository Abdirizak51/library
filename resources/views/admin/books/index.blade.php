@extends('layouts.app')
@section('title', 'Add Book')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-12">
    <div class="mb-8">
        <a href="{{ route('admin.books.index') }}" class="text-xs text-stone-400 hover:text-amber-600 mb-1 block">← All Books</a>
        <h1 class="section-title">Add New Book</h1>
    </div>

    <form method="POST" action="{{ route('admin.books.store') }}"
          class="space-y-6 bg-white dark:bg-stone-900 rounded-2xl border border-stone-200 dark:border-stone-800 p-8">
        @csrf

        @if($errors->any())
        <div class="p-4 bg-red-50 rounded-xl border border-red-200 text-sm text-red-700">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div>
            <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1">Title *</label>
            <input type="text" name="title" value="{{ old('title') }}"
                   class="search-input w-full" required>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1">Category *</label>
                <select name="category_id" class="search-input w-full" required>
                    <option value="">-- Select --</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1">Status</label>
                <select name="status" class="search-input w-full">
                    <option value="active">Active</option>
                    <option value="draft">Draft</option>
                    <option value="hidden">Hidden</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1">Description</label>
            <textarea name="description" rows="4" class="search-input w-full resize-y">{{ old('description') }}</textarea>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1">Published Year</label>
                <input type="number" name="published_year" value="{{ old('published_year') }}"
                       min="1000" max="2100" class="search-input w-full">
            </div>
            <div>
                <label class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-1">Cover Image URL</label>
                <input type="text" name="cover_image" value="{{ old('cover_image') }}"
                       class="search-input w-full" placeholder="https://...">
            </div>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="btn-primary">Create Book</button>
            <a href="{{ route('admin.books.index') }}" class="btn-outline">Cancel</a>
        </div>
    </form>
</div>
@endsection