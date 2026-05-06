@extends('layouts.app')

@section('content')
@include('dashboard.partials.pgu-slide-4-wrap', $pguSlide4 ?? [])
@endsection
