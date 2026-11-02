@if (session('login_results'))
    @php $results = collect(session('login_results')); @endphp
    <div class="mb-4 rounded-md border border-primary-200 bg-primary-50/60 p-4">
        <p class="text-[14px] font-semibold text-neutral-900">{{ t('memberLogin.resultsTitle') }}</p>
        <p class="mt-0.5 text-[12.5px] text-neutral-600">{{ t('memberLogin.resultsHint') }}</p>
        @if ($results->isEmpty())
            <p class="mt-3 text-[13px] text-neutral-700">{{ t('memberLogin.nobodyMissing') }}</p>
        @else
            <div class="mt-3 overflow-x-auto">
                <table class="w-full text-[13px]">
                    <thead><tr class="text-left text-[11px] uppercase tracking-wide text-neutral-500">
                        <th class="py-1.5 pr-4">{{ t('common.member') }}</th><th class="py-1.5 pr-4">{{ t('memberLogin.username') }}</th>
                        <th class="py-1.5 pr-4">{{ t('memberLogin.temporaryPassword') }}</th><th class="py-1.5 pr-4">SMS</th><th class="py-1.5">{{ t('common.email') }}</th>
                    </tr></thead>
                    <tbody class="divide-y divide-primary-100">
                        @foreach ($results as $r)
                            <tr>
                                <td class="py-1.5 pr-4 font-medium text-neutral-800">{{ $r['name'] }}</td>
                                <td class="py-1.5 pr-4 font-mono">{{ $r['username'] }}</td>
                                <td class="py-1.5 pr-4 font-mono">
                                    @if ($r['status'] === 'staff') <span class="font-sans text-neutral-500">{{ t('memberLogin.usesStaffLogin') }}</span>
                                    @elseif ($r['status'] === 'no_phone') <span class="font-sans text-red-700">{{ t('memberLogin.noPhone') }}</span>
                                    @else {{ $r['password'] }} @endif
                                </td>
                                @foreach (['sms', 'email'] as $channel)
                                    <td class="py-1.5 pr-4">
                                        @if ($r[$channel] === true)<x-badge tone="success">{{ t('memberLogin.sent') }}</x-badge>
                                        @elseif ($r[$channel] === false)<x-badge tone="danger">{{ t('memberLogin.failed') }}</x-badge>
                                        @else<span class="text-neutral-400">{{ in_array($r['status'], ['staff', 'no_phone']) ? '—' : t('memberLogin.notSent') }}</span>@endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif
