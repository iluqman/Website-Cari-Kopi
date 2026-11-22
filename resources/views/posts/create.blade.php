@extends('layouts.dashboard')
@section('title', 'Tambah Kafe')
@section('panel')
    <div class="page-head"><h1>Tambah kafe</h1></div>
    <form action="{{ route('posts.store') }}" method="POST" enctype="multipart/form-data" class="panel panel-body">
        @csrf
        @include('posts._form')
    </form>
@endsection
