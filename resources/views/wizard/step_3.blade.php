@extends("wizard.master")

@section("content")
                    <h1 class="text-2xl font-bold mb-10">
                        {{ __("Sürüm değişiklikleri") }}
                    </h1>
                    
                    <iframe src="/wizard/changelogs/{{ app()->getLocale() }}.html" frameborder="0" style="width: 100%; height: 400px; margin-bottom: 70px;"></iframe>
@endsection