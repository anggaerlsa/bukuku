@php
    $inWorlds = request()->routeIs('worlds.*') || request()->routeIs('characters.*') || request()->routeIs('locations.*');
@endphp
<nav x-data="{ open: false }" class="panel-shell rounded-none border-x-0 border-t-0 border-b-2 border-accent/40 shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center gap-6">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 shrink-0">
                    <x-application-logo class="h-9 w-auto" />
                    <span class="font-display text-2xl text-accent-light hidden sm:block leading-none">{{ config('app.name') }}</span>
                </a>

                <div class="hidden md:flex items-center gap-1">
                    <a href="{{ route('dashboard') }}" class="nav-top {{ request()->routeIs('dashboard') ? 'nav-top-active' : '' }}">Dasbor</a>

                    @hasanyrole('superadmin|admin|author')
                        <a href="{{ route('worlds.index') }}" class="nav-top {{ $inWorlds ? 'nav-top-active' : '' }}">Dunia</a>
                    @endhasanyrole

                    @can('manage genres')
                        <a href="{{ route('genres.index') }}" class="nav-top {{ request()->routeIs('genres.*') ? 'nav-top-active' : '' }}">Genre</a>
                    @endcan

                    @can('manage users')
                        <a href="{{ route('users.index') }}" class="nav-top {{ request()->routeIs('users.*') ? 'nav-top-active' : '' }}">Pengguna</a>
                    @endcan
                </div>
            </div>

            <!-- User menu (desktop) -->
            <div class="hidden md:flex items-center gap-3">
                <div class="relative" x-data="{ menu: false }" @click.outside="menu = false">
                    <button @click="menu = !menu" class="flex items-center gap-2 rounded-md border border-accent/30 bg-white/5 pl-1.5 pr-2.5 py-1.5 hover:border-accent/60 transition">
                        <span class="grid place-items-center h-7 w-7 rounded-full bg-gradient-to-b from-accent-light to-accent-dark text-white font-display font-bold text-sm">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </span>
                        <span class="text-left leading-tight">
                            <span class="block text-sm font-display text-white">{{ auth()->user()->name }}</span>
                            <span class="block text-[0.6rem] uppercase tracking-wider text-accent-light/80">{{ auth()->user()->primaryRoleLabel() }}</span>
                        </span>
                        <svg class="h-4 w-4 text-white/60" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                    </button>

                    <div x-show="menu" x-transition x-cloak class="absolute right-0 mt-2 w-60 panel-shell p-1.5 z-50">
                        <div class="px-3 py-2 border-b border-accent/20">
                            <p class="text-sm font-display text-white">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-white/50 truncate">{{ auth()->user()->email }}</p>
                            <span class="badge-accent mt-1.5">{{ auth()->user()->primaryRoleLabel() }}</span>
                        </div>
                        <a href="{{ route('profile.edit') }}" class="block px-3 py-2 rounded text-sm text-white/80 hover:bg-white/10 hover:text-accent-light transition">Profil &amp; Keamanan</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-3 py-2 rounded text-sm text-white/80 hover:bg-danger/30 hover:text-white transition">Keluar</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Hamburger -->
            <div class="flex items-center md:hidden">
                <button @click="open = !open" class="p-2 rounded-md text-white/70 hover:text-accent-light hover:bg-white/5 transition">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{ 'hidden': open, 'inline-flex': !open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        <path :class="{ 'hidden': !open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile menu -->
    <div x-show="open" x-cloak class="md:hidden border-t border-accent/20 px-4 py-3 space-y-1">
        <a href="{{ route('dashboard') }}" class="block nav-top {{ request()->routeIs('dashboard') ? 'nav-top-active' : '' }}">Dasbor</a>
        @hasanyrole('superadmin|admin|author')
            <a href="{{ route('worlds.index') }}" class="block nav-top {{ $inWorlds ? 'nav-top-active' : '' }}">Dunia</a>
        @endhasanyrole
        @can('manage genres')
            <a href="{{ route('genres.index') }}" class="block nav-top">Genre</a>
        @endcan
        @can('manage users')
            <a href="{{ route('users.index') }}" class="block nav-top">Pengguna</a>
        @endcan

        <div class="pt-3 mt-2 border-t border-accent/20">
            <p class="px-3 text-sm font-display text-white">{{ auth()->user()->name }}</p>
            <p class="px-3 text-xs text-white/50">{{ auth()->user()->email }} · {{ auth()->user()->primaryRoleLabel() }}</p>
            <a href="{{ route('profile.edit') }}" class="block nav-top mt-1">Profil &amp; Keamanan</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full text-left nav-top text-white/70 hover:text-white">Keluar</button>
            </form>
        </div>
    </div>
</nav>
