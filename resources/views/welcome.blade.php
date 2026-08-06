<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ config('branding.product_name') }} — Multi-tenant enterprise suite unifying CRM, Projects, HRMS, Marketing, and Analytics in one workspace.">
    <meta name="application-name" content="{{ config('branding.product_name') }}">
    <meta name="apple-mobile-web-app-title" content="{{ config('branding.product_name') }}">
    <title>{{ config('branding.product_name') }} — CRM, Projects, HR &amp; Analytics</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-slate-900 bg-white" x-data="{ mobileMenu: false }">

    {{-- ═══════════════ HERO (dark) ═══════════════ --}}
    <div class="relative bg-slate-950 text-white overflow-hidden">
        <div class="absolute inset-0 landing-grid pointer-events-none"></div>
        <div class="absolute inset-0 landing-glow pointer-events-none"></div>
        <div class="absolute inset-0 landing-glow-secondary pointer-events-none"></div>

        {{-- Nav --}}
        <header class="relative z-50">
            <div class="max-w-6xl mx-auto px-5 sm:px-8">
                <div class="flex items-center justify-between h-[72px]">
                    <a href="{{ route('home') }}" class="flex items-center gap-3 group">
                        <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center font-bold text-white shadow-lg shadow-indigo-500/30 group-hover:shadow-indigo-500/50 transition-shadow">{{ strtoupper(substr(config('branding.product_short_name'), 0, 1)) }}</div>
                        <span class="font-bold text-xl tracking-tight">{{ config('branding.product_name') }}</span>
                    </a>

                    <nav class="hidden md:flex items-center gap-1">
                        <a href="#workspaces" class="px-4 py-2 text-sm font-medium text-slate-300 hover:text-white rounded-lg hover:bg-white/5 transition">Workspaces</a>
                        <a href="#workflow" class="px-4 py-2 text-sm font-medium text-slate-300 hover:text-white rounded-lg hover:bg-white/5 transition">How it works</a>
                        <a href="#platform" class="px-4 py-2 text-sm font-medium text-slate-300 hover:text-white rounded-lg hover:bg-white/5 transition">Platform</a>
                    </nav>

                    <div class="hidden md:flex items-center gap-3">
                        @auth
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-5 py-2.5 bg-white text-slate-900 text-sm font-semibold rounded-xl hover:bg-slate-100 transition shadow-lg shadow-black/20">
                                Open Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="px-4 py-2.5 text-sm font-medium text-slate-300 hover:text-white transition">Sign in</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="inline-flex items-center px-5 py-2.5 bg-white text-slate-900 text-sm font-semibold rounded-xl hover:bg-slate-100 transition shadow-lg shadow-black/20">
                                    Get started free
                                </a>
                            @endif
                        @endauth
                    </div>

                    <button @click="mobileMenu = !mobileMenu" class="md:hidden p-2 text-slate-300 hover:text-white rounded-lg hover:bg-white/5">
                        <svg x-show="!mobileMenu" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        <svg x-show="mobileMenu" x-cloak class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Mobile menu --}}
                <div x-show="mobileMenu" x-cloak x-transition class="md:hidden pb-6 border-t border-white/10 pt-4 space-y-1">
                    <a href="#workspaces" @click="mobileMenu = false" class="block px-4 py-3 text-sm font-medium text-slate-300 hover:text-white rounded-lg hover:bg-white/5">Workspaces</a>
                    <a href="#workflow" @click="mobileMenu = false" class="block px-4 py-3 text-sm font-medium text-slate-300 hover:text-white rounded-lg hover:bg-white/5">How it works</a>
                    <a href="#platform" @click="mobileMenu = false" class="block px-4 py-3 text-sm font-medium text-slate-300 hover:text-white rounded-lg hover:bg-white/5">Platform</a>
                    <div class="pt-4 flex flex-col gap-2 px-4">
                        @auth
                            <a href="{{ route('dashboard') }}" class="text-center px-5 py-3 bg-white text-slate-900 text-sm font-semibold rounded-xl">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="text-center px-5 py-3 border border-white/20 text-sm font-medium rounded-xl">Sign in</a>
                            <a href="{{ route('register') }}" class="text-center px-5 py-3 bg-white text-slate-900 text-sm font-semibold rounded-xl">Get started free</a>
                        @endauth
                    </div>
                </div>
            </div>
        </header>

        {{-- Hero content --}}
        <section class="relative z-10 pt-12 pb-20 sm:pt-16 sm:pb-28 lg:pt-20 lg:pb-32">
            <div class="max-w-6xl mx-auto px-5 sm:px-8">
                <div class="max-w-3xl mx-auto text-center">
                    <div class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-1.5 text-sm text-slate-300 backdrop-blur-sm">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        Enterprise suite · CRM · Projects · HR · Marketing · Analytics
                    </div>

                    <h1 class="mt-8 text-4xl sm:text-5xl lg:text-[3.5rem] font-extrabold tracking-tight leading-[1.1]">
                        One platform for<br>
                        <span class="text-gradient">sales, delivery &amp; people</span>
                    </h1>

                    <p class="mt-6 text-lg sm:text-xl text-slate-400 leading-relaxed max-w-2xl mx-auto">
                        Replace disconnected tools with a multi-tenant workspace: quote-to-cash, project delivery, HR &amp; recruitment, marketing attribution, and executive analytics — shared org data and RBAC throughout.
                    </p>

                    <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                        @guest
                            <a href="{{ route('register') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-4 bg-gradient-to-r from-indigo-500 to-violet-600 text-white font-semibold rounded-xl hover:from-indigo-400 hover:to-violet-500 transition shadow-xl shadow-indigo-500/25">
                                Start for free
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                            </a>
                            <a href="{{ route('login') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-4 text-slate-300 font-semibold rounded-xl border border-white/15 hover:bg-white/5 transition">
                                Sign in to your account
                            </a>
                        @else
                            <a href="{{ route('dashboard') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-4 bg-gradient-to-r from-indigo-500 to-violet-600 text-white font-semibold rounded-xl hover:from-indigo-400 hover:to-violet-500 transition shadow-xl shadow-indigo-500/25">
                                Go to Dashboard
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                            </a>
                        @endguest
                    </div>
                </div>

                {{-- Product preview — enterprise workspace home --}}
                <div class="mt-16 sm:mt-20 relative max-w-5xl mx-auto">
                    <div class="absolute -inset-4 bg-gradient-to-r from-indigo-500/20 via-violet-500/20 to-cyan-500/20 rounded-3xl blur-2xl opacity-60"></div>
                    <div class="relative rounded-2xl border border-white/10 bg-slate-900/80 backdrop-blur-xl shadow-2xl shadow-black/50 overflow-hidden">
                        <div class="flex min-h-[420px] sm:min-h-[480px]">
                            {{-- Mini sidebar --}}
                            <div class="hidden sm:flex flex-col w-52 bg-slate-950 border-r border-white/5 shrink-0">
                                <div class="p-4 border-b border-white/5">
                                    <div class="flex items-center gap-2">
                                        <div class="h-8 w-8 rounded-lg bg-indigo-600 flex items-center justify-center text-xs font-bold">{{ strtoupper(substr(config('branding.product_short_name'), 0, 1)) }}</div>
                                        <div>
                                            <p class="text-xs font-semibold text-white">{{ config('branding.product_name') }}</p>
                                            <p class="text-[10px] text-slate-500">Enterprise Suite</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-3 flex-1 space-y-1">
                                    <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-indigo-600/20 text-indigo-300 text-xs font-medium">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                        Home
                                    </div>
                                    @foreach (['CRM', 'Projects', 'HR', 'Marketing', 'Analytics'] as $nav)
                                        <div class="flex items-center gap-2 px-3 py-2 rounded-lg text-slate-500 text-xs">
                                            <div class="h-4 w-4 rounded bg-slate-800"></div>
                                            {{ $nav }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Main preview --}}
                            <div class="flex-1 p-5 sm:p-8 bg-slate-50 min-w-0">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="text-[11px] font-medium uppercase tracking-wider text-slate-400">Home</p>
                                        <p class="text-xl sm:text-2xl font-bold text-slate-900 mt-0.5">Acme Corporation</p>
                                        <p class="text-sm text-slate-500 mt-1">Your workspace overview</p>
                                    </div>
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach (['CRM', 'Projects', 'HR', 'Marketing'] as $chip)
                                            <span class="inline-flex items-center rounded-lg bg-white border border-slate-200 px-2.5 py-1 text-[10px] font-semibold text-slate-600 shadow-sm">{{ $chip }}</span>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="mt-4 grid grid-cols-2 lg:grid-cols-4 gap-3">
                                    @foreach ([
                                        ['Leads', '128', 'bg-indigo-500'],
                                        ['Open tasks', '34', 'bg-cyan-500'],
                                        ['Headcount', '86', 'bg-emerald-500'],
                                        ['Campaigns', '12', 'bg-violet-500'],
                                    ] as [$label, $val, $barClass])
                                        <div class="rounded-xl bg-white border border-slate-200/80 p-4 shadow-sm">
                                            <p class="text-[11px] font-medium uppercase tracking-wider text-slate-400">{{ $label }}</p>
                                            <p class="mt-1 text-xl font-bold text-slate-900">{{ $val }}</p>
                                            <div class="mt-2 h-1 rounded-full bg-slate-100 overflow-hidden">
                                                <div class="h-full rounded-full {{ $barClass }} w-2/3"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="mt-4 grid sm:grid-cols-3 gap-3">
                                    @foreach ([
                                        ['Pipeline', 'Quote → cash', '5 stages'],
                                        ['Delivery', 'Active projects', '9 live'],
                                        ['People', 'Leave & attendance', 'On track'],
                                    ] as [$title, $sub, $meta])
                                        <div class="rounded-xl bg-white border border-slate-200/80 p-4 shadow-sm">
                                            <p class="text-sm font-semibold text-slate-900">{{ $title }}</p>
                                            <p class="mt-1 text-xs text-slate-500">{{ $sub }}</p>
                                            <p class="mt-3 text-[11px] font-medium text-indigo-600">{{ $meta }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Stats --}}
                <div class="mt-16 grid grid-cols-2 md:grid-cols-4 gap-6 max-w-3xl mx-auto">
                    @foreach ([['6', 'Workspaces'], ['100%', 'Tenant Isolated'], ['RBAC', 'Permissions'], ['Live', 'Modules Ready']] as [$stat, $label])
                        <div class="text-center">
                            <p class="text-2xl sm:text-3xl font-bold text-white">{{ $stat }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $label }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    </div>

    {{-- ═══════════════ WORKSPACES ═══════════════ --}}
    <section id="workspaces" class="py-24 sm:py-32 bg-slate-50">
        <div class="max-w-6xl mx-auto px-5 sm:px-8">
            <div class="max-w-2xl">
                <p class="text-sm font-semibold uppercase tracking-wider text-indigo-600">Workspaces</p>
                <h2 class="mt-3 text-3xl sm:text-4xl font-bold text-slate-900 tracking-tight">Everything your mid-market team runs day to day</h2>
                <p class="mt-4 text-lg text-slate-600 leading-relaxed">Six connected workspaces on shared organization data — so sales, delivery, people, and growth stay aligned.</p>
            </div>

            <div class="mt-16 grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach ([
                    ['title' => 'CRM', 'desc' => 'Leads, pipeline, customers, quotations, invoices, and payments — full quote-to-cash.', 'icon' => 'bg-indigo-50 text-indigo-600'],
                    ['title' => 'Projects', 'desc' => 'Portfolios, programs, tasks, resources, risks, and delivery reporting in one EPM workspace.', 'icon' => 'bg-cyan-50 text-cyan-600'],
                    ['title' => 'HRMS', 'desc' => 'Employees, attendance, leave, payroll hooks, performance, and recruitment with careers portal.', 'icon' => 'bg-emerald-50 text-emerald-600'],
                    ['title' => 'Marketing', 'desc' => 'Campaigns, provider connections, tracking, and attribution back to revenue outcomes.', 'icon' => 'bg-violet-50 text-violet-600'],
                    ['title' => 'Analytics', 'desc' => 'Cross-module dashboards and KPIs for leaders — one surface across the suite.', 'icon' => 'bg-amber-50 text-amber-600'],
                    ['title' => 'Administration', 'desc' => 'Org settings, team, modules, security, API tokens, and audit — built for admins.', 'icon' => 'bg-rose-50 text-rose-600'],
                ] as $f)
                    <div class="feature-card group">
                        <div class="h-11 w-11 rounded-xl {{ $f['icon'] }} flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <h3 class="mt-5 text-lg font-semibold text-slate-900">{{ $f['title'] }}</h3>
                        <p class="mt-2 text-sm text-slate-600 leading-relaxed">{{ $f['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═══════════════ WORKFLOW ═══════════════ --}}
    <section id="workflow" class="py-24 sm:py-32 bg-white">
        <div class="max-w-6xl mx-auto px-5 sm:px-8">
            <div class="text-center max-w-2xl mx-auto">
                <p class="text-sm font-semibold uppercase tracking-wider text-indigo-600">How it works</p>
                <h2 class="mt-3 text-3xl sm:text-4xl font-bold text-slate-900 tracking-tight">From opportunity to outcome</h2>
                <p class="mt-4 text-lg text-slate-600">A connected operating rhythm across sales, delivery, people, and growth.</p>
            </div>

            <div class="mt-16 relative">
                <div class="hidden lg:block absolute top-12 left-[10%] right-[10%] h-0.5 bg-gradient-to-r from-indigo-200 via-violet-200 to-cyan-200"></div>
                <div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-8 lg:gap-4">
                    @foreach ([
                        ['step' => '01', 'title' => 'Win', 'desc' => 'Capture leads, run pipeline, quote, invoice, and collect payment.'],
                        ['step' => '02', 'title' => 'Deliver', 'desc' => 'Plan projects, assign work, track risks, and manage resources.'],
                        ['step' => '03', 'title' => 'Hire & run HR', 'desc' => 'Recruit, onboard, track attendance and leave, manage performance.'],
                        ['step' => '04', 'title' => 'Attribute', 'desc' => 'Connect campaigns and channels to pipeline and revenue.'],
                        ['step' => '05', 'title' => 'Decide', 'desc' => 'Use Analytics and Home for cross-module KPIs and next actions.'],
                    ] as $item)
                        <div class="relative text-center lg:text-left">
                            <div class="mx-auto lg:mx-0 h-10 w-10 rounded-full bg-indigo-600 text-white flex items-center justify-center text-sm font-bold shadow-lg shadow-indigo-500/30 relative z-10">
                                {{ $item['step'] }}
                            </div>
                            <h3 class="mt-5 text-base font-semibold text-slate-900">{{ $item['title'] }}</h3>
                            <p class="mt-2 text-sm text-slate-600 leading-relaxed">{{ $item['desc'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════ PLATFORM ═══════════════ --}}
    <section id="platform" class="py-24 sm:py-32 bg-slate-950 text-white">
        <div class="max-w-6xl mx-auto px-5 sm:px-8">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-20 items-center">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wider text-indigo-400">Platform</p>
                    <h2 class="mt-3 text-3xl sm:text-4xl font-bold tracking-tight">Modular enterprise architecture,<br>ready for production</h2>
                    <p class="mt-4 text-lg text-slate-400 leading-relaxed">{{ config('branding.product_name') }} ships as a multi-tenant SaaS with an enterprise AppShell, workspace homes, Knowledge Center, and a SaaS owner console at <span class="text-slate-300">/platform</span>.</p>
                    <ul class="mt-8 space-y-3">
                        @foreach ([
                            'Organization & team management with RBAC',
                            'Audit logs, security settings & API tokens',
                            'In-app Knowledge Center & documentation',
                            'Demo environment, pilots & launch playbooks',
                        ] as $point)
                            <li class="flex items-center gap-3 text-slate-300">
                                <svg class="h-5 w-5 text-emerald-400 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                {{ $point }}
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    @foreach ([
                        'CRM', 'Projects', 'HRMS',
                        'Recruitment', 'Marketing', 'Analytics',
                        'Administration', 'Platform', 'Knowledge',
                        'Tasks', 'Invoices', 'Careers',
                    ] as $name)
                        <div class="bento-card ring-1 ring-indigo-500/30 bg-indigo-500/10">
                            <p class="text-sm font-medium text-white">{{ $name }}</p>
                            <span class="mt-2 inline-block text-[10px] uppercase tracking-wider font-semibold text-emerald-400">Live</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════ CTA ═══════════════ --}}
    <section class="py-24 sm:py-32 bg-white">
        <div class="max-w-6xl mx-auto px-5 sm:px-8">
            <div class="relative rounded-3xl overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900"></div>
                <div class="absolute inset-0 landing-grid opacity-30"></div>
                <div class="absolute inset-0 landing-glow opacity-50"></div>

                <div class="relative px-8 py-16 sm:px-16 sm:py-20 text-center">
                    <h2 class="text-3xl sm:text-4xl font-bold text-white tracking-tight">Ready to consolidate your stack?</h2>
                    <p class="mt-4 text-lg text-slate-400 max-w-xl mx-auto">Create your organization in under a minute. One workspace for sales, delivery, people, and growth.</p>
                    <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                        @guest
                            <a href="{{ route('register') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-4 bg-white text-slate-900 font-semibold rounded-xl hover:bg-slate-100 transition shadow-xl">
                                Create free account
                            </a>
                            <a href="{{ route('login') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-4 text-white font-semibold rounded-xl border border-white/20 hover:bg-white/5 transition">
                                I already have an account
                            </a>
                        @else
                            <a href="{{ route('dashboard') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-4 bg-white text-slate-900 font-semibold rounded-xl hover:bg-slate-100 transition shadow-xl">
                                Open Dashboard
                            </a>
                        @endguest
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════ FOOTER ═══════════════ --}}
    <footer class="border-t border-slate-200 bg-slate-50">
        <div class="max-w-6xl mx-auto px-5 sm:px-8 py-12">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-8">
                <div>
                    <div class="flex items-center gap-3">
                        <div class="h-9 w-9 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center font-bold text-white text-sm">{{ strtoupper(substr(config('branding.product_short_name'), 0, 1)) }}</div>
                        <span class="font-bold text-lg text-slate-900">{{ config('branding.product_name') }}</span>
                    </div>
                    <p class="mt-3 text-sm text-slate-500 max-w-xs">Multi-tenant enterprise suite for CRM, Projects, HRMS, Marketing, and Analytics.</p>
                </div>
                <div class="flex gap-12 text-sm">
                    <div>
                        <p class="font-semibold text-slate-900">Product</p>
                        <ul class="mt-3 space-y-2 text-slate-500">
                            <li><a href="#workspaces" class="hover:text-indigo-600 transition">Workspaces</a></li>
                            <li><a href="#platform" class="hover:text-indigo-600 transition">Platform</a></li>
                        </ul>
                    </div>
                    <div>
                        <p class="font-semibold text-slate-900">Account</p>
                        <ul class="mt-3 space-y-2 text-slate-500">
                            <li><a href="{{ route('login') }}" class="hover:text-indigo-600 transition">Sign in</a></li>
                            <li><a href="{{ route('register') }}" class="hover:text-indigo-600 transition">Register</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="mt-10 pt-8 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-slate-500">
                <p>&copy; {{ date('Y') }} {{ config('branding.company_name') }}. All rights reserved.</p>
                <p>Built with Laravel & Tailwind CSS</p>
            </div>
        </div>
    </footer>
</body>
</html>
