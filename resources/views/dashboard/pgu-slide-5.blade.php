@extends('layouts.app')

@section('content')
@include('dashboard.partials.pgu-slide-5-wrap', $pguSlide5 ?? [])
@endsection
