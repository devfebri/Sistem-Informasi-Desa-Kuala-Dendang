@extends('layouts.app')
@section('title', 'Dashboard Admin')

@section('content')
    @include('dashboard._statistik', ['peran' => 'admin'])
@endsection
