<x-mail::message>
    {{-- 1. CUSTOM HEADER (Ganti Logo Laravel dengan Logo Kamu) --}}
    <x-slot:header>
        <x-mail::header :url="config('app.url')">
            {{-- Ubah ke gambar favicon kamu --}}
            <img src="{{ asset('logo1.webp') }}" class="logo" alt="Logo Perusahaan" style="max-height: 40px; width: auto;">
        </x-mail::header>
    </x-slot:header>

    {{-- Greeting --}}
    @if (! empty($greeting))
    # {{ $greeting }}
    @else
    @if ($level === 'error')
    # @lang('Whoops!')
    @else
    # @lang('Hello!')
    @endif
    @endif

    {{-- Intro Lines --}}
    @foreach ($introLines as $line)
    {{ $line }}

    @endforeach

    {{-- Action Button --}}
    @isset($actionText)
    <?php
    $color = match ($level) {
        'success', 'error' => $level,
        default => 'primary',
    };
    ?>
    <x-mail::button :url="$actionUrl" :color="$color">
        {{ $actionText }}
    </x-mail::button>
    @endisset

    {{-- Outro Lines --}}
    @foreach ($outroLines as $line)
    {{ $line }}

    @endforeach

    {{-- Salutation --}}
    @if (! empty($salutation))
    {{ $salutation }}
    @else
    @lang('Regards,')<br>
    {{ config('app.name') }}
    @endif

    {{-- Subcopy --}}
    @isset($actionText)
    <x-slot:subcopy>
        @lang(
        "If you're having trouble clicking the \":actionText\" button, copy and paste the URL below\n".
        'into your web browser:',
        [
        'actionText' => $actionText,
        ]
        ) <span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
    </x-slot:subcopy>
    @endisset

    {{-- 2. CUSTOM FOOTER (Ganti tulisan © 2026 Laravel jadi Nama Perusahaan) --}}
    <x-slot:footer>
        <x-mail::footer>
            © {{ date('Y') }} PT Schlemmer Automotive Indonesia. All rights reserved.
        </x-mail::footer>
    </x-slot:footer>

</x-mail::message>