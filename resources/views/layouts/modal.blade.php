{{--
    Bare layout used when a patient page is loaded inside the dashboard modal
    (fetch sends X-Requested-With, so request()->ajax() is true).
    Outputs only the page's styles and content, with no sidebar, header or scripts.
--}}
@hasSection('styles')
    <style>
        @yield('styles')
    </style>
@endif

@yield('content')