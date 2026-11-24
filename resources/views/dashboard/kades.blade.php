@extends('layouts.app')
@section('title', 'Dashboard Kepala Desa')

@section('content')
    @include('dashboard._statistik', ['peran' => 'kades'])
@endsection
