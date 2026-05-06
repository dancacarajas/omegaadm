@extends('layouts.app')

@section('content')
@include('dashboard.partials.pgu-slide-2-wrap', $pguSlide2 ?? [])
@endsection
