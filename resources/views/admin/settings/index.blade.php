@extends('admin.layout')

@section('title', 'Governance & Security Audit')
@section('header-tag', 'System Governance')
@section('header-title', 'Admins & Audit Logs')

@section('content')
<div class="space-y-8 max-w-7xl mx-auto">

    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-[#E8E5DF]">
        <div>
            <h1 class="text-2xl font-heading font-semibold text-[#121417] tracking-tight">
                Governance, Roles & Audit Ledger
            </h1>
            <p class="text-xs text-[#52565E] mt-1">
                Authorized platform operators, role-based access control, and tamper-resistant security action logs.
            </p>
        </div>
    </div>

    <!-- Admin Operators Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- Authorized Admin List (7 cols) -->
        <div class="lg:col-span-7 bg-white border border-[#E8E5DF] rounded-xl overflow-hidden shadow-xs">
            <div class="p-5 border-b border-[#E8E5DF]">
                <h2 class="text-base font-heading font-semibold text-[#121417]">
                    Authorized Administrators
                </h2>
                <p class="text-xs text-[#52565E]">Accounts authorized to request OTP codes and access the operations console</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#F7F5F0] text-[#52565E] font-heading font-medium uppercase text-[11px] tracking-wider border-b border-[#E8E5DF]">
                        <tr>
                            <th class="py-3 px-4 font-semibold">Operator</th>
                            <th class="py-3 px-4 font-semibold">Role</th>
                            <th class="py-3 px-4 font-semibold">Last Login</th>
                            <th class="py-3 px-4 font-semibold">Status</th>
                            <th class="py-3 px-4 text-right font-semibold">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E8E5DF]">
                        @foreach($admins as $adm)
                            <tr class="hover:bg-[#FAF8F5] transition">
                                <td class="py-3.5 px-4">
                                    <span class="font-heading font-semibold text-[#121417] block">{{ $adm->name }}</span>
                                    <span class="text-[11px] font-mono text-[#6B7280]">{{ $adm->email }}</span>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-[11px] font-semibold text-[#121417]">
                                    {{ $adm->role }}
                                </td>
                                <td class="py-3.5 px-4 font-mono text-[#6B7280]">
                                    {{ $adm->last_login_at ? $adm->last_login_at->format('M d, H:i') : 'Never' }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="px-2 py-0.5 rounded text-[11px] font-mono font-semibold
                                        {{ $adm->is_active ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200' }}">
                                        {{ $adm->is_active ? 'ACTIVE' : 'REVOKED' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    @if($adm->id !== $currentAdmin->id)
                                    <form method="POST" action="{{ route('admin.settings.toggleAdmin', $adm->id) }}" class="inline">
                                        @csrf
                                        <button type="submit"
                                            class="text-[11px] font-heading font-medium px-2.5 py-1 rounded border border-[#DDD9D0] bg-[#FAF8F5] hover:bg-[#121417] hover:text-white transition cursor-pointer">
                                            {{ $adm->is_active ? 'Revoke' : 'Activate' }}
                                        </button>
                                    </form>
                                    @else
                                    <span class="text-[11px] font-mono text-[#8A8F99]">Self</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add Operator Form (5 cols) -->
        <div class="lg:col-span-5 bg-white border border-[#E8E5DF] rounded-xl p-6 shadow-xs">
            <h2 class="text-base font-heading font-semibold text-[#121417] mb-1">
                Authorize New Administrator
            </h2>
            <p class="text-xs text-[#52565E] mb-5">
                New operators authenticate exclusively using single-use verification emails.
            </p>

            <form method="POST" action="{{ route('admin.settings.storeAdmin') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                        Administrator Name
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="Alex Vance" required
                        class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3 py-2 text-xs text-[#121417] focus:outline-none focus:border-[#121417]" />
                </div>

                <div>
                    <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                        Corporate / Institutional Email
                    </label>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="alex@zihubridge.com" required
                        class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3 py-2 text-xs text-[#121417] focus:outline-none focus:border-[#121417]" />
                </div>

                <div>
                    <label class="block text-xs font-heading font-semibold uppercase tracking-wider text-[#52565E] mb-1.5">
                        Access Role
                    </label>
                    <select name="role" required
                        class="w-full bg-[#FAF8F5] border border-[#DDD9D0] rounded-lg px-3 py-2 text-xs font-heading font-medium text-[#121417] focus:outline-none focus:border-[#121417]">
                        <option value="SUPER_ADMIN">SUPER_ADMIN (Full Platform Control)</option>
                        <option value="FINANCE_ADMIN">FINANCE_ADMIN (Platform Fees & Revenue)</option>
                        <option value="LISTING_MANAGER">LISTING_MANAGER (Token Listing Approvals)</option>
                    </select>
                </div>

                <div class="pt-2">
                    <button type="submit"
                        class="w-full bg-[#121417] hover:bg-black text-white font-heading font-semibold py-2.5 rounded-lg text-xs transition cursor-pointer">
                        Grant Operator Access
                    </button>
                </div>
            </form>
        </div>

    </div>

    <!-- Security Audit Logs Table -->
    <div class="bg-white border border-[#E8E5DF] rounded-xl overflow-hidden shadow-xs">
        <div class="p-5 border-b border-[#E8E5DF]">
            <h2 class="text-base font-heading font-semibold text-[#121417]">
                Security Action Audit Trail
            </h2>
            <p class="text-xs text-[#52565E]">Immutable record of all sensitive operator actions across the platform</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F7F5F0] text-[#52565E] font-heading font-medium uppercase text-[11px] tracking-wider border-b border-[#E8E5DF]">
                    <tr>
                        <th class="py-3 px-4 font-semibold">Timestamp</th>
                        <th class="py-3 px-4 font-semibold">Action</th>
                        <th class="py-3 px-4 font-semibold">Operator</th>
                        <th class="py-3 px-4 font-semibold">Target Resource</th>
                        <th class="py-3 px-4 font-semibold">IP Address</th>
                        <th class="py-3 px-4 text-right font-semibold">Payload Snapshot</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E8E5DF]">
                    @forelse($auditLogs as $log)
                        <tr class="hover:bg-[#FAF8F5] transition">
                            <td class="py-3.5 px-4 font-mono text-[#6B7280]">
                                {{ $log->created_at->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="py-3.5 px-4 font-mono font-semibold text-[#121417]">
                                <span class="px-2 py-0.5 rounded text-[11px] bg-[#FAF8F5] border border-[#DDD9D0]">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-sans text-[#121417]">
                                {{ $log->adminUser->name ?? 'System' }}
                            </td>
                            <td class="py-3.5 px-4 font-mono text-[#52565E]">
                                {{ $log->resource_type }} #{{ $log->resource_id }}
                            </td>
                            <td class="py-3.5 px-4 font-mono text-[#6B7280]">
                                {{ $log->ip_address ?: '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono text-[11px] text-[#8A8F99] max-w-xs truncate" title="{{ json_encode($log->new_values_json) }}">
                                {{ $log->new_values_json ? json_encode($log->new_values_json) : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center text-[#8A8F99]">No audit actions recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($auditLogs->hasPages())
            <div class="p-4 border-t border-[#E8E5DF] bg-[#FAF8F5]">
                {{ $auditLogs->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
