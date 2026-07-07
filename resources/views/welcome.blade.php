<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="NovaCRM — Multi-tenant CRM to manage leads, sales pipeline, customers, and invoices.">
    <title>NovaCRM — Customer Relationship Management</title>
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
                        <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center font-bold text-white shadow-lg shadow-indigo-500/30 group-hover:shadow-indigo-500/50 transition-shadow">N</div>
                        <span class="font-bold text-xl tracking-tight">NovaCRM</span>
                    </a>

                    <nav class="hidden md:flex items-center gap-1">
                        <a href="#features" class="px-4 py-2 text-sm font-medium text-slate-300 hover:text-white rounded-lg hover:bg-white/5 transition">Features</a>
                        <a href="#workflow" class="px-4 py-2 text-sm font-medium text-slate-300 hover:text-white rounded-lg hover:bg-white/5 transition">Workflow</a>
                        <a href="#modules" class="px-4 py-2 text-sm font-medium text-slate-300 hover:text-white rounded-lg hover:bg-white/5 transition">Modules</a>
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
                    <a href="#features" @click="mobileMenu = false" class="block px-4 py-3 text-sm font-medium text-slate-300 hover:text-white rounded-lg hover:bg-white/5">Features</a>
                    <a href="#workflow" @click="mobileMenu = false" class="block px-4 py-3 text-sm font-medium text-slate-300 hover:text-white rounded-lg hover:bg-white/5">Workflow</a>
                    <a href="#modules" @click="mobileMenu = false" class="block px-4 py-3 text-sm font-medium text-slate-300 hover:text-white rounded-lg hover:bg-white/5">Modules</a>
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
                        Multi-tenant CRM for growing teams
                    </div>

                    <h1 class="mt-8 text-4xl sm:text-5xl lg:text-[3.5rem] font-extrabold tracking-tight leading-[1.1]">
                        The CRM that grows<br>
                        <span class="text-gradient">with your business</span>
                    </h1>

                    <p class="mt-6 text-lg sm:text-xl text-slate-400 leading-relaxed max-w-2xl mx-auto">
                        Track leads, manage pipelines, send quotations, and invoice customers — all in one secure workspace built for modern sales teams.
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

                {{-- Product preview --}}
                <div class="mt-16 sm:mt-20 relative max-w-5xl mx-auto">
                    <div class="absolute -inset-4 bg-gradient-to-r from-indigo-500/20 via-violet-500/20 to-cyan-500/20 rounded-3xl blur-2xl opacity-60"></div>
                    <div class="relative rounded-2xl border border-white/10 bg-slate-900/80 backdrop-blur-xl shadow-2xl shadow-black/50 overflow-hidden">
                        <div class="flex min-h-[420px] sm:min-h-[480px]">
                            {{-- Mini sidebar --}}
                            <div class="hidden sm:flex flex-col w-52 bg-slate-950 border-r border-white/5 shrink-0">
                                <div class="p-4 border-b border-white/5">
                                    <div class="flex items-center gap-2">
                                        <div class="h-8 w-8 rounded-lg bg-indigo-600 flex items-center justify-center text-xs font-bold">N</div>
                                        <div>
                                            <p class="text-xs font-semibold text-white">NovaCRM</p>
                                            <p class="text-[10px] text-slate-500">Business Suite</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-3 flex-1 space-y-1">
                                    <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-indigo-600/20 text-indigo-300 text-xs font-medium">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                        Dashboard
                                    </div>
                                    @foreach (['Leads', 'Customers', 'Pipeline', 'Invoices'] as $nav)
                                        <div class="flex items-center gap-2 px-3 py-2 rounded-lg text-slate-500 text-xs">
                                            <div class="h-4 w-4 rounded bg-slate-800"></div>
                                            {{ $nav }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Main preview --}}
                            <div class="flex-1 p-5 sm:p-8 bg-slate-50 min-w-0">
                                <div class="rounded-xl bg-gradient-to-br from-indigo-600 via-indigo-700 to-violet-800 p-5 sm:p-6 text-white">
                                    <p class="text-indigo-200 text-xs font-medium">Welcome back, Sarah</p>
                                    <p class="text-xl sm:text-2xl font-bold mt-1">Acme Corporation</p>
                                    <p class="text-indigo-200/80 text-sm mt-2 max-w-md">Your CRM workspace — track sales, manage customers, and grow revenue.</p>
                                </div>

                                <div class="mt-4 grid grid-cols-2 lg:grid-cols-4 gap-3">
                                    @foreach ([
                                        ['Leads', '128', 'bg-indigo-500'],
                                        ['Customers', '64', 'bg-emerald-500'],
                                        ['Quotes', '23', 'bg-amber-500'],
                                        ['Revenue', '$48k', 'bg-violet-500'],
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

                                <div class="mt-4 grid sm:grid-cols-5 gap-2">
                                    @foreach (['New', 'Contacted', 'Qualified', 'Proposal', 'Won'] as $i => $stage)
                                        <div class="rounded-lg bg-white border border-slate-200/80 p-3 text-center shadow-sm">
                                            <p class="text-[10px] text-slate-400 truncate">{{ $stage }}</p>
                                            <p class="text-lg font-bold text-slate-800 mt-0.5">{{ [24, 18, 12, 8, 5][$i] }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Stats --}}
                <div class="mt-16 grid grid-cols-2 md:grid-cols-4 gap-6 max-w-3xl mx-auto">
                    @foreach ([['25+', 'CRM Modules'], ['100%', 'Data Isolated'], ['Multi', 'Org Support'], ['Secure', 'RBAC Ready']] as [$stat, $label])
                        <div class="text-center">
                            <p class="text-2xl sm:text-3xl font-bold text-white">{{ $stat }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $label }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    </div>

    {{-- ═══════════════ FEATURES ═══════════════ --}}
    <section id="features" class="py-24 sm:py-32 bg-slate-50">
        <div class="max-w-6xl mx-auto px-5 sm:px-8">
            <div class="max-w-2xl">
                <p class="text-sm font-semibold uppercase tracking-wider text-indigo-600">Features</p>
                <h2 class="mt-3 text-3xl sm:text-4xl font-bold text-slate-900 tracking-tight">Built for the full sales cycle</h2>
                <p class="mt-4 text-lg text-slate-600 leading-relaxed">From first touch to final invoice — every step of your customer journey in one platform.</p>
            </div>

            <div class="mt-16 grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach ([
                    ['title' => 'Lead Management', 'desc' => 'Capture leads from any source, score them, assign to reps, and track every interaction.', 'icon' => 'bg-indigo-50 text-indigo-600'],
                    ['title' => 'Sales Pipeline', 'desc' => 'Visual pipeline with stages, probabilities, and revenue forecasting at a glance.', 'icon' => 'bg-violet-50 text-violet-600'],
                    ['title' => 'Customer Profiles', 'desc' => 'Complete customer records with contacts, addresses, documents, and history.', 'icon' => 'bg-cyan-50 text-cyan-600'],
                    ['title' => 'Quotations & Invoices', 'desc' => 'Professional quotes with PDF export, tax calculation, and payment tracking.', 'icon' => 'bg-emerald-50 text-emerald-600'],
                    ['title' => 'Multi-Tenancy', 'desc' => 'Each organization gets an isolated workspace — your data never mixes with others.', 'icon' => 'bg-amber-50 text-amber-600'],
                    ['title' => 'Reports & Analytics', 'desc' => 'Revenue trends, conversion rates, and team performance dashboards.', 'icon' => 'bg-rose-50 text-rose-600'],
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
                <h2 class="mt-3 text-3xl sm:text-4xl font-bold text-slate-900 tracking-tight">From lead to loyal customer</h2>
                <p class="mt-4 text-lg text-slate-600">A clear path through your sales process, built into the platform.</p>
            </div>

            <div class="mt-16 relative">
                <div class="hidden lg:block absolute top-12 left-[10%] right-[10%] h-0.5 bg-gradient-to-r from-indigo-200 via-violet-200 to-cyan-200"></div>
                <div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-8 lg:gap-4">
                    @foreach ([
                        ['step' => '01', 'title' => 'Capture', 'desc' => 'Import or create leads from website, ads, referrals, or API.'],
                        ['step' => '02', 'title' => 'Qualify', 'desc' => 'Score and assign leads to the right sales executive.'],
                        ['step' => '03', 'title' => 'Propose', 'desc' => 'Send branded quotations with products, taxes, and terms.'],
                        ['step' => '04', 'title' => 'Close', 'desc' => 'Convert won deals to invoices and record payments.'],
                        ['step' => '05', 'title' => 'Retain', 'desc' => 'Manage ongoing relationships and repeat business.'],
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

    {{-- ═══════════════ MODULES ═══════════════ --}}
    <section id="modules" class="py-24 sm:py-32 bg-slate-950 text-white">
        <div class="max-w-6xl mx-auto px-5 sm:px-8">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-20 items-center">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wider text-indigo-400">Platform</p>
                    <h2 class="mt-3 text-3xl sm:text-4xl font-bold tracking-tight">Modular architecture,<br>infinite possibilities</h2>
                    <p class="mt-4 text-lg text-slate-400 leading-relaxed">NovaCRM is built as a modular platform. Start with core CRM features and unlock more as your business scales.</p>
                    <ul class="mt-8 space-y-3">
                        @foreach (['Organization & team management', 'Role-based permissions', 'Audit logs & activity tracking', 'API-ready for integrations'] as $point)
                            <li class="flex items-center gap-3 text-slate-300">
                                <svg class="h-5 w-5 text-emerald-400 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                {{ $point }}
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    @foreach ([
                        ['Leads', true], ['Customers', true], ['Pipeline', true],
                        ['Products', true], ['Quotations', true], ['Invoices', true],
                        ['Tasks', true], ['Reports', true], ['Payments', true],
                        ['HR', false], ['Support', false], ['Automation', false],
                    ] as [$name, $active])
                        <div class="bento-card {{ $active ? 'ring-1 ring-indigo-500/30 bg-indigo-500/10' : '' }}">
                            <p class="text-sm font-medium {{ $active ? 'text-white' : 'text-slate-400' }}">{{ $name }}</p>
                            @if ($active)
                                <span class="mt-2 inline-block text-[10px] uppercase tracking-wider font-semibold text-emerald-400">Live</span>
                            @else
                                <span class="mt-2 inline-block text-[10px] uppercase tracking-wider font-semibold text-slate-600">Soon</span>
                            @endif
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
                    <h2 class="text-3xl sm:text-4xl font-bold text-white tracking-tight">Ready to transform your sales?</h2>
                    <p class="mt-4 text-lg text-slate-400 max-w-xl mx-auto">Create your organization in under a minute. No credit card required.</p>
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
                        <div class="h-9 w-9 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center font-bold text-white text-sm">N</div>
                        <span class="font-bold text-lg text-slate-900">NovaCRM</span>
                    </div>
                    <p class="mt-3 text-sm text-slate-500 max-w-xs">Cloud CRM for teams that want to sell smarter, not harder.</p>
                </div>
                <div class="flex gap-12 text-sm">
                    <div>
                        <p class="font-semibold text-slate-900">Product</p>
                        <ul class="mt-3 space-y-2 text-slate-500">
                            <li><a href="#features" class="hover:text-indigo-600 transition">Features</a></li>
                            <li><a href="#modules" class="hover:text-indigo-600 transition">Modules</a></li>
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
                <p>&copy; {{ date('Y') }} NovaCRM. All rights reserved.</p>
                <p>Built with Laravel & Tailwind CSS</p>
            </div>
        </div>
    </footer>
</body>
</html>
