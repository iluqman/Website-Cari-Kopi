@extends('layouts.dashboard')
@section('title', 'Edit Kafe')
@section('panel')
    <div class="page-head"><h1>Edit kafe</h1></div>
    <form action="{{ route('posts.update', $post) }}" method="POST" enctype="multipart/form-data" class="panel panel-body">
        @csrf @method('PATCH')
        @include('posts._form')
    </form>
@endsection
