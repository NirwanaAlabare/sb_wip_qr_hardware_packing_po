@extends('layouts.index')

@section('content')
    {{-- Production Panel Livewire --}}
    @livewire('production-panel-return')

@endsection

@section('custom-script')
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            $('.select2').select2({
                theme: "bootstrap-5",
                width: $( this ).data( 'width' ) ? $( this ).data( 'width' ) : $( this ).hasClass( 'w-100' ) ? '100%' : 'style',
                placeholder: $( this ).data( 'placeholder' ),
            });
        });

        Livewire.on('alert', (type, message) => {
            showNotification(type, message);
        });
    </script>
@endsection
