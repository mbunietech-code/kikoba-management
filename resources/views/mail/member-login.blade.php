<x-mail::message>
# {{ __('k.memberLogin.emailGreeting', ['name' => $memberName], 'sw') }}

{{ __('k.memberLogin.emailIntro', ['org' => $groupName], 'sw') }}

<x-mail::panel>
**{{ __('k.memberLogin.username', [], 'sw') }}:** {{ $username }}<br>
**{{ __('k.memberLogin.temporaryPassword', [], 'sw') }}:** {{ $temporaryPassword }}
</x-mail::panel>

{{ __('k.memberLogin.emailChange', [], 'sw') }}

<x-mail::button :url="$loginUrl">
{{ __('k.memberLogin.signIn', [], 'sw') }}
</x-mail::button>

{{ __('k.memberLogin.emailIgnore', [], 'sw') }}

{{ $groupName }}
</x-mail::message>
