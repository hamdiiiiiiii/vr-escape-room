<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'BugHunt VR') }} - My Avatar</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|chakra-petch:500,600,700|jetbrains-mono:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .lobby { --teal: #3fe0c5; --amber: #ff8a3d; --line: #16323a; --line2: #2a4650; --ok: #4ade80; --txt: #c9d3dc; --strong: #e6edf3; --mut: #8b9bab; --dim: #6b7c8c; }
        .lobby button, .lobby select { font: inherit; color: inherit; }
        .lb-wrap { max-width: 1500px; margin: 0 auto; padding: 28px 16px 48px; }
        .lb-top { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 18px; }
        .lb-top h1 { margin: 4px 0 0; font-family: 'Chakra Petch', Inter, sans-serif; font-weight: 700; font-size: 34px; line-height: 1.05; text-transform: uppercase; letter-spacing: .02em; color: var(--strong); }
        .lb-top p.sub { margin: 6px 0 0; color: var(--mut); font-size: 15px; }
        .lb-stat { display: flex; gap: 12px; align-items: center; font-family: 'JetBrains Mono', monospace; font-size: 13px; letter-spacing: .06em; }
        .lb-stat span { display: inline-flex; align-items: center; gap: 8px; padding: 7px 12px; border: 1px solid var(--line2); border-radius: 10px; background: #0b1218cc; color: var(--txt); }
        .lb-stat .on { color: var(--ok); border-color: #4ade8055; }
        .lb-stat .on i { width: 9px; height: 9px; border-radius: 50%; background: var(--ok); box-shadow: 0 0 10px var(--ok); }
        .lb-stat .cnt { color: var(--teal); border-color: #3fe0c555; font-weight: 700; }

        /* the chamber: same vault-door card language as the welcome page */
        .chamber { position: relative; overflow: hidden; isolation: isolate; border: 2px solid var(--line2); border-radius: 18px; background: #0b1218; box-shadow: 0 0 0 8px #070c11, 0 0 90px #3fe0c52a, inset 0 0 60px #000000b3; }
        .chamber .hz { height: 14px; background: repeating-linear-gradient(135deg, #ff8a3d 0 16px, #120c06 16px 32px); position: relative; z-index: 3; }
        .chamber .hz.b { height: 10px; opacity: .65; }
        .bg { position: absolute; inset: 0; width: 100%; height: 100%; z-index: -2; }
        .vig { position: absolute; inset: 0; z-index: -1; background: radial-gradient(ellipse 75% 75% at 50% 45%, transparent 40%, #000000b0 100%); pointer-events: none; }
        .scan { position: absolute; inset: 0; z-index: 0; pointer-events: none; background: repeating-linear-gradient(0deg, #ffffff05 0 1px, transparent 1px 3px); }

        .main { position: relative; z-index: 1; display: grid; grid-template-columns: 330px minmax(0, 1fr) 390px; gap: 22px; padding: 24px 24px 22px; min-height: 700px; }
        .panel { position: relative; background: #0b1218e8; border: 1px solid var(--line); border-radius: 12px; box-shadow: inset 0 0 40px #00000066; backdrop-filter: blur(3px); }
        .panel::before { content: ''; position: absolute; left: 14px; top: -1px; width: 90px; height: 2px; background: var(--teal); box-shadow: 0 0 10px var(--teal); }
        .panel .ph { padding: 16px 18px 12px; border-bottom: 1px solid var(--line); }
        .eyebrow { font-family: 'JetBrains Mono', monospace; font-size: 11px; letter-spacing: .16em; text-transform: uppercase; color: var(--teal); }
        .panel h2 { margin: 3px 0 0; font-family: 'Chakra Petch', Inter, sans-serif; font-weight: 700; font-size: 20px; text-transform: uppercase; letter-spacing: .03em; color: var(--strong); }

        .left { display: flex; flex-direction: column; gap: 16px; align-self: start; }
        .row { display: flex; align-items: center; gap: 12px; padding: 10px 16px; min-height: 62px; border-bottom: 1px solid #16323a99; }
        .row.me { background: linear-gradient(90deg, #3fe0c51c, transparent); box-shadow: inset 3px 0 0 var(--teal); }
        .pic { position: relative; width: 44px; height: 44px; flex: none; }
        .face { width: 44px; height: 44px; border-radius: 50%; overflow: hidden; border: 2px solid var(--line2); background: #0b1218; }
        .face svg { width: 100%; height: 100%; display: block; }
        .pic::after { content: ''; position: absolute; right: -1px; bottom: 0; width: 11px; height: 11px; border-radius: 50%; background: var(--ok); border: 2px solid #0b1218; }
        .nm { font-weight: 600; font-size: 16px; color: var(--strong); display: flex; align-items: center; gap: 8px; min-width: 0; word-break: break-all; }
        .crown { color: var(--amber); flex: none; }
        .tag { font-family: 'JetBrains Mono', monospace; font-size: 10px; letter-spacing: .12em; padding: 2px 6px; border-radius: 5px; border: 1px solid #3fe0c544; color: var(--teal); }
        .x { margin-left: auto; width: 30px; height: 30px; border-radius: 8px; border: 1px solid var(--line2); background: transparent; color: var(--mut); cursor: pointer; font-size: 18px; line-height: 1; }
        .x:hover { color: #ff5d5d; border-color: #ff5d5d; }
        .empty summary, .empty .slot { list-style: none; cursor: pointer; display: flex; align-items: center; gap: 12px; padding: 10px 16px; min-height: 62px; border-bottom: 1px solid #16323a99; color: var(--mut); font-weight: 600; }
        .empty summary::-webkit-details-marker { display: none; }
        .empty summary:hover { background: #3fe0c50d; color: var(--strong); }
        .empty .slot { cursor: default; }
        .plus { width: 44px; height: 44px; flex: none; border-radius: 50%; border: 2px dashed var(--line2); display: grid; place-items: center; font-size: 24px; color: var(--teal); line-height: 1; }
        .cands { padding: 6px 10px 10px; display: flex; flex-direction: column; gap: 6px; max-height: 190px; overflow-y: auto; }
        .cand { width: 100%; display: flex; align-items: center; gap: 10px; padding: 6px 8px; background: #0e1a22; border: 1px solid var(--line); border-radius: 8px; color: var(--strong); cursor: pointer; text-align: left; font-weight: 600; }
        .cand:hover { border-color: var(--teal); }
        .cand .face { width: 34px; height: 34px; }
        .note { padding: 10px 16px 6px; color: var(--dim); font-size: 13px; line-height: 1.4; }
        .leave { background: none; border: 0; color: var(--dim); text-decoration: underline; cursor: pointer; font-size: 13px; padding: 2px 16px 14px; }
        .leave:hover { color: #ff5d5d; }
        .info { display: flex; gap: 14px; align-items: center; padding: 16px 18px; font-size: 14.5px; line-height: 1.45; color: var(--txt); }
        .info svg { flex: none; color: var(--teal); }

        .stage { position: relative; min-height: 600px; }
        .fig { position: absolute; left: 50%; bottom: 9%; height: 80%; transform: translateX(-50%); filter: drop-shadow(0 18px 16px #000000b0); z-index: 2; }
        .fig svg { height: 100%; width: auto; display: block; overflow: visible; }
        .dais { position: absolute; left: 50%; bottom: 0; width: min(760px, 112%); transform: translateX(-50%); z-index: 1; }
        .dais svg { width: 100%; display: block; }

        .right { align-self: start; padding-bottom: 14px; }
        .tabs { display: flex; gap: 6px; padding: 12px 14px 8px; }
        .tabs button { flex: 1; background: transparent; border: 1px solid var(--line); border-radius: 8px; padding: 9px 2px; font-family: 'JetBrains Mono', monospace; font-weight: 600; font-size: 10.5px; letter-spacing: .06em; text-transform: uppercase; color: var(--mut); cursor: pointer; }
        .tabs button:hover { color: var(--strong); border-color: var(--line2); }
        .tabs button.on { color: var(--teal); border-color: var(--teal); background: #3fe0c51a; box-shadow: 0 0 14px #3fe0c533; }
        .box { height: 300px; overflow-y: auto; margin: 0 6px 0 14px; padding-right: 8px; scrollbar-width: thin; scrollbar-color: #2a4650 transparent; }
        .pane { display: none; } .pane.on { display: block; }
        .av-opts { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; padding: 3px; }
        .av-opt { position: relative; aspect-ratio: 1 / 1; padding: 0; overflow: hidden; background: #0e1a22; border: 1px solid var(--line); border-radius: 10px; cursor: pointer; display: block; }
        .av-opt svg { width: 100%; height: 100%; display: block; }
        .av-opt .lb { position: absolute; left: 0; right: 0; bottom: 0; padding: 3px 3px; text-align: center; font-size: 11px; font-weight: 600; background: #05080ccc; color: var(--txt); line-height: 1.15; }
        .av-opt:hover { border-color: #3fe0c588; }
        .av-opt.on { border: 2px solid var(--teal); box-shadow: 0 0 16px #3fe0c566; }
        .sub2 { margin: 12px 0 8px; font-family: 'JetBrains Mono', monospace; font-weight: 600; font-size: 11px; letter-spacing: .16em; text-transform: uppercase; color: var(--teal); }
        .sub2:first-child { margin-top: 4px; }
        .sw { display: flex; flex-wrap: wrap; gap: 10px; }
        .av-swb { width: 36px; height: 36px; border-radius: 8px; border: 2px solid #ffffff26; padding: 0; cursor: pointer; }
        .av-swb:hover { border-color: #ffffff88; }
        .av-swb.on { border-color: var(--teal); box-shadow: 0 0 0 2px #05080c inset, 0 0 14px #3fe0c588; }
        .lbl { margin: 14px 16px 6px; font-family: 'JetBrains Mono', monospace; font-size: 11px; letter-spacing: .16em; text-transform: uppercase; color: var(--teal); }
        .nrow { display: flex; gap: 8px; margin: 0 16px; }
        .nval { flex: 1; min-width: 0; background: #05080c; border: 1px solid var(--line); border-radius: 8px; padding: 9px 12px; font-size: 17px; font-weight: 600; color: var(--strong); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .nbtn { width: 42px; background: #05080c; border: 1px solid var(--line); border-radius: 8px; cursor: pointer; display: grid; place-items: center; color: var(--strong); }
        .nbtn:hover { border-color: var(--teal); color: var(--teal); }
        .edit { display: none; grid-template-columns: 1fr 1fr 70px; gap: 8px; margin: 8px 16px 0; }
        .edit.on { display: grid; }
        .edit select { min-width: 0; padding: 8px; border-radius: 8px; font-size: 14px; }
        .team { padding: 0 16px; }
        .lobby .join { display: flex; align-items: center; justify-content: center; gap: 12px; width: 100%; margin-top: 16px; padding: 16px 20px; border-radius: 12px; border: 0; cursor: pointer; background: var(--amber); color: #05080c; font-family: 'Chakra Petch', Inter, sans-serif; font-weight: 700; font-size: 22px; letter-spacing: .06em; text-transform: uppercase; box-shadow: 0 0 28px #ff8a3d66; transition: transform .12s, box-shadow .12s; }
        .lobby .join:hover { box-shadow: 0 0 40px #ff8a3d99; transform: translateY(-1px); }
        .back { display: inline-flex; align-items: center; gap: 10px; margin-top: 18px; color: var(--mut); font-weight: 600; text-decoration: none; }
        .back kbd { border: 1px solid var(--line2); border-radius: 6px; padding: 3px 8px; font-family: 'JetBrains Mono', monospace; font-size: 12px; }
        .back:hover { color: var(--strong); }

        .toast { position: fixed; top: 84px; left: 50%; transform: translateX(-50%); z-index: 60; padding: 10px 20px; border: 1px solid #4ade8077; background: #0b1a12f2; color: #a3f3c0; border-radius: 10px; font-size: 14px; }
        .toast.err { border-color: #ff5d5d77; background: #1c0c0cf2; color: #ffb0b0; }
        .lobby button:focus-visible, .lobby summary:focus-visible, .lobby a:focus-visible, .lobby select:focus-visible { outline: 2px solid var(--teal); outline-offset: 2px; }
        @media (prefers-reduced-motion: no-preference) { .pulse { animation: pu 3s ease-in-out infinite; } @keyframes pu { 50% { opacity: .5; } } }
        @media (max-width: 1180px) { .main { grid-template-columns: minmax(0, 1fr); } .stage { order: -1; min-height: 540px; } }
        @media (max-width: 560px) { .main { padding: 14px; } .main > * { min-width: 0; } .lb-top h1 { font-size: 26px; } .tabs button { font-size: 9.5px; padding: 9px 0; } .box { height: 330px; } .stage { min-height: 460px; } }
    </style>
</head>
<body class="font-sans antialiased bg-chamber-950 text-gray-700">
<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
    <defs>
        <radialGradient id="shine" cx=".36" cy=".3" r=".8">
            <stop offset="0" stop-color="#fff" stop-opacity=".3"/><stop offset=".5" stop-color="#fff" stop-opacity="0"/><stop offset="1" stop-color="#000" stop-opacity=".3"/>
        </radialGradient>
        <filter id="vol" x="-6%" y="-6%" width="112%" height="112%" color-interpolation-filters="sRGB">
            <feGaussianBlur in="SourceAlpha" stdDeviation="3.2" result="b"/>
            <feSpecularLighting in="b" surfaceScale="5" specularConstant=".85" specularExponent="24" lighting-color="#ffffff" result="spec"><feDistantLight azimuth="235" elevation="50"/></feSpecularLighting>
            <feComposite in="spec" in2="SourceAlpha" operator="in" result="specIn"/>
            <feGaussianBlur in="SourceAlpha" stdDeviation="6" result="b2"/>
            <feComposite in="SourceAlpha" in2="b2" operator="arithmetic" k2="1" k3="-1" result="edge"/>
            <feFlood flood-color="#000" flood-opacity=".5"/>
            <feComposite in2="edge" operator="in" result="dark"/>
            <feComposite in="SourceGraphic" in2="specIn" operator="arithmetic" k2="1" k3=".36" result="lit"/>
            <feMerge><feMergeNode in="lit"/><feMergeNode in="dark"/></feMerge>
        </filter>
    </defs>
</svg>
<div class="hud-floor" aria-hidden="true"></div>
<div class="relative z-10 min-h-screen flex flex-col lobby">
    @include('layouts.navigation')

    @if (session('status') === 'avatar-saved')
        <div class="toast" role="status">Avatar saved. This is how you will look in the game.</div>
    @elseif (session('status') === 'squad-added')
        <div class="toast" role="status">Player added to your squad.</div>
    @elseif (session('status') === 'squad-removed')
        <div class="toast" role="status">Player removed from your squad.</div>
    @elseif (session('status') === 'squad-left')
        <div class="toast" role="status">You left the squad.</div>
    @endif
    @if ($errors->any())
        <div class="toast err" role="alert">{{ $errors->first() }}</div>
    @endif

    <form id="avatar-form" method="POST" action="{{ route('student.avatar.update') }}">@csrf @method('PUT')</form>
    @foreach (['head', 'skin_tone', 'hair_color', 'outfit', 'headgear', 'gear', 'held', 'suit_color', 'team_color', 'name_first', 'name_second', 'name_number'] as $field)
        <input type="hidden" form="avatar-form" name="{{ $field }}" id="f_{{ $field }}">
    @endforeach


    <main class="lb-wrap w-full">
        <div class="lb-top">
            <div>
                <p class="eyebrow">// multiplayer_lobby</p>
                <h1>Customize your operative</h1>
                <p class="sub">Pick how you look in the escape room and build your squad of 1 to {{ $squad['max'] }}. Your look and player name follow you into every room.</p>
            </div>
            <div class="lb-stat">
                <span class="on"><i></i>ONLINE</span>
                <span class="cnt" title="Players in your squad (1 to {{ $squad['max'] }})">
                    <svg width="20" height="16" viewBox="0 0 46 38" fill="currentColor" aria-hidden="true"><circle cx="23" cy="10" r="7"/><path d="M9 36c0-9 5-14 14-14s14 5 14 14z"/><circle cx="8" cy="14" r="5" opacity=".7"/><circle cx="38" cy="14" r="5" opacity=".7"/></svg>
                    {{ $squad['size'] }}/{{ $squad['max'] }}
                </span>
            </div>
        </div>

        <div class="chamber">
            <div class="hz" aria-hidden="true"></div>
            <svg class="bg" viewBox="0 0 1400 760" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
                <defs>
                    <linearGradient id="wall" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#0b1218"/><stop offset=".7" stop-color="#0a1118"/><stop offset="1" stop-color="#05080c"/></linearGradient>
                    <linearGradient id="floor" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#0c161d"/><stop offset="1" stop-color="#05080c"/></linearGradient>
                    <radialGradient id="tg"><stop offset="0" stop-color="#3fe0c5" stop-opacity=".4"/><stop offset="1" stop-color="#3fe0c5" stop-opacity="0"/></radialGradient>
                    <radialGradient id="og"><stop offset="0" stop-color="#ff8a3d" stop-opacity=".4"/><stop offset="1" stop-color="#ff8a3d" stop-opacity="0"/></radialGradient>
                    <filter id="bl14"><feGaussianBlur stdDeviation="12"/></filter>
                </defs>
                <rect width="1400" height="760" fill="url(#wall)"/>
                <g stroke="#16323a" stroke-width="2" opacity=".8"><path d="M0 80H1400M0 140H1400M280 0V140M700 0V140M1120 0V140"/></g>
                <path d="M560 120H612L628 130H544Z" fill="#bff6ec"/><ellipse cx="586" cy="150" rx="130" ry="44" fill="url(#tg)"/>
                <circle cx="880" cy="120" r="60" fill="#ff5d5d" opacity=".18"/><rect x="870" y="108" width="20" height="22" fill="#ff5d5d"/>
                <!-- vault door, like the welcome card -->
                <circle cx="880" cy="320" r="200" fill="#05080c" opacity=".85"/>
                <circle cx="880" cy="320" r="168" fill="none" stroke="#3fe0c5" stroke-width="12" opacity=".4" filter="url(#bl14)"/>
                <circle cx="880" cy="320" r="170" fill="none" stroke="#2a4650" stroke-width="2" stroke-dasharray="2 10"/>
                <circle class="pulse" cx="880" cy="320" r="140" fill="#0b1218" stroke="#3fe0c5" stroke-width="5" stroke-dasharray="64 22" opacity=".9"/>
                <circle cx="880" cy="320" r="112" fill="#0e1a22" stroke="#16323a" stroke-width="6"/>
                <g stroke="#16323a" stroke-width="12"><path d="M880 214v212M774 320h212"/></g>
                <circle cx="880" cy="320" r="38" fill="#0e1a22" stroke="#ff8a3d" stroke-width="3"/>
                <circle cx="880" cy="320" r="38" fill="#ff8a3d" opacity=".12"/>
                <!-- racks -->
                <rect x="300" y="190" width="140" height="340" fill="#0a1118" stroke="#16323a" stroke-width="3"/>
                <g stroke="#3fe0c5" stroke-width="3" opacity=".85">
                    <path d="M316 214h40M316 232h40M316 250h40M316 268h40M316 286h40M316 304h40M316 322h40M316 340h40M316 358h40M316 376h40M316 394h40M316 412h40M316 430h40M316 448h40M316 466h40M316 484h40"/>
                    <path d="M376 222h40M376 240h40M376 258h40M376 276h40M376 294h40M376 312h40M376 330h40M376 348h40M376 366h40M376 384h40M376 402h40M376 420h40M376 438h40M376 456h40M376 474h40"/>
                </g>
                <ellipse cx="370" cy="360" rx="120" ry="190" fill="url(#tg)" opacity=".35"/>
                <rect x="458" y="270" width="118" height="78" fill="#08141c" stroke="#2a4650" stroke-width="3"/>
                <g stroke="#3fe0c5" stroke-width="2" fill="none" opacity=".8"><rect x="472" y="284" width="24" height="16"/><rect x="510" y="290" width="24" height="16"/><path d="M496 292h14M520 306v14M480 324h60"/></g>
                <rect x="40" y="420" width="180" height="120" fill="#0b1218" stroke="#16323a" stroke-width="3"/><rect x="1230" y="430" width="150" height="110" fill="#0b1218" stroke="#16323a" stroke-width="3"/>
                <rect y="540" width="1400" height="220" fill="url(#floor)"/>
                <g stroke="#16323a" stroke-width="2" opacity=".9"><path d="M0 580H1400M0 640H1400M0 710H1400M200 540L-120 760M520 540L420 760M880 540L980 760M1230 540L1470 760"/></g>
                <ellipse cx="880" cy="570" rx="250" ry="24" fill="url(#og)"/>
                <ellipse cx="700" cy="660" rx="420" ry="64" fill="url(#tg)" opacity=".3"/>
            </svg>
            <div class="vig"></div>
            <div class="scan" aria-hidden="true"></div>

            <div class="main">
        <!-- PLAYERS -->
        <div class="left">
            <section class="panel players" aria-labelledby="pl">
                <div class="ph"><p class="eyebrow">// squad</p><h2 id="pl">Players</h2></div>
                @foreach ($squad['members'] as $m)
                    <div class="row {{ $m['isYou'] ? 'me' : '' }}">
                        <div class="pic"><div class="face" data-look="{{ json_encode($m['look']) }}" @if ($m['isYou']) id="meFace" @endif></div></div>
                        <div class="nm">
                            <span @if ($m['isYou']) id="meName" @endif>{{ $m['callsign'] }}</span>
                            @if ($m['isLeader'])
                                <svg class="crown" width="20" height="16" viewBox="0 0 24 18" fill="currentColor" aria-label="Squad leader"><path d="M2 16L0 4l7 5 5-8 5 8 7-5-2 12z"/></svg>
                            @elseif (! $m['isYou'])
                                <span class="on" aria-hidden="true"></span>
                            @endif
                        </div>
                        @if ($squad['isLeader'] && ! $m['isYou'])
                            <form method="POST" action="{{ route('student.squad.remove', $m['id']) }}" style="margin-left:auto">
                                @csrf @method('DELETE')
                                <button type="submit" class="x" title="Remove from squad" aria-label="Remove {{ $m['callsign'] }} from the squad">&times;</button>
                            </form>
                        @endif
                    </div>
                @endforeach

                @php $emptySlots = max(0, $squad['max'] - $squad['size']); @endphp
                @for ($i = 0; $i < $emptySlots; $i++)
                    @if ($i === 0 && $squad['canAdd'])
                        <details class="empty">
                            <summary><span class="plus">+</span><span>Empty Slot</span></summary>
                            @if ($squad['candidates']->isEmpty())
                                <p class="note">
                                    @if ($squad['hasClass']) No classmates are free right now. They may already be in a squad.
                                    @else You are not linked to a class yet. Ask your instructor to add you. @endif
                                </p>
                            @else
                                <div class="cands">
                                    @foreach ($squad['candidates'] as $c)
                                        <form method="POST" action="{{ route('student.squad.add') }}">
                                            @csrf
                                            <input type="hidden" name="user_id" value="{{ $c['id'] }}">
                                            <button type="submit" class="cand"><span class="face" data-look="{{ json_encode($c['look']) }}"></span><span>{{ $c['callsign'] }}</span></button>
                                        </form>
                                    @endforeach
                                </div>
                            @endif
                        </details>
                    @else
                        <div class="empty"><div class="slot"><span class="plus">+</span><span>Empty Slot</span></div></div>
                    @endif
                @endfor
                <p class="note">Squads are 1 to {{ $squad['max'] }} players. They do not have to be full.</p>
                @if ($squad['hasSquad'])
                    <form method="POST" action="{{ route('student.squad.leave') }}">@csrf<button type="submit" class="leave">Leave squad</button></form>
                @endif
            </section>

            <section class="panel info">
                <svg width="46" height="38" viewBox="0 0 46 38" fill="currentColor" aria-hidden="true"><circle cx="23" cy="10" r="7"/><path d="M9 36c0-9 5-14 14-14s14 5 14 14z"/><circle cx="8" cy="14" r="5" opacity=".7"/><circle cx="38" cy="14" r="5" opacity=".7"/></svg>
                <span>Customize your avatar and join the team. Work together to solve the puzzles and escape!</span>
            </section>
        </div>


        <!-- CENTRE -->
        <div class="stage" aria-label="Avatar preview">
            <div class="dais" aria-hidden="true">
                <svg viewBox="0 0 780 230">
                    <ellipse cx="390" cy="150" rx="380" ry="62" fill="#05080c"/>
                    <ellipse cx="390" cy="140" rx="372" ry="58" fill="#0b1218" stroke="#16323a" stroke-width="5"/>
                    <ellipse class="pulse" cx="390" cy="128" rx="352" ry="52" fill="none" stroke="#3fe0c5" stroke-width="5"/>
                    <ellipse cx="390" cy="122" rx="300" ry="42" fill="#0e1a22" stroke="#2a4650" stroke-width="3"/>
                    <ellipse cx="390" cy="120" rx="236" ry="32" fill="#0b1218" stroke="#ff8a3d" stroke-width="2" opacity=".8"/>
                    <ellipse cx="390" cy="118" rx="150" ry="20" fill="none" stroke="#2a4650" stroke-width="3"/>
                </svg>
            </div>
            <div class="fig" id="figure" role="img" aria-label="Your avatar"></div>
        </div>

        <!-- AVATAR CUSTOMIZATION -->
        <div>
            <section class="panel right" aria-labelledby="ac">
                <div class="ph"><p class="eyebrow">// avatar_customization</p><h2 id="ac">Avatar</h2></div>
                <div class="tabs" role="tablist">
                    <button type="button" role="tab" class="on" data-tab="head">Head</button>
                    <button type="button" role="tab" data-tab="outfit">Outfit</button>
                    <button type="button" role="tab" data-tab="acc">Accessories</button>
                    <button type="button" role="tab" data-tab="colors">Colors</button>
                </div>
                <div class="box">
                    <div class="pane on" id="pane-head"><div class="av-opts" id="optsHead"></div></div>
                    <div class="pane" id="pane-outfit"><div class="av-opts" id="optsOutfit"></div></div>
                    <div class="pane" id="pane-acc">
                        <div class="sub2">Headgear</div><div class="av-opts" id="optsHeadgear"></div>
                        <div class="sub2">Worn gear</div><div class="av-opts" id="optsGear"></div>
                        <div class="sub2">In your hand</div><div class="av-opts" id="optsHeld"></div>
                    </div>
                    <div class="pane" id="pane-colors">
                        <div class="sub2">Skin tone</div><div class="sw" id="skinSw"></div>
                        <div class="sub2">Hair color</div><div class="sw" id="hairSw"></div>
                        <div class="sub2">Suit color</div><div class="sw" id="suitSw"></div>
                    </div>
                </div>

                <div class="lbl">Player Name</div>
                <div class="nrow">
                    <div class="nval" id="nameVal" aria-live="polite"></div>
                    <button type="button" class="nbtn" id="nameEdit" title="Change name" aria-label="Change name">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3 17.25V21h3.75L17.8 9.94l-3.75-3.75zM20.7 7.04a1 1 0 000-1.41l-2.34-2.34a1 1 0 00-1.41 0l-1.83 1.83 3.75 3.75z"/></svg>
                    </button>
                </div>
                <div class="edit" id="nameEditor">
                    <select id="selFirst" aria-label="First word"></select>
                    <select id="selSecond" aria-label="Second word (optional)"></select>
                    <select id="selNum" aria-label="Number (optional)"></select>
                </div>

                <div class="lbl">Team Color</div>
                <div class="team sw" id="teamSw"></div>
            </section>

            <button type="submit" form="avatar-form" name="join" value="1" class="join">
                <svg width="38" height="30" viewBox="0 0 46 38" fill="currentColor" aria-hidden="true"><circle cx="23" cy="10" r="7"/><path d="M9 36c0-9 5-14 14-14s14 5 14 14z"/><circle cx="8" cy="14" r="5" opacity=".7"/><circle cx="38" cy="14" r="5" opacity=".7"/></svg>
                Join Game
            </button>
        </div>
    </div>
            <div class="hz b" aria-hidden="true"></div>
        </div>
        <a class="back" href="{{ route('student.dashboard') }}"><kbd>ESC</kbd> Back</a>
    </main>
</div>

    <script>
    (function () {
        var CAT = @json($catalog);
        var LOOK = @json($look);
        var DASH = @json(route('student.dashboard'));

        function byId(list, id) { for (var i = 0; i < list.length; i++) if (list[i].id === id) return list[i]; return list[0]; }
        function hexOf(list, id) { return byId(list, id).hex; }
        function shade(hex, amt) {
            var n = parseInt(hex.slice(1), 16), r = n >> 16, g = (n >> 8) & 255, b = n & 255;
            function f(c) { c = amt < 0 ? c * (1 + amt) : c + (255 - c) * amt; return Math.max(0, Math.min(255, Math.round(c))); }
            return '#' + ((1 << 24) + (f(r) << 16) + (f(g) << 8) + f(b)).toString(16).slice(1);
        }
        function callsign(first, second, num) { return first + (second || '') + (num ? '_' + num : ''); }
        function el(id) { return document.getElementById(id); }

        /* ================= drawing: flat SVG built from catalogue ids (no images) ================= */
        var CX = 120, CY = 74;      // head centre in the 240 x 446 figure
        var T = 'transform="translate(' + CX + ' ' + CY + ')"';

        function ctx(look) {
            return {
                head: byId(CAT.heads, look.head), skin: hexOf(CAT.skinTones, look.skin_tone), hair: hexOf(CAT.hairColors, look.hair_color),
                suit: hexOf(CAT.suitColors, look.suit_color), team: hexOf(CAT.teamColors, look.team_color)
            };
        }
        function P(d, fill, extra) { return '<path ' + T + ' fill="' + fill + '" d="' + d + '" ' + (extra || '') + '/>'; }

        function hairBack(style, c) {
            switch (style) {
                case 'long': return P('M-35,-8 C-42,40 -36,72 -26,80 L26,80 C36,72 42,40 35,-8 C31,-52 -31,-52 -35,-8Z', c);
                case 'bob': case 'bangs': return P('M-37,-8 C-42,22 -36,36 -27,40 L27,40 C36,36 42,22 37,-8 C33,-52 -33,-52 -37,-8Z', c);
                case 'ponytail': return P('M26,-34 C62,-34 64,26 40,50 C48,14 44,-8 28,-16Z', c);
                case 'pigtails': return P('M-30,-18 C-62,-14 -66,28 -46,46 C-50,16 -42,0 -30,-6Z', c) + P('M30,-18 C62,-14 66,28 46,46 C50,16 42,0 30,-6Z', c) +
                    '<circle ' + T + ' cx="-38" cy="-8" r="5" fill="#ff8a3d"/><circle ' + T + ' cx="38" cy="-8" r="5" fill="#ff8a3d"/>';
                case 'braid': {
                    var s = '<g ' + T + ' fill="' + c + '">';
                    for (var i = 0; i < 6; i++) s += '<ellipse cx="' + (36 + (i % 2 ? 3 : -2)) + '" cy="' + (-4 + i * 15) + '" rx="8" ry="10"/>';
                    return s + '<circle cx="36" cy="88" r="5" fill="#ff8a3d"/></g>';
                }
                case 'afro': return '<circle ' + T + ' cx="0" cy="-14" r="47" fill="' + c + '"/>';
                case 'wavy': return P('M-35,-4 C-43,14 -38,30 -29,34 L-24,6Z', c) + P('M35,-4 C43,14 38,30 29,34 L24,6Z', c);
            }
            return '';
        }
        function hairFront(style, c) {
            switch (style) {
                case 'short': case 'beard': case 'ponytail': case 'pigtails': case 'braid':
                    return P('M-32,-4 C-36,-46 36,-46 32,-4 C28,-22 12,-28 0,-26 C-12,-28 -28,-22 -32,-4Z', c);
                case 'bun': return P('M-32,-4 C-36,-46 36,-46 32,-4 C28,-22 12,-28 0,-26 C-12,-28 -28,-22 -32,-4Z', c) + '<circle ' + T + ' cx="0" cy="-52" r="15" fill="' + c + '"/><ellipse ' + T + ' cx="0" cy="-40" rx="12" ry="3" fill="#ff8a3d"/>';
                case 'side': return P('M-32,-2 C-38,-48 38,-48 32,-6 C22,-30 0,-24 -20,-14 C-27,-10 -30,-4 -32,-2Z', c);
                case 'fade': return P('M-31,-8 C-34,-42 -6,-52 14,-46 C30,-42 34,-24 31,-8 C27,-24 -27,-24 -31,-8Z', c);
                case 'buzz': return P('M-31,-8 C-33,-38 33,-38 31,-8 C27,-20 -27,-20 -31,-8Z', c, 'opacity=".85"');
                case 'spiky': return P('M-31,-8 L-31,-34 L-21,-24 L-15,-46 L-7,-28 L2,-50 L9,-28 L19,-46 L23,-24 L33,-34 L31,-8 C27,-20 -27,-20 -31,-8Z', c);
                case 'long': return P('M-34,0 C-40,-50 40,-50 34,0 C30,-22 16,-30 4,-28 C-14,-30 -28,-20 -34,0Z', c);
                case 'bob': return P('M-34,-2 C-40,-50 40,-50 34,-2 C26,-22 -26,-22 -34,-2Z', c);
                case 'bangs': return P('M-34,2 C-40,-52 40,-52 34,2 C28,-12 20,-18 0,-16 C-20,-18 -28,-12 -34,2Z', c);
                case 'wavy': return P('M-33,0 C-40,-50 40,-50 33,0 C28,-10 22,-26 6,-24 C-12,-22 -26,-12 -33,0Z', c);
                case 'afro': return P('M-34,-4 C-40,-52 40,-52 34,-4 C26,-24 -26,-24 -34,-4Z', c);
                case 'curly':
                    return '<g ' + T + ' fill="' + c + '"><circle cx="-24" cy="-22" r="13"/><circle cx="-8" cy="-33" r="14"/><circle cx="9" cy="-34" r="14"/><circle cx="25" cy="-24" r="13"/><circle cx="-32" cy="-6" r="9"/><circle cx="32" cy="-8" r="9"/></g>';
            }
            return '';
        }
        function face(skin, hair, gender, style) {
            var dark = shade(skin, -0.22), light = shade(skin, 0.14), girl = gender === 'girl';
            var rx = girl ? 27 : 30, ink = '#1a120c';
            var g = '<g ' + T + '>';
            g += '<path d="M-12,24 L-12,50 C-6,56 6,56 12,50 L12,24Z" fill="' + dark + '"/><path d="M-12,24 L12,24 L12,36 C4,42 -4,42 -12,36Z" fill="#000" opacity=".22"/>';
            g += '<ellipse cx="-' + (rx - 0.5) + '" cy="4" rx="5.5" ry="9" fill="' + dark + '"/><ellipse cx="' + (rx - 0.5) + '" cy="4" rx="5.5" ry="9" fill="' + dark + '"/>';
            g += '<path d="M-' + (rx + 1) + ',2 q3,-3 4,2 M' + (rx + 1) + ',2 q-3,-3 -4,2" stroke="' + shade(skin, -0.38) + '" stroke-width="1.4" fill="none"/>';
            // head shape: wider cheekbones, narrower jaw
            g += '<path d="M-' + rx + ',-6 C-' + rx + ',-40 ' + rx + ',-40 ' + rx + ',-6 C' + rx + ',14 ' + (girl ? 20 : 24) + ',34 ' + (girl ? 8 : 11) + ',38 C4,40 -4,40 -' + (girl ? 8 : 11) + ',38 C-' + (girl ? 20 : 24) + ',34 -' + rx + ',14 -' + rx + ',-6Z" fill="' + skin + '"/>';
            g += '<path d="M-' + rx + ',-6 C-' + rx + ',-40 ' + rx + ',-40 ' + rx + ',-6 C' + rx + ',14 ' + (girl ? 20 : 24) + ',34 ' + (girl ? 8 : 11) + ',38 C4,40 -4,40 -' + (girl ? 8 : 11) + ',38 C-' + (girl ? 20 : 24) + ',34 -' + rx + ',14 -' + rx + ',-6Z" fill="url(#shine)"/>';
            g += '<ellipse cx="0" cy="26" rx="16" ry="9" fill="' + dark + '" opacity=".18"/>';
            if (girl) g += '<ellipse cx="-16" cy="13" rx="7" ry="4" fill="#ff7080" opacity=".2"/><ellipse cx="16" cy="13" rx="7" ry="4" fill="#ff7080" opacity=".2"/>';
            // eyes: white, iris, pupil, glint, lid
            var ey = 1, er = girl ? 4.1 : 3.7;
            [-1, 1].forEach(function (k) {
                var x = 11 * k;
                g += '<ellipse cx="' + x + '" cy="' + ey + '" rx="5.4" ry="' + er + '" fill="#f3efe9"/>';
                g += '<circle cx="' + (x + k * 0.4) + '" cy="' + ey + '" r="' + (girl ? 3.2 : 2.9) + '" fill="#4a3322"/><circle cx="' + (x + k * 0.4) + '" cy="' + ey + '" r="1.5" fill="#0a0806"/>';
                g += '<circle cx="' + (x + k * 0.4 - 1) + '" cy="' + (ey - 1.1) + '" r="0.9" fill="#fff" opacity=".9"/>';
                g += '<path d="M' + (x - 5.6) + ',' + (ey + 0.2) + ' Q' + x + ',' + (ey - er - 1.8) + ' ' + (x + 5.6) + ',' + (ey + 0.2) + '" stroke="' + ink + '" stroke-width="' + (girl ? 1.9 : 1.5) + '" fill="none" stroke-linecap="round"/>';
                g += '<path d="M' + (x - 4.8) + ',' + (ey + er - 0.6) + ' Q' + x + ',' + (ey + er + 0.9) + ' ' + (x + 4.8) + ',' + (ey + er - 0.6) + '" stroke="' + dark + '" stroke-width=".8" fill="none"/>';
                if (girl) g += '<path d="M' + (x + k * 5.4) + ',' + (ey - 1) + ' l' + (k * 3) + ',-2.6" stroke="' + ink + '" stroke-width="1.5" stroke-linecap="round"/>';
                // eyebrow
                g += '<path d="M' + (x - 7) + ',-8.5 Q' + (x - 0.5 * k) + ',' + (girl ? -12.5 : -13) + ' ' + (x + 7) + ',-9.6" stroke="' + shade(hair, -0.15) + '" stroke-width="' + (girl ? 2.1 : 3.2) + '" fill="none" stroke-linecap="round"/>';
            });
            // nose
            g += '<path d="M-1.8,2 C-2.4,10 -4.6,14 -4.4,16.4 Q0,19.4 4.4,16.4 C4.6,14 2.4,10 1.8,2" fill="' + light + '" opacity=".55"/>';
            g += '<path d="M-4.6,16 Q-1.8,18.2 0,16.6 Q1.8,18.2 4.6,16" stroke="' + shade(skin, -0.42) + '" stroke-width="1.3" fill="none" stroke-linecap="round"/><ellipse cx="-2.8" cy="16.6" rx="1.1" ry=".7" fill="#000" opacity=".28"/><ellipse cx="2.8" cy="16.6" rx="1.1" ry=".7" fill="#000" opacity=".28"/>';
            g += '<path d="M3,3 C4,9 5,12 4.6,15" stroke="' + dark + '" stroke-width="1" fill="none" opacity=".5"/>';
            // mouth
            var lip = girl ? '#b8545e' : shade(skin, -0.3);
            g += '<path d="M-7.5,26 Q-3.5,24 0,25.4 Q3.5,24 7.5,26 Q0,31.5 -7.5,26Z" fill="' + lip + '"/>';
            g += '<path d="M-7.5,26 Q0,27.6 7.5,26" stroke="#2a0f10" stroke-width="1" fill="none" opacity=".7"/><path d="M-4,28.6 Q0,30.2 4,28.6" stroke="#fff" stroke-width=".9" fill="none" opacity=".35"/>';
            g += '<path d="M-3,33 Q0,34.4 3,33" stroke="' + dark + '" stroke-width="1.2" fill="none" opacity=".5"/>';
            if (style === 'beard') {
                g += '<path d="M-29,6 C-28,40 28,40 29,6 C23,26 15,28 8,24 Q0,22 -8,24 C-15,28 -23,26 -29,6Z" fill="' + hair + '"/><path d="M-9,21 Q0,17.5 9,21 Q0,24 -9,21Z" fill="' + hair + '"/>';
                g += '<path d="M-7.5,26 Q0,31 7.5,26" stroke="' + lip + '" stroke-width="2.2" fill="none" opacity=".9"/>';
            }
            return g + '</g>';
        }

        function headgear(id, team, suit, hair) {
            switch (id) {
                case 'cap': return '<g ' + T + '><path d="M-33,-8 C-36,-48 36,-48 33,-8 Z" fill="' + shade(suit, -0.3) + '"/><path d="M-34,-8 L-4,-8 C14,-8 44,-4 46,-1 C30,2 -2,0 -34,-2Z" fill="' + shade(suit, -0.45) + '"/><rect x="-3" y="-36" width="6" height="8" fill="' + team + '"/></g>';
                case 'headset': return '<g ' + T + '><path d="M-33,2 C-38,-52 38,-52 33,2" fill="none" stroke="#1b1e24" stroke-width="5"/><rect x="-39" y="-4" width="10" height="20" rx="4" fill="#23272f"/><rect x="29" y="-4" width="10" height="20" rx="4" fill="#23272f"/><rect x="-37" y="0" width="3" height="10" fill="' + team + '"/><path d="M-34,14 C-30,32 -14,30 -8,28" fill="none" stroke="#1b1e24" stroke-width="3"/><circle cx="-8" cy="28" r="3.5" fill="' + team + '"/></g>';
                case 'visor': return '<g ' + T + '><rect x="-33" y="-6" width="66" height="14" rx="6" fill="#0b0d11" stroke="' + team + '" stroke-width="2"/><rect x="-28" y="-2" width="56" height="5" rx="2.5" fill="' + team + '" opacity=".9"/></g>';
                case 'goggles': return '<g ' + T + '><path d="M-34,-18 L34,-18" stroke="#1b1e24" stroke-width="6"/><circle cx="-14" cy="-20" r="11" fill="#12151a" stroke="' + team + '" stroke-width="3"/><circle cx="14" cy="-20" r="11" fill="#12151a" stroke="' + team + '" stroke-width="3"/><circle cx="-14" cy="-20" r="6" fill="' + team + '" opacity=".55"/><circle cx="14" cy="-20" r="6" fill="' + team + '" opacity=".55"/></g>';
                case 'helmet': return '<g ' + T + '><path d="M-37,-4 C-42,-58 42,-58 37,-4 L28,-6 C20,-28 -20,-28 -28,-6Z" fill="' + shade(suit, -0.1) + '" stroke="#0b0d11" stroke-width="2"/><rect x="-5" y="-50" width="10" height="26" fill="' + team + '"/></g>';
                case 'fedora': return '<g ' + T + '><ellipse cx="0" cy="-24" rx="50" ry="10" fill="#2b2118"/><path d="M-27,-25 C-27,-64 27,-64 27,-25Z" fill="#3a2d20"/><rect x="-27" y="-36" width="54" height="9" fill="' + team + '"/><path d="M-27,-25 C-14,-17 14,-17 27,-25" fill="none" stroke="#1d150e" stroke-width="2"/></g>';
                case 'headlamp': return '<g ' + T + '><circle cx="0" cy="-28" r="20" fill="#fff6c0" opacity=".16"/><path d="M-33,-14 C-20,-24 20,-24 33,-14" stroke="#1b1e24" stroke-width="6" fill="none"/><rect x="-10" y="-35" width="20" height="14" rx="4" fill="#2a2e36" stroke="' + team + '" stroke-width="2"/><circle cx="0" cy="-28" r="5" fill="#fff6c0"/></g>';
                case 'bandana': return '<g ' + T + '><path d="M-33,-14 C-20,-26 20,-26 33,-14 L33,-4 C20,-16 -20,-16 -33,-4Z" fill="' + team + '"/><circle cx="-18" cy="-14" r="2" fill="#fff" opacity=".6"/><circle cx="-4" cy="-17" r="2" fill="#fff" opacity=".6"/><circle cx="12" cy="-16" r="2" fill="#fff" opacity=".6"/><path d="M31,-10 L48,-20 L46,-2Z" fill="' + shade(team, -0.2) + '"/></g>';
                case 'beret': return '<g ' + T + '><ellipse cx="-4" cy="-34" rx="36" ry="14" fill="' + shade(suit, -0.35) + '" transform="rotate(-8)"/><circle cx="-6" cy="-48" r="3.5" fill="' + shade(suit, -0.55) + '"/><path d="M-34,-24 C-20,-18 20,-18 34,-24" stroke="' + shade(suit, -0.55) + '" stroke-width="4" fill="none"/></g>';
            }
            return '';
        }

        function legsAndBoots(outfit, suit) {
            var pants = outfit === 'outfit_mechanic' ? shade(suit, -0.15) : (outfit === 'outfit_agent' ? shade(suit, -0.35) : shade(suit, -0.5)), boot = '#14171c';
            var legsTo = 380;
            var o = '<path d="M82,236 L118,236 L116,' + (legsTo + 30) + ' L90,' + (legsTo + 30) + 'Z" fill="' + pants + '"/>';
            o += '<path d="M122,236 L158,236 L150,' + (legsTo + 30) + ' L124,' + (legsTo + 30) + 'Z" fill="' + pants + '"/>';
            o += '<path d="M86,404 L116,404 L120,436 L74,436 L78,412Z" fill="' + boot + '"/><path d="M124,404 L154,404 L166,436 L122,436Z" fill="' + boot + '"/>';
            o += '<rect x="74" y="428" width="46" height="8" rx="3" fill="#07080a"/><rect x="122" y="428" width="46" height="8" rx="3" fill="#07080a"/>';
            return o;
        }
        function arms(outfit, suit, skin) {
            var sleeve = shade(suit, -0.14), cuff = shade(suit, -0.45);
            if (outfit === 'outfit_labcoat') { sleeve = shade(suit, 0.2); cuff = shade(suit, -0.1); }
            if (outfit === 'outfit_explorer' || outfit === 'outfit_mechanic') { sleeve = shade(suit, 0.42); cuff = shade(suit, 0.2); }
            if (outfit === 'outfit_agent') { sleeve = shade(suit, -0.3); cuff = '#e8eaee'; }
            var o = '<path d="M76,124 C62,150 56,214 60,266 L86,268 C90,216 92,166 94,130Z" fill="' + sleeve + '"/>';
            o += '<path d="M164,124 C178,150 184,214 180,266 L154,268 C150,216 148,166 146,130Z" fill="' + sleeve + '"/>';
            o += '<path d="M60,254 L86,256 L86,270 L60,268Z" fill="' + cuff + '"/><path d="M180,254 L154,256 L154,270 L180,268Z" fill="' + cuff + '"/>';
            o += '<ellipse cx="73" cy="284" rx="10" ry="13" fill="' + skin + '"/><ellipse cx="167" cy="284" rx="10" ry="13" fill="' + skin + '"/>';
            return o;
        }
        function body(outfit, suit, team) {
            var d = shade(suit, -0.35), l = shade(suit, 0.18);
            var base = '<path d="M70,126 C92,112 148,112 170,126 L174,240 C150,250 90,250 66,240Z" fill="' + suit + '"/>';
            var out = '';
            switch (outfit) {
                case 'outfit_labcoat':
                    out += '<path d="M66,126 C92,112 148,112 174,126 L182,330 L58,330Z" fill="' + shade(suit, 0.25) + '"/>';
                    out += '<path d="M120,118 L120,330" stroke="' + shade(suit, -0.2) + '" stroke-width="2.5"/><path d="M98,120 L120,172 L142,120" fill="' + shade(suit, 0.4) + '" stroke="' + shade(suit, -0.2) + '" stroke-width="1.5"/>';
                    out += '<rect x="76" y="262" width="30" height="24" fill="' + shade(suit, -0.1) + '" opacity=".5"/><rect x="134" y="262" width="30" height="24" fill="' + shade(suit, -0.1) + '" opacity=".5"/>';
                    out += '<rect x="84" y="150" width="14" height="5" fill="' + team + '"/>';
                    break;
                case 'outfit_hazmat':
                    out += base;
                    out += '<path d="M68,196 L172,196 L173,216 L67,216Z" fill="#f2c200"/><path d="M78,196 L96,196 L88,216 L70,216Z M112,196 L130,196 L122,216 L104,216Z M146,196 L164,196 L156,216 L138,216Z" fill="#111"/>';
                    out += '<rect x="92" y="136" width="56" height="34" rx="6" fill="' + d + '"/><circle cx="106" cy="153" r="7" fill="#101318" stroke="' + team + '" stroke-width="2"/><circle cx="134" cy="153" r="7" fill="#101318" stroke="' + team + '" stroke-width="2"/>';
                    break;
                case 'outfit_hoodie':
                    out += base;
                    out += '<path d="M88,118 C100,152 140,152 152,118 L142,113 C134,136 106,136 98,113Z" fill="' + d + '"/>';
                    out += '<path d="M104,140 L102,178 M136,140 L138,178" stroke="#d9dde3" stroke-width="2.5" stroke-linecap="round"/>';
                    out += '<path d="M88,206 L152,206 L158,238 L82,238Z" fill="' + shade(suit, -0.2) + '"/><rect x="106" y="180" width="28" height="4" fill="' + team + '"/>';
                    out += '<text x="120" y="172" font-size="14" text-anchor="middle" fill="' + team + '" font-family="monospace" font-weight="700">&lt;/&gt;</text>';
                    break;
                case 'outfit_armor':
                    out += base;
                    out += '<path d="M84,126 C102,116 138,116 156,126 L162,208 C140,218 100,218 78,208Z" fill="' + shade(suit, -0.25) + '" stroke="#0b0d11" stroke-width="2"/>';
                    out += '<path d="M120,120 L120,214" stroke="#0b0d11" stroke-width="2"/><rect x="96" y="152" width="48" height="6" fill="' + team + '"/>';
                    out += '<ellipse cx="70" cy="130" rx="17" ry="13" fill="' + shade(suit, -0.2) + '" stroke="#0b0d11" stroke-width="2"/><ellipse cx="170" cy="130" rx="17" ry="13" fill="' + shade(suit, -0.2) + '" stroke="#0b0d11" stroke-width="2"/>';
                    break;
                case 'outfit_jumpsuit':
                    out += base;
                    out += '<path d="M120,120 L120,242" stroke="' + d + '" stroke-width="3"/><rect x="90" y="156" width="24" height="22" rx="2" fill="' + l + '" opacity=".5"/><rect x="126" y="156" width="24" height="22" rx="2" fill="' + l + '" opacity=".5"/>';
                    out += '<rect x="68" y="228" width="104" height="10" fill="' + team + '"/>';
                    break;
                case 'outfit_detective':
                    out += '<path d="M66,126 C92,112 148,112 174,126 L184,332 L56,332Z" fill="' + shade(suit, -0.05) + '"/>';
                    out += '<path d="M120,120 L120,332" stroke="' + shade(suit, -0.4) + '" stroke-width="2.5"/>';
                    out += '<path d="M90,114 L120,170 L104,208 L76,140Z" fill="' + shade(suit, -0.3) + '"/><path d="M150,114 L120,170 L136,208 L164,140Z" fill="' + shade(suit, -0.3) + '"/>';
                    out += '<rect x="62" y="206" width="116" height="10" fill="' + shade(suit, -0.5) + '"/><rect x="112" y="204" width="16" height="14" rx="2" fill="' + team + '"/>';
                    out += '<circle cx="108" cy="240" r="3" fill="#0b0d11"/><circle cx="132" cy="240" r="3" fill="#0b0d11"/><circle cx="108" cy="270" r="3" fill="#0b0d11"/><circle cx="132" cy="270" r="3" fill="#0b0d11"/>';
                    break;
                case 'outfit_explorer':
                    out += '<path d="M70,126 C92,112 148,112 170,126 L174,240 C150,250 90,250 66,240Z" fill="' + shade(suit, 0.42) + '"/>';
                    out += '<path d="M72,128 L112,118 L108,238 L68,240Z" fill="' + suit + '"/><path d="M168,128 L128,118 L132,238 L172,240Z" fill="' + suit + '"/>';
                    out += '<rect x="78" y="160" width="26" height="24" rx="2" fill="' + shade(suit, -0.25) + '"/><rect x="136" y="160" width="26" height="24" rx="2" fill="' + shade(suit, -0.25) + '"/>';
                    out += '<rect x="76" y="196" width="28" height="26" rx="2" fill="' + shade(suit, -0.25) + '"/><rect x="136" y="196" width="28" height="26" rx="2" fill="' + shade(suit, -0.25) + '"/>';
                    out += '<rect x="86" y="134" width="12" height="6" fill="' + team + '"/>';
                    break;
                case 'outfit_mechanic':
                    out += '<path d="M70,126 C92,112 148,112 170,126 L174,240 C150,250 90,250 66,240Z" fill="' + shade(suit, 0.42) + '"/>';
                    out += '<path d="M92,150 L148,150 L152,248 L88,248Z" fill="' + suit + '"/><path d="M96,150 L88,118 M144,150 L152,118" stroke="' + suit + '" stroke-width="9" stroke-linecap="round" fill="none"/>';
                    out += '<rect x="104" y="170" width="32" height="26" rx="3" fill="' + shade(suit, -0.25) + '"/><circle cx="96" cy="154" r="3" fill="#c9a24a"/><circle cx="144" cy="154" r="3" fill="#c9a24a"/>';
                    out += '<path d="M112,178 L128,190 M128,178 L112,190" stroke="' + team + '" stroke-width="3" stroke-linecap="round"/>';
                    break;
                case 'outfit_agent':
                    out += '<path d="M70,126 C92,112 148,112 170,126 L174,240 C150,250 90,250 66,240Z" fill="' + shade(suit, -0.3) + '"/>';
                    out += '<path d="M102,118 L120,182 L138,118Z" fill="#e8eaee"/>';
                    out += '<path d="M96,118 L120,190 L102,204 L84,150Z" fill="' + shade(suit, -0.5) + '"/><path d="M144,118 L120,190 L138,204 L156,150Z" fill="' + shade(suit, -0.5) + '"/>';
                    out += '<path d="M116,128 L124,128 L127,186 L120,198 L113,186Z" fill="' + team + '"/>';
                    out += '<rect x="136" y="170" width="14" height="4" fill="#e8eaee"/>';
                    break;
                default: /* field suit */
                    out += base;
                    out += '<path d="M70,126 C92,112 148,112 170,126 L166,142 C146,132 94,132 74,142Z" fill="' + l + '" opacity=".6"/>';
                    out += '<path d="M120,118 L120,240" stroke="' + d + '" stroke-width="2.5"/><rect x="90" y="172" width="22" height="28" rx="2" fill="' + d + '" opacity=".55"/><rect x="128" y="172" width="22" height="28" rx="2" fill="' + d + '" opacity=".55"/>';
                    out += '<rect x="62" y="138" width="14" height="30" fill="' + team + '"/>';
                    out += '<rect x="68" y="228" width="104" height="11" fill="#1a1d23"/><rect x="112" y="226" width="16" height="15" fill="' + team + '"/>';
            }
            return out;
        }

        function gearBack(id, suit) { return id === 'backpack' ? '<rect x="56" y="104" width="128" height="150" rx="22" fill="' + shade(suit, -0.55) + '"/><rect x="72" y="96" width="96" height="22" rx="10" fill="' + shade(suit, -0.4) + '"/>' : ''; }
        function gearFront(id, team, suit) {
            switch (id) {
                case 'backpack': return '<path d="M84,122 L96,226 M156,122 L144,226" stroke="' + shade(suit, -0.6) + '" stroke-width="9" stroke-linecap="round" fill="none"/><rect x="88" y="196" width="10" height="8" fill="#c9a24a"/><rect x="142" y="196" width="10" height="8" fill="#c9a24a"/>';
                case 'toolbelt': return '<rect x="70" y="228" width="100" height="12" fill="#3a2a1a"/><rect x="82" y="238" width="16" height="22" rx="3" fill="#4a3522"/><rect x="142" y="238" width="16" height="22" rx="3" fill="#4a3522"/><rect x="113" y="226" width="14" height="16" fill="#c9a24a"/><path d="M150,242 L156,262" stroke="#9aa3ad" stroke-width="3"/>';
                case 'badge': return '<rect x="132" y="146" width="22" height="30" rx="3" fill="#e9eaee"/><rect x="136" y="150" width="14" height="10" fill="' + team + '"/><rect x="136" y="164" width="14" height="3" fill="#7a808a"/><path d="M142,146 L136,120 M142,146 L150,120" stroke="' + team + '" stroke-width="1.5"/>';
                case 'keycard': return '<path d="M104,114 L116,176 M136,114 L124,176" stroke="' + team + '" stroke-width="3.5" fill="none"/><rect x="108" y="172" width="24" height="32" rx="3" fill="#e9eaee" stroke="#0b0d11" stroke-width="1.5"/><rect x="108" y="172" width="24" height="7" fill="' + team + '"/><rect x="114" y="184" width="10" height="8" rx="1.5" fill="#c9a24a"/><rect x="113" y="196" width="14" height="2" fill="#7a808a"/>';
            }
            return '';
        }
        function heldItem(id, team) {
            var g = '<g transform="translate(167 284)">';
            switch (id) {
                case 'lantern': return g + '<circle cx="0" cy="14" r="26" fill="' + team + '" opacity=".16"/><rect x="-3" y="-14" width="6" height="10" fill="#2a2e36"/><rect x="-10" y="-4" width="20" height="28" rx="4" fill="' + team + '" opacity=".95"/><rect x="-10" y="-4" width="20" height="28" rx="4" fill="none" stroke="#1a1d23" stroke-width="3"/></g>';
                case 'flashlight': return g + '<path d="M-7,-16 L-30,-70 L30,-70 L7,-16Z" fill="#fff6c0" opacity=".2"/><rect x="-5" y="-8" width="10" height="36" rx="3" fill="#2a2e36"/><rect x="-9" y="-18" width="18" height="11" rx="2" fill="#566070"/><rect x="-5" y="2" width="10" height="3" fill="' + team + '"/></g>';
                case 'magnifier': return g + '<line x1="0" y1="2" x2="-4" y2="24" stroke="#5a3a1e" stroke-width="7" stroke-linecap="round"/><circle cx="8" cy="-16" r="18" fill="#3fe0c5" fill-opacity=".22" stroke="#b8c4d0" stroke-width="4.5"/><path d="M-2,-22 Q4,-30 14,-28" stroke="#fff" stroke-width="2" fill="none" opacity=".7"/></g>';
                case 'clipboard': return g + '<rect x="-19" y="-30" width="38" height="52" rx="3" fill="#7a5a34"/><rect x="-15" y="-24" width="30" height="42" fill="#e8eaee"/><rect x="-7" y="-33" width="14" height="8" rx="2" fill="#9aa3ad"/><path d="M-10,-14 L-6,-10 L1,-19" stroke="#2fbf71" stroke-width="2.4" fill="none" stroke-linecap="round" stroke-linejoin="round"/><path d="M-10,0 L-4,6 M-4,0 L-10,6" stroke="#e23b3b" stroke-width="2.4" stroke-linecap="round"/><rect x="2" y="-10" width="10" height="2" fill="#7a808a"/><rect x="2" y="2" width="10" height="2" fill="#7a808a"/><rect x="-10" y="11" width="22" height="2" fill="#7a808a"/></g>';
                case 'bugjar': return g + '<rect x="-12" y="-8" width="24" height="32" rx="6" fill="#9fe8dd" fill-opacity=".28" stroke="#cfeee9" stroke-width="2"/><rect x="-13" y="-15" width="26" height="8" rx="2" fill="#a3acb5"/><ellipse cx="0" cy="12" rx="5" ry="7" fill="#ff5d5d"/><path d="M-5,8 L-10,5 M5,8 L10,5 M-5,13 L-10,14 M5,13 L10,14 M-2,5 L-4,0 M2,5 L4,0" stroke="#7a1d1d" stroke-width="1.4" stroke-linecap="round"/></g>';
                case 'radio': return g + '<rect x="4" y="-36" width="3.5" height="24" rx="1.5" fill="#566070"/><rect x="-10" y="-14" width="22" height="38" rx="4" fill="#2a2e36"/><rect x="-6" y="-9" width="14" height="9" rx="2" fill="#2fbf71" opacity=".8"/><circle cx="-1" cy="10" r="4" fill="' + team + '"/><rect x="-6" y="17" width="14" height="2" fill="#566070"/></g>';
            }
            return '';
        }

        function figure(look) {
            var c = ctx(look);
            var svg = '<svg viewBox="0 0 240 446" xmlns="http://www.w3.org/2000/svg">';
            svg += '<ellipse cx="120" cy="438" rx="92" ry="9" fill="#000" opacity=".45"/>';
            svg += '<g filter="url(#vol)">' + gearBack(look.gear, c.suit) + '</g>';
            svg += '<g filter="url(#vol)">' + hairBack(c.head.hairStyle, c.hair) + '</g>';
            svg += '<g filter="url(#vol)">' + legsAndBoots(look.outfit, c.suit) + '</g>';
            svg += '<g filter="url(#vol)">' + arms(look.outfit, c.suit, c.skin) + '</g>';
            svg += '<g filter="url(#vol)">' + body(look.outfit, c.suit, c.team) + '</g>';
            svg += '<g filter="url(#vol)">' + face(c.skin, c.hair, c.head.gender, c.head.hairStyle) + '</g>';
            svg += '<g filter="url(#vol)">' + hairFront(c.head.hairStyle, c.hair) + '</g>';
            svg += '<g filter="url(#vol)">' + headgear(look.headgear, c.team, c.suit, c.hair) + '</g>';
            svg += '<g filter="url(#vol)">' + gearFront(look.gear, c.team, c.suit) + '</g>';
            svg += '<g filter="url(#vol)">' + heldItem(look.held, c.team) + '</g>';
            return svg + '</svg>';
        }
        /* head and shoulders only (faces in the squad list and the option tiles) */
        function bust(look, viewBox) {
            var c = ctx(look);
            var svg = '<svg viewBox="' + (viewBox || '58 18 124 124') + '" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">';
            svg += '<rect x="0" y="0" width="240" height="200" fill="#0e1a22"/>';
            svg += '<g filter="url(#vol)">' + hairBack(c.head.hairStyle, c.hair) + '<path d="M60,170 C60,124 90,112 120,112 C150,112 180,124 180,170Z" fill="' + c.suit + '"/></g>';
            svg += '<g filter="url(#vol)">' + face(c.skin, c.hair, c.head.gender, c.head.hairStyle) + hairFront(c.head.hairStyle, c.hair) + headgear(look.headgear, c.team, c.suit, c.hair) + '</g>';
            return svg + '</svg>';
        }
        function crop(look, box) { return figure(look).replace('viewBox="0 0 240 446"', 'viewBox="' + box + '" preserveAspectRatio="xMidYMid slice"'); }

        /* ================= state and lists ================= */
        var S = {};
        ['head', 'skin_tone', 'hair_color', 'outfit', 'headgear', 'gear', 'held', 'suit_color', 'team_color'].forEach(function (k) { S[k] = LOOK[k]; });
        S.first = LOOK.name.first; S.second = LOOK.name.second || ''; S.number = LOOK.name.number ? String(LOOK.name.number) : '';
        function cur() { var l = {}; for (var k in S) if (k !== 'first' && k !== 'second' && k !== 'number') l[k] = S[k]; return l; }
        function withLook(over) { var l = cur(); for (var k in over) l[k] = over[k]; return l; }

        function tile(inner, label, on, onClick, aria) {
            var b = document.createElement('button');
            b.type = 'button'; b.className = 'av-opt' + (on ? ' on' : ''); b.setAttribute('aria-pressed', on ? 'true' : 'false'); b.setAttribute('aria-label', aria || label || 'option');
            b.innerHTML = inner + (label ? '<span class="lb">' + label + '</span>' : '');
            b.addEventListener('click', onClick);
            return b;
        }
        function swatch(hex, name, on, onClick) {
            var b = document.createElement('button');
            b.type = 'button'; b.className = 'av-swb' + (on ? ' on' : ''); b.style.background = hex; b.setAttribute('aria-label', name); b.setAttribute('aria-pressed', on ? 'true' : 'false'); b.title = name;
            b.addEventListener('click', onClick);
            return b;
        }
        function fillTiles(box, items, key, inner, labeled) {
            var h = el(box); h.innerHTML = '';
            items.forEach(function (x, i) {
                h.appendChild(tile(inner(x), labeled ? x.label : '', S[key] === x.id, function () { S[key] = x.id; update(); }, labeled ? x.label : 'Face ' + (i + 1)));
            });
        }
        function fillSwatches(box, items, key) {
            var h = el(box); h.innerHTML = '';
            items.forEach(function (c) { h.appendChild(swatch(c.hex, c.id.replace(/_/g, ' '), S[key] === c.id, function () { S[key] = c.id; update(); })); });
        }

        function renderLists() {
            fillTiles('optsHead', CAT.heads, 'head', function (x) { return bust(withLook({ head: x.id, headgear: 'none' })); }, false);
            fillTiles('optsOutfit', CAT.outfits, 'outfit', function (x) { return crop(withLook({ outfit: x.id, held: 'none', gear: 'none' }), '30 96 180 220'); }, true);
            fillTiles('optsHeadgear', CAT.headgear, 'headgear', function (x) { return bust(withLook({ headgear: x.id })); }, true);
            fillTiles('optsGear', CAT.gear, 'gear', function (x) { return crop(withLook({ gear: x.id, held: 'none' }), '30 96 180 200'); }, true);
            fillTiles('optsHeld', CAT.held, 'held', function (x) { return crop(withLook({ held: x.id, gear: 'none' }), '105 215 125 105'); }, true);
            fillSwatches('skinSw', CAT.skinTones, 'skin_tone');
            fillSwatches('hairSw', CAT.hairColors, 'hair_color');
            fillSwatches('suitSw', CAT.suitColors, 'suit_color');
            fillSwatches('teamSw', CAT.teamColors, 'team_color');
        }

        function drawFaces() {
            document.querySelectorAll('.face[data-look]').forEach(function (f) {
                if (f.id === 'meFace') { f.innerHTML = bust(cur()); return; }
                try { f.innerHTML = bust(JSON.parse(f.getAttribute('data-look'))); } catch (e) {}
            });
        }

        function syncForm() {
            ['head', 'skin_tone', 'hair_color', 'outfit', 'headgear', 'gear', 'held', 'suit_color', 'team_color'].forEach(function (k) { el('f_' + k).value = S[k]; });
            el('f_name_first').value = S.first; el('f_name_second').value = S.second; el('f_name_number').value = S.number;
        }

        function update() {
            el('figure').innerHTML = figure(cur());
            var name = callsign(S.first, S.second, S.number);
            el('nameVal').textContent = name;
            if (el('meName')) el('meName').textContent = name;
                        
                        renderLists(); drawFaces(); syncForm();
        }

        /* ================= name pickers (only approved words) ================= */
        function fillSelect(sel, items, current, blank) {
            sel.innerHTML = '';
            if (blank) { var b = document.createElement('option'); b.value = ''; b.textContent = blank; sel.appendChild(b); }
            items.forEach(function (w) { var o = document.createElement('option'); o.value = w; o.textContent = w; sel.appendChild(o); });
            sel.value = current || '';
        }
        var nums = []; for (var n = 1; n <= 99; n++) nums.push(String(n));
        fillSelect(el('selFirst'), CAT.nameWords, S.first);
        fillSelect(el('selSecond'), CAT.nameWords, S.second, '(none)');
        fillSelect(el('selNum'), nums, S.number, '#');
        ['selFirst', 'selSecond', 'selNum'].forEach(function (id) {
            el(id).addEventListener('change', function () { S.first = el('selFirst').value; S.second = el('selSecond').value; S.number = el('selNum').value; update(); });
        });
        el('nameEdit').addEventListener('click', function () { el('nameEditor').classList.toggle('on'); });
        if (el('nameDice')) el('nameDice').addEventListener('click', function () {
            var w = CAT.nameWords;
            S.first = w[Math.floor(Math.random() * w.length)];
            S.second = Math.random() < 0.4 ? w[Math.floor(Math.random() * w.length)] : '';
            S.number = Math.random() < 0.8 ? String(1 + Math.floor(Math.random() * 99)) : '';
            el('selFirst').value = S.first; el('selSecond').value = S.second; el('selNum').value = S.number;
            update();
        });

        /* ================= tabs and keys ================= */
        document.querySelectorAll('.tabs button').forEach(function (b) {
            b.addEventListener('click', function () {
                document.querySelectorAll('.tabs button').forEach(function (x) { x.classList.toggle('on', x === b); x.setAttribute('aria-selected', x === b ? 'true' : 'false'); });
                document.querySelectorAll('.pane').forEach(function (p) { p.classList.toggle('on', p.id === 'pane-' + b.dataset.tab); });
            });
        });
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            if (el('nameEditor').classList.contains('on')) { el('nameEditor').classList.remove('on'); return; }
            var t = e.target && e.target.tagName;
            if (t === 'SELECT' || t === 'INPUT') return;
            window.location.href = DASH;
        });

        @if ($errors->has('name_first'))
            el('nameEditor').classList.add('on');
        @endif

        update();
    })();
    </script>
</body>
</html>
