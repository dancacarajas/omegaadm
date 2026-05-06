@extends('layouts.app')

@section('content')
@include('dashboard.partials.pgu-slide-3-wrap', $pguSlide3 ?? [])
@endsection
