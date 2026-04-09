<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="theme-color" content="#1a56db">
    <meta name="description" content="تطبيق المندوب الذكي - تطبيق لتسجيل طلبيات الأدوية بسهولة وسرعة للمندوبين">
    <meta property="og:title" content="المندوب الذكي">
    <meta property="og:description" content="تطبيق لتسجيل طلبيات الأدوية بسهولة وسرعة للمندوبين">
    <title>تحميل تطبيق المندوب الذكي</title>

    <!-- Google Fonts: Cairo & Amiri -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --primary:        #1A4275;
            --primary-dark:   #0D2A4F;
            --primary-light:  #e6edf7;
            --secondary:      #245A9E;
            --accent:         #06b6d4;
            --success:        #10b981;
            --warning:        #f59e0b;
            --danger:         #ef4444;
            --text-dark:      #111827;
            --text-medium:    #374151;
            --text-light:     #6b7280;
            --text-muted:     #9ca3af;
            --bg:             #F8FAFC;
            --surface:        #ffffff;
            --border:         #e5e7eb;
            --shadow-sm:      0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.06);
            --shadow-md:      0 4px 16px rgba(0,0,0,.10), 0 2px 6px rgba(0,0,0,.06);
            --shadow-lg:      0 12px 32px rgba(26,66,117,.15);
            --radius:         16px;
            --radius-sm:      10px;
            --radius-lg:      24px;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Cairo', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text-dark);
            min-height: 100vh;
            direction: rtl;
            line-height: 1.6;
        }

        h1, h2, h3, .modal-title, .section-title, .card-label {
            font-family: 'Amiri', serif;
        }

        /* ── HERO ─────────────────────────────────── */
        .hero {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            padding: 56px 20px 80px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Ccircle cx='30' cy='30' r='20'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
            pointer-events: none;
        }

        .hero::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0; right: 0;
            height: 60px;
            background: var(--bg);
            clip-path: ellipse(55% 100% at 50% 100%);
        }

        .hero-inner { position: relative; z-index: 1; max-width: 480px; margin: 0 auto; }

        .app-logo {
            width: 96px;
            height: 96px;
            background: rgba(255,255,255,.18);
            border-radius: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,.22);
            border: 2px solid rgba(255,255,255,.3);
            backdrop-filter: blur(8px);
        }

        .app-logo svg {
            width: 52px;
            height: 52px;
            fill: #ffffff;
        }

        .hero h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: .5px;
            margin-bottom: 10px;
            text-shadow: 0 2px 8px rgba(0,0,0,.3);
        }

        .hero .tagline {
            font-size: 1rem;
            color: rgba(255,255,255,.88);
            font-weight: 500;
            max-width: 340px;
            margin: 0 auto 28px;
            line-height: 1.7;
        }

        .version-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,.2);
            border: 1px solid rgba(255,255,255,.35);
            border-radius: 50px;
            padding: 6px 16px;
            font-size: .8rem;
            font-weight: 600;
            color: #ffffff;
            backdrop-filter: blur(8px);
        }

        .version-badge svg { width: 14px; height: 14px; flex-shrink: 0; }

        /* ── MAIN CONTAINER ───────────────────────── */
        .container {
            max-width: 520px;
            margin: 0 auto;
            padding: 0 16px 40px;
        }

        /* ── ERROR CARD ───────────────────────────── */
        .error-card {
            background: #fff7ed;
            border: 1.5px solid #fed7aa;
            border-radius: var(--radius);
            padding: 28px 24px;
            text-align: center;
            margin-top: -30px;
            box-shadow: var(--shadow-md);
        }

        .error-card .error-icon {
            font-size: 2.5rem;
            margin-bottom: 12px;
        }

        .error-card h3 {
            color: #92400e;
            font-size: 1.05rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .error-card p {
            color: #b45309;
            font-size: .9rem;
        }

        /* ── DOWNLOAD CARD ────────────────────────── */
        .download-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: 32px 24px 28px;
            box-shadow: var(--shadow-lg);
            margin-top: -30px;
            border: 1px solid var(--border);
        }

        .download-card .card-label {
            font-size: .78rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px;
            text-align: center;
        }

        /* ── BUTTONS ──────────────────────────────── */
        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 16px 24px;
            border-radius: var(--radius);
            font-family: 'Cairo', sans-serif;
            font-size: 1.05rem;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: transform .15s ease, box-shadow .15s ease, background .15s ease;
            position: relative;
            overflow: hidden;
        }

        .btn:active { transform: scale(.97); }

        .btn svg { width: 22px; height: 22px; flex-shrink: 0; }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #ffffff;
            box-shadow: 0 4px 20px rgba(26,66,117,.38);
            margin-bottom: 14px;
            font-size: 1.12rem;
        }

        .btn-primary:hover {
            box-shadow: 0 6px 28px rgba(26,66,117,.48);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: var(--surface);
            color: var(--primary);
            border: 1.5px solid var(--primary);
            font-size: .95rem;
        }

        .btn-secondary:hover {
            background: var(--primary-light);
        }

        .arch-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 14px;
        }

        .arch-row .btn {
            flex-direction: column;
            align-items: center;
            gap: 5px;
            font-size: .95rem;
            padding: 18px 10px 15px;
            border-radius: var(--radius);
            line-height: 1.3;
        }

        .arch-row .btn svg { width: 28px; height: 28px; }

        .arch-btn-label {
            font-size: 1rem;
            font-weight: 800;
        }

        .arch-btn-sub {
            font-size: .7rem;
            font-weight: 500;
            opacity: .80;
            white-space: nowrap;
        }

        .arch-btn-badge {
            font-size: .63rem;
            font-weight: 700;
            background: rgba(26,66,117,.1);
            color: var(--primary);
            border-radius: 50px;
            padding: 2px 9px;
            letter-spacing: .4px;
        }

        .btn-arch-64 {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
            box-shadow: 0 4px 16px rgba(26,66,117,.32);
            border: 2px solid transparent;
        }

        .btn-arch-64 .arch-btn-badge {
            background: rgba(255,255,255,.2);
            color: #fff;
        }

        .btn-arch-32 {
            background: var(--surface);
            color: var(--text-medium);
            border: 1.5px solid var(--border);
        }

        .btn-arch-32 .arch-btn-badge {
            background: var(--border);
            color: var(--text-light);
        }

        .btn-arch-32:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .btn-disabled {
            background: #f3f4f6;
            color: var(--text-muted);
            cursor: not-allowed;
            box-shadow: none;
        }

        /* ── ARCH HINT ────────────────────────────── */
        .arch-hint {
            background: var(--primary-light);
            border-radius: var(--radius-sm);
            padding: 14px 16px;
            margin-top: 16px;
        }

        .arch-hint p {
            font-size: .82rem;
            color: var(--primary-dark);
            font-weight: 600;
            line-height: 1.8;
        }

        .arch-hint p::before {
            content: '💡 ';
        }

        /* ── SECTION CARD ─────────────────────────── */
        .section-card {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 24px 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            margin-top: 16px;
        }

        .section-card .section-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 16px;
        }

        .section-card .section-title span {
            font-size: 1.25rem;
        }

        /* ── UPDATE NOTES ─────────────────────────── */
        .update-notes-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .update-notes-list li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: .92rem;
            color: var(--text-medium);
            line-height: 1.6;
        }

        .update-notes-list li::before {
            content: '';
            width: 8px;
            height: 8px;
            background: var(--primary);
            border-radius: 50%;
            flex-shrink: 0;
            margin-top: 7px;
        }

        /* ── SUPPORT CARD ─────────────────────────── */
        .support-note {
            font-size: .88rem;
            color: var(--text-medium);
            text-align: center;
            margin-bottom: 16px;
            line-height: 1.7;
        }

        .support-links {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .support-link-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background: var(--bg);
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            text-decoration: none;
            transition: background .15s;
        }

        .support-link-item:hover { background: var(--primary-light); }

        .support-link-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: var(--primary-light);
        }

        .support-link-icon svg { width: 22px; height: 22px; fill: var(--primary); }

        .support-link-text strong {
            display: block;
            font-size: .88rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 1px;
        }

        .support-link-text span {
            font-size: .78rem;
            color: var(--text-light);
            direction: ltr;
            display: block;
        }

        /* ── FOOTER ───────────────────────────────── */
        footer {
            text-align: center;
            padding: 28px 16px 32px;
            font-size: .8rem;
            color: rgba(255,255,255,.7);
            background: var(--primary-dark);
            margin-top: 24px;
        }

        footer p { margin: 0; color: #fff; opacity: 0.8; }
        footer p + p { margin-top: 4px; }

        /* ── MODAL ────────────────────────────────── */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.55);
            backdrop-filter: blur(4px);
            z-index: 1000;
            display: none;
            align-items: flex-end;
            justify-content: center;
        }

        .modal-overlay.active { display: flex; }

        .modal-sheet {
            background: var(--surface);
            border-radius: 28px 28px 0 0;
            padding: 28px 24px 36px;
            width: 100%;
            max-width: 520px;
            animation: slideUp .28s ease;
        }

        @keyframes slideUp {
            from { transform: translateY(100%); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }

        .modal-handle {
            width: 44px;
            height: 5px;
            background: var(--border);
            border-radius: 4px;
            margin: 0 auto 24px;
        }

        .modal-title {
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 6px;
        }

        .modal-subtitle {
            font-size: .85rem;
            color: var(--text-light);
            margin-bottom: 24px;
            line-height: 1.6;
        }

        .modal-options {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .modal-option {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px;
            background: var(--bg);
            border: 1.5px solid var(--border);
            border-radius: var(--radius);
            cursor: pointer;
            text-decoration: none;
            transition: border-color .15s, background .15s;
        }

        .modal-option:hover {
            border-color: var(--primary);
            background: var(--primary-light);
        }

        .modal-option-icon {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: #f3f4f6;
        }

        .modal-option-icon.icon-64 { background: var(--primary-light); }
        .modal-option-icon.icon-32 { background: #f3f4f6; }

        .modal-option-icon svg { width: 24px; height: 24px; fill: var(--primary); }

        .modal-option-text strong {
            display: block;
            font-size: .95rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        .modal-option-text span {
            font-size: .8rem;
            color: var(--text-light);
        }

        .modal-close {
            margin-top: 14px;
            text-align: center;
            font-size: .88rem;
            color: var(--text-light);
            cursor: pointer;
            padding: 8px;
        }

        .modal-close:hover { color: var(--text-dark); }

        /* ── RESPONSIVE ───────────────────────────── */
        @media (min-width: 500px) {
            .hero h1 { font-size: 2.2rem; }
            .hero .tagline { font-size: 1.05rem; }
            .btn-primary { font-size: 1.15rem; }
        }
    </style>
</head>
<body>

    <!-- ── HERO ── -->
    <header class="hero">
        <div class="hero-inner">
            <div class="app-logo" id="app-logo-wrap">
                <img src="{{asset('app_logo.png')}}"
                     alt="App Icon"
                     style="width:60px;height:60px;object-fit:contain;border-radius:12px;"
                     onerror="this.style.display='none';document.getElementById('app-logo-fallback').style.display='flex';">
                <!-- fallback SVG if image fails to load -->
                <svg id="app-logo-fallback" viewBox="0 0 24 24" fill="white"
                     style="display:none;width:52px;height:52px;">
                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/>
                </svg>
            </div>

            <h1>المندوب الذكي</h1>
            <p class="tagline">تطبيق لتسجيل طلبيات الأدوية بسهولة وسرعة للمندوبين</p>

            @if(!$fetchFailed && $latestVersion)
                <div class="version-badge">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/>
                    </svg>
                    الإصدار {{ $latestVersion }}
                </div>
            @endif
        </div>
    </header>

    <!-- ── MAIN CONTENT ── -->
    <main class="container">

        @if($fetchFailed)
            <!-- Error state -->
            <div class="error-card">
                <div class="error-icon">⚠️</div>
                <h3>تعذر تحميل بيانات التنزيل حالياً</h3>
                <p>يرجى المحاولة مرة أخرى لاحقاً أو التواصل مع الدعم الفني.</p>
            </div>

        @else
            <!-- Download card -->
            <div class="download-card">
                <p class="card-label">تحميل التطبيق</p>

                @if($primaryDownload)
                    <!-- Primary CTA -->
                    <button class="btn btn-primary" onclick="openDownloadModal()">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 9h-4V3H9v6H5l7 7 7-7zm-8 2V5h2v6h1.17L12 13.17 9.83 11H11zm-6 7h14v2H5v-2z"/>
                        </svg>
                        تحميل التطبيق
                    </button>

                    <!-- Architecture buttons -->
                    <div class="arch-row">
                        @if($download64)
                            <a href="{{ $download64 }}" class="btn btn-arch-64">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M19 9h-4V3H9v6H5l7 7 7-7zm-8 2V5h2v6h1.17L12 13.17 9.83 11H11zm-6 7h14v2H5v-2z"/>
                                </svg>
                                <span class="arch-btn-label">64-bit</span>
                                <span class="arch-btn-sub">أجهزة 2018 وأحدث</span>
                                <span class="arch-btn-badge">⭐ موصى بها</span>
                            </a>
                        @endif

                        @if($download32)
                            <a href="{{ $download32 }}" class="btn btn-arch-32">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M19 9h-4V3H9v6H5l7 7 7-7zm-8 2V5h2v6h1.17L12 13.17 9.83 11H11zm-6 7h14v2H5v-2z"/>
                                </svg>
                                <span class="arch-btn-label">32-bit</span>
                                <span class="arch-btn-sub">أجهزة أقدم</span>
                                <span class="arch-btn-badge">للضرورة</span>
                            </a>
                        @endif
                    </div>

                @else
                    <div class="btn btn-disabled">
                        روابط التحميل غير متاحة حالياً
                    </div>
                @endif

                <!-- Architecture hint -->
                <div class="arch-hint">
                    <p>إذا كان جهازك حديثاً (2018 أو أحدث) اختر نسخة 64-bit</p>
                    <p style="margin-top:4px">إذا لم تكن متأكداً استخدم زر "تحميل التطبيق" الرئيسي</p>
                </div>
            </div>

            <!-- Update notes -->
            @if(!empty($updateNotes))
                <div class="section-card">
                    <div class="section-title">
                        <span>✨</span>
                        آخر التحسينات
                    </div>
                    <ul class="update-notes-list">
                        @foreach($updateNotes as $note)
                            <li>{{ $note }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endif

        <!-- Support card (always shown) -->
        <div class="section-card">
            <div class="section-title">
                <span>🎧</span>
                الدعم الفني
            </div>
            <p class="support-note">
                إذا واجهت مشكلة في التنزيل أو التثبيت، تواصل معنا وسنكون سعداء بمساعدتك.
            </p>
            <div class="support-links">
                <a href="mailto:smart.agent.app.support@gmail.com" class="support-link-item">
                    <div class="support-link-icon email">
                        <svg viewBox="0 0 24 24">
                            <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                        </svg>
                    </div>
                    <div class="support-link-text">
                        <strong>البريد الإلكتروني</strong>
                        <span>smart.agent.app.support@gmail.com</span>
                    </div>
                </a>

                <a href="https://wa.me/963959027196" class="support-link-item" target="_blank">
                    <div class="support-link-icon whatsapp">
                        <svg viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/>
                        </svg>
                    </div>
                    <div class="support-link-text">
                        <strong>واتساب</strong>
                        <span>963959027196+</span>
                    </div>
                </a>

                <a href="https://t.me/+963959027196" class="support-link-item" target="_blank">
                    <div class="support-link-icon telegram">
                        <svg viewBox="0 0 24 24">
                            <path d="M9.78 18.65l.28-4.23 7.68-6.92c.34-.31-.07-.46-.52-.19L7.74 13.3 3.64 12c-.88-.25-.89-.86.2-1.3l15.97-6.16c.73-.33 1.43.18 1.15 1.3l-2.72 12.81c-.19.91-.74 1.13-1.5.71L12.6 16.3l-1.99 1.93c-.23.23-.42.42-.83.42z"/>
                        </svg>
                    </div>
                    <div class="support-link-text">
                        <strong>تيليغرام</strong>
                        <span>حساب الدعم الفني</span>
                    </div>
                </a>
            </div>
        </div>

    </main>

    <!-- ── FOOTER ── -->
    <footer>
        <p>© {{ date('Y') }} تطبيق المندوب الذكي &mdash; جميع الحقوق محفوظة</p>
        @if(!$fetchFailed && $latestVersion)
            <p>الإصدار الحالي: {{ $latestVersion }}</p>
        @endif
    </footer>

    <!-- ── DOWNLOAD MODAL ── -->
    @if(!$fetchFailed && $primaryDownload)
    <div class="modal-overlay" id="downloadModal" onclick="handleOverlayClick(event)">
        <div class="modal-sheet">
            <div class="modal-handle"></div>
            <div class="modal-title">اختر نسخة التطبيق</div>
            <div class="modal-subtitle">
                اختر النسخة المناسبة لجهازك. معظم الأجهزة الحديثة تدعم نسخة 64-bit.
            </div>
            <div class="modal-options">

                @if($download64)
                <a href="{{ $download64 }}" class="modal-option">
                    <div class="modal-option-icon icon-64">
                        <svg viewBox="0 0 24 24" fill="#1a56db">
                            <path d="M19 9h-4V3H9v6H5l7 7 7-7zm-8 2V5h2v6h1.17L12 13.17 9.83 11H11zm-6 7h14v2H5v-2z"/>
                        </svg>
                    </div>
                    <div class="modal-option-text">
                        <strong>نسخة 64-bit &nbsp;⭐ موصى بها</strong>
                        <span>للأجهزة الحديثة (2018 وما بعد)</span>
                    </div>
                </a>
                @endif

                @if($download32)
                <a href="{{ $download32 }}" class="modal-option">
                    <div class="modal-option-icon icon-32">
                        <svg viewBox="0 0 24 24" fill="#6b7280">
                            <path d="M19 9h-4V3H9v6H5l7 7 7-7zm-8 2V5h2v6h1.17L12 13.17 9.83 11H11zm-6 7h14v2H5v-2z"/>
                        </svg>
                    </div>
                    <div class="modal-option-text">
                        <strong>نسخة 32-bit</strong>
                        <span>للأجهزة القديمة أو إذا لم تكن متأكداً</span>
                    </div>
                </a>
                @endif

            </div>
            <div class="modal-close" onclick="closeDownloadModal()">إغلاق</div>
        </div>
    </div>
    @endif

    <script>
        function openDownloadModal() {
            const modal = document.getElementById('downloadModal');
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeDownloadModal() {
            const modal = document.getElementById('downloadModal');
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        }

        function handleOverlayClick(e) {
            if (e.target === e.currentTarget) {
                closeDownloadModal();
            }
        }

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeDownloadModal();
        });
    </script>

</body>
</html>

