<x-app-layout>
    <div id="app" data-user="{{ json_encode(Auth::user()) }}">
        <historial-content :user="{{ json_encode(Auth::user()) }}" />
    </div>
</x-app-layout>
