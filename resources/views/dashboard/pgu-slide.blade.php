@extends('layouts.app')

@section('content')
@include('dashboard.partials.pgu-executive-slide-wrap', $pguExecutiveSlide ?? [])
@endsection
