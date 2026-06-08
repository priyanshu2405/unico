<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Unicou Menu</title>
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <style>
        .user-profile-dropdown {
            position: relative;
            display: inline-block;
            cursor: pointer;
        }

        .user-info-wrapper {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 5px;
            border-radius: 8px;
            transition: background 0.2s;
        }

        .user-info-wrapper:hover {
            background: rgba(0, 0, 0, 0.05);
        }

        .user-avatar img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #2daae1;
        }

        .user-details {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
            text-align: left;
        }

        .user-name {
            font-weight: 700;
            color: #333;
            font-size: 0.95rem;
        }

        .user-id {
            font-size: 0.8rem;
            color: #666;
            font-weight: 500;
        }

        .profile-dropdown-menu {
            position: absolute;
            top: 80px;
            left: 100%;
            background: #fff;
            /* min-width: 200px; */
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            padding: 10px 10px;
            /* margin-top: 10px; */
            display: none;
            z-index: 1000;
            border: 1px solid #eee;
            transform: translate(-50%, -50%);
        }

        .profile-dropdown-menu.show {
            display: flex;
            /* animation: fadeInDown 0.3s ease; */
            gap: 10px;
        }

        div#profileDropdownMenu .dropdown-item {
            white-space: nowrap;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            padding: 10px 20px;
            color: #444;
            text-decoration: none;
            font-size: 0.9rem;
            transition: background 0.2s;
            font-weight: 500;
        }

        .dropdown-item:hover {
            background: #f8f9fa;
            color: #2daae1;
        }

        .dropdown-divider {
            height: 1px;
            background: #eee;
            margin: 8px 0;
        }

        .footer-col.accordion a {
            color: #fff;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <!-- PAGE LOADER -->
    <div id="pageLoader">
        <div class="loader-content">
            <div class="loader-logo">
                <img src="{{asset('images/company_logo.png')}}" alt="unicou logo">
            </div>
            <p class="loader-text">
                Global Learning • Digital Education • Career Growth
            </p>

            <div class="loader-dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </div>


    <div class="floatingNav scroll">
        <div class="listItem Sec1 active" data-target="Sec1"><span>Home</span></div>
        <div class="listItem Sec2" data-target="Sec2"><span>GLOBAL<br>AUDIENCE InUni</span></div>
        <div class="listItem Sec3" data-target="Sec3"><span>STUDY AT <br> UNICOU</span></div>
        <div class="listItem Sec4" data-target="Sec4"><span> INTERNATIONAL <br> AWARDING</span></div>
        <div class="listItem Sec5" data-target="Sec5"><span>LEARNING <br> SUPPORT</span></div>
        <div class="listItem Sec6" data-target="Sec6"><span>TECHNOLOGY <br> DRIVEN</span></div>
        <div class="listItem Sec7" data-target="Sec7"><span>UNICOU <br> LEARNING</span></div>
        <div class="listItem Sec8" data-target="Sec8"><span>Global <br> Education</span></div>
        <div class="listItem Sec9" data-target="Sec9"><span>Start <br> Learning</span></div>
    </div>

    <header class="site-header">
        <div class="logo">
            <img src="{{asset('images/company_logo.png')}}" alt="unicou logo">
        </div>

        <!-- Hamburger -->
        <div class="hamburger" id="openMenu">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </header>

    <!-- Overlay -->
    <div class="menu-overlay" id="menuOverlay"></div>

    <!-- Slide Menu -->
    <div class="side-menu" id="sideMenu">
        <div class="menu-header">
            <span class="close-btn" id="closeMenu">&times;</span>
        </div>
        @auth
        @php
        $connectRoute = match(auth()->user()->account_type) {
        'admin' => route('dashboard'),
        'reseller_agent' => route('auth.forms.B2BResellerAgent'),
        'student' => route('auth.form.student'),
        default => route('home'),
        };
        @endphp


        @endauth
        <div class="menu-footer">
            <div class="btn-group login-reg">

                @auth
                {{-- Logged in user dropdown --}}
                <div class="user-profile-dropdown">

                    <div class="user-info-wrapper" id="userDropdownTrigger">
                        <div class="user-avatar">
                            <img src="{{ auth()->user()->profile_photo 
                        ? asset('storage/' . auth()->user()->profile_photo) 
                        : asset('images/user.png') }}" alt="User">
                        </div>

                        <div class="user-details">
                            <div class="user-name">
                                {{ auth()->user()->first_name ?? auth()->user()->name }}
                                <i class="fas fa-chevron-down ms-1" style="font-size: 0.7rem; color: #999;"></i>
                            </div>

                            <div class="user-id">
                                {{ auth()->user()->user_id }}
                            </div>

                            <div class="user-type badge bg-primary-subtle text-primary"
                                style="font-size: 0.6rem; padding: 2px 6px;">
                                {{ ucfirst(str_replace('_', ' ', auth()->user()->account_type)) }}
                            </div>
                        </div>
                    </div>

                    {{-- Dropdown Menu --}}
                    <div class="profile-dropdown-menu" id="profileDropdownMenu">

                        
                        @if(auth()->user()->account_type !== 'admin')
                        <a href="{{ $connectRoute }}" class="dropdown-item">
                            <i class="fas fa-user-cog me-2"></i> Fill Form
                        </a>
                        @endif

                        <div class="dropdown-divider"></div>

                        <a href="{{ route('auth.logout') }}"
                            class="dropdown-item text-danger"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>

                    </div>

                </div>

                {{-- Logout Form --}}
                <form id="logout-form" action="{{ route('auth.logout') }}" method="POST" class="d-none">
                    @csrf
                </form>

                @endauth


                @guest
                {{-- Guest --}}
                <a href="{{ route('login') }}" class="btn_login">Login</a>
                <a href="{{ route('register') }}" class="btn_regis">Registration</a>
                @endguest

            </div>
        </div>


        <ul class="menu-links">
             @auth
            <li class="nav-item">
                <a href="{{ route('dashboard') }}">
                    Dashboard
                </a>
            </li>
            @endauth
            <li><a href="#">ABOUT</a></li>
            <li><a href="#">STUDY ABROAD</a></li>
            <li><a href="#">EXAMS</a></li>
            <li><a href="#">LEARNING HUB</a></li>
            <li><a href="#">BLOGS</a></li>
            <li><a href="#">CONNECT</a></li>
        </ul>
    </div>


    <section class="hero-section padding_top_bot Sec1" id="Sec1" style="background-image: url('{{ asset('images/bgLeftTop.png') }}');">
        <div id="particles-bg"></div>

        <div class="container">
            <div class="hero-container">

                <!-- LEFT CONTENT -->
                <div class="hero-content">

                    <span class="hero-top-text">
                        Official English & Academic Test Vouchers – Secure, Global & Discounted
                    </span>

                    <h1>
                        UniCou Exam <span>Vouchers</span>
                    </h1>

                    <div class="hero-text-block">
                        <p>
                            Welcome to <strong>UniCou Exam Vouchers</strong>, the official digital voucher platform of <strong>UniCou Ltd.</strong> We provide <strong>secure, verified, and discounted exam vouchers </strong> for internationally recognised English language and academic tests, helping students and professionals book the right exam with confidence.
                        </p>
                    </div>

                    <div class="hero-text-block">
                        <p>
                            Built as part of UniCou’s <strong>education technology ecosystem,</strong> our voucher platform connects learners directly to trusted testing providers, eliminating confusion, delays, and unnecessary costs in the exam booking process.
                        </p>
                    </div>

                    <a href="#" class="hero-btn">
                        INITIALIZE SYNC NODE →
                    </a>

                </div>

                <!-- RIGHT IMAGE -->
                <div class="hero-media">
                    <img src="{{ asset('images/hero_Banner_img (1).png') }}" alt="Learning Hub Graphic">
                </div>

            </div>
        </div>
    </section>

    <section class="learning-platform padding_top_bot Sec2" id="Sec2" style="background-image: url('{{ asset('images/learning-platform.png') }}');">
        <div class="container">
            <div class="lp-container">

                <!-- LEFT CONTENT -->
                <div class="lp-content">
                    <h2>
                        A Trusted Global Platform for<br>
                        <span>Exam Vouchers</span>
                    </h2>

                    <div class="lp-divider"></div>

                    <p>
                        Booking an international exam should be simple, transparent, and secure. UniCou Exam Vouchers was created to solve common challenges faced by candidates worldwide, including:
                    </p>

                    <ul class="ul_cls_learning-platform">
                        <li>High exam booking costs</li>
                        <li>Limited local access to official test centres</li>
                        <li>Confusion over exam acceptance and score requirements</li>
                        <li>Risk of unofficial or fraudulent booking channels</li>
                    </ul>

                    <p>
                        Our platform ensures that every voucher is <strong>official, verified, and issued through recognised exam partners,</strong> giving candidates peace of mind and clarity.
                    </p>
                </div>

                <!-- RIGHT IMAGE -->
                <div class="lp-image">
                    <img src="{{ asset('images/Untitled design.png') }}" alt="Global Network">
                    <!-- <video autoplay="" muted="" loop="">
        <source src="./Untitled design (7).mp4" autoplay="" muted="" loop="" type="video/mp4"></video> -->
                </div>

            </div>
        </div>
    </section>

    <section class="awarding-section exa_UniCou_Vouchers  padding_top_bot Sec4" id="Sec4">
        <div class="container">
            <div class="awarding-card">
                <h2>Exams Available on UniCou Vouchers</h2>
                <p>
                    We offer <strong>vouchers for a wide range of globally</strong>
                    accepted English language and academic exams, including:
                </p>
                <div class="main_exa_UniCou_Vouchers">
                    <div class="exa_UniCou_Vouchers_box_one">
                        <p class="sub-title"><strong>English Language Tests</strong></p>
                        <ul class="list">
                            <li>IELTS</li>
                            <li>PTE Academic</li>
                            <li>TOEFL iBT</li>
                            <li>LanguageCert International ESOL</li>
                            <li>Skills for English</li>
                            <li>Duolingo English Test</li>
                            <li>Oxford ELLT</li>
                        </ul>
                    </div>
                    <div class="exa_UniCou_Vouchers_box_two">
                        <p class="sub-title"><strong>Academic & Admission Tests</strong></p>

                        <ul class="list">
                            <li>GRE</li>
                            <li>SAT</li>
                            <li>GMAT</li>
                        </ul>

                        <p>
                            All vouchers are issued in line with official testing policies and are suitable for <strong>study abroad, migration, professional registration, and academic admissions,</strong> subject to institutional acceptance.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="study-section padding_top_bot Sec3" id="Sec3">
        <div class="container">

            <!-- SECTION TITLE -->
            <div class="section-head">
                <h2>Why Buy Exam Vouchers from UniCou?</h2>
                <!-- <p>We offer vouchers for a wide range of globally accepted English language and academic exams, including:</p> -->
            </div>

            <!-- CARDS -->
            <div class="study-grid">

                <!-- CARD 1 -->
                <div class="study-card">
                    <h3>
                        Official & Verified Vouchers
                    </h3>
                    <span class="card-line"></span>

                    <p>
                        All vouchers provided through our platform are
                        <strong>official and authentic, </strong>
                        linked to recognised testing bodies and approved centres.
                    </p>
                </div>

                <!-- CARD 2 -->
                <div class="study-card">
                    <h3>
                        Discounted Pricing
                    </h3>
                    <span class="card-line"></span>

                    <p>
                        We offer <strong>competitive and discounted exam vouchers,</strong> helping students reduce overall application and exam costs without compromising security.
                    </p>
                </div>

                <!-- CARD 3 -->
                <div class="study-card">
                    <h3>
                        Global Accessibility
                    </h3>
                    <span class="card-line"></span>

                    <p>
                        Candidates from <strong>Asia, Africa, Europe, the Middle East, and beyond</strong> can purchase vouchers online without relying on local agents or limited test centre availability.
                    </p>
                </div>

                <!-- CARD 4 -->
                <div class="study-card">
                    <h3>
                        Guidance Before You Buy
                    </h3>
                    <span class="card-line"></span>

                    <p>
                        We help candidates confirm:
                    </p>

                    <ul>
                        <li>Which exam is accepted by their university or authority</li>
                        <li>Required score levels</li>
                        <li>Whether an alternative exam may be more suitable</li>
                    </ul>

                    <p>
                        This reduces the risk of booking the wrong test.
                    </p>
                </div>

            </div>
        </div>
    </section>

    <section class="tech-section padding_top_bot Sec6" id="Sec6">
        <div class="container">
            <h1>Secure Digital Voucher System</h1>
            <p class="subtitle">
                UniCou Exam Vouchers operates on a <strong>secure digital delivery system,</strong> ensuring:
            </p>

            <div class="timeline-wrapper">
                <div class="timeline-path"></div>

                <div class="step step-1">
                    <span>01</span>
                    <p>Verified voucher issuance</p>
                </div>

                <div class="step step-2">
                    <span>02</span>
                    <p>Controlled redemption processes</p>
                </div>

                <div class="step step-3">
                    <span>03</span>
                    <p>Clear usage instructions</p>
                </div>

                <div class="step step-4">
                    <span>04</span>
                    <p>Compliance with testing body requirements</p>
                </div>
            </div>

            <p class="footer-text">
                Our platform is designed to support <strong>individual candidates, education agents, institutions, and preparation centres.</strong>
            </p>
        </div>
    </section>

    <section class="Desi_Stu_Age_Ins padding_top_bot Sec4" id="Sec4">
        <div class="container">
            <div class="awarding-card" style="background-image: url('{{ asset('images/planeImg.png') }}');">

                <h2>
                    Designed for Students, Agents & Institutions
                </h2>

                <h4>For Students</h4>

                <p>
                    Students can purchase exam vouchers directly, saving time and cost while ensuring they <br>
                    book the correct exam for their study abroad or migration goals.
                </p>

                <p class="sub-title"><strong>For Education Agents & Preparation Centres</strong></p>
                <p>Our platform supports <strong>agent accounts and bulk voucher access,</strong>, enabling:</p>
                <ul class="list">
                    <li>Faster student service</li>
                    <li>Centralised voucher management</li>
                    <li>Transparent pricing and tracking</li>
                </ul>

                <p class="sub-title"><strong>For Institutions & Partners</strong></p>
                <p>Universities, colleges, and training providers can work with UniCou to support students with<br> <strong>secure exam access and preparation pathways.</strong></p>
            </div>
        </div>
    </section>

    <section class="who-section padding_top_bot Sec7" id="Sec7">
        <div class="container">
            <div class="who-container">

                <!-- LEFT CONTENT BOX -->
                <div class="who-content cls_inte_unicou">
                    <h2>Integrated with UniCou Learning & Study Abroad Services</h2>
                    <p class="intro">UniCou Exam Vouchers is part of the wider <strong>UniCou global education ecosystem,</strong> which includes:</p>

                    <ul>
                        <li>Study abroad and university admissions support</li>
                        <li>Learning Hub (LMS) for academic and professional courses</li>
                        <li>Exam preparation and e-learning resources</li>
                        <li>Immigration and global mobility services</li>
                    </ul>

                    <p>This integration ensures candidates can move smoothly from <strong>learning → exam → admission → progression.</strong></p>
                </div>

                <!-- RIGHT IMAGE -->
                <div class="who-image">
                    <img src="{{ asset('images/How-we-partner.png') }}" alt="Professional Meeting">
                </div>

            </div>
        </div>
    </section>

    <section class="section-wrapper padding_top_bot Sec8" id="Sec8">
        <div class="container">
            <div class="cards">

                <!-- Left Card -->
                <div class="card card-dark">
                    <h2>Trusted by Global Partners</h2>
                    <p>
                        UniCou Ltd operates with strong professional credentials and industry recognition, including:
                    </p>
                    <ul>
                        <li>British Council and ICEF-trained counselors</li>
                        <li>UKVI and Pearson-approved test centre operations</li>
                        <li>Partnerships with international testing and awarding bodies</li>
                    </ul>
                    <p>
                        These foundations ensure our voucher platform operates with <strong>integrity, compliance, and long-term reliability.</strong>
                    </p>
                </div>

                <!-- Right Card -->
                <div class="card card-light">
                    <h2>Who Should Use UniCou Exam Vouchers?</h2>
                    <p>
                        UniCou Exam Vouchers is ideal for:
                    </p>
                    <ul>
                        <li>Students applying for <strong>international education</strong></li>
                        <li>Candidates preparing for <strong>English language exams</strong></li>
                        <li>Professionals needing test results for migration or registration</li>
                        <li>Education agents managing multiple student bookings</li>
                        <li>Preparation centres and institutions</li>
                    </ul>
                    <p>Whether you are booking a single exam or managing vouchers at scale, our platform adapts to your needs.</p>
                </div>

            </div>
        </div>
    </section>

    <section class="Our_Commitment padding_top_bot Sec9" id="Sec9">
        <div class="container">
            <div class="cta-card crd_Our_Commitment_one cls_com_crd_Our_Com">
                <h2>Our Commitment</h2>
                <p>We are committed to:</p>
                <ul>
                    <li>Transparency in pricing and processes</li>
                    <li>Secure and compliant voucher issuance</li>
                    <li>Accurate guidance before booking</li>
                    <li>Long-term support beyond the exam</li>
                </ul>
                <p>We do not simply sell vouchers — we help candidates<strong>make the right exam choice.</strong></p>
            </div>

            <div class="cta-card crd_Our_Commitment_two cls_com_crd_Our_Com">
                <h2>Start Your Exam Journey with Confidence</h2>
                <p>Choosing the correct exam is a critical step in your academic or professional journey. UniCou Exam Vouchers makes that step simpler, safer, and more affordable.</p>
                <ul>
                    <li>Official vouchers</li>
                    <li>Discounted pricin</li>
                    <li>Global access</li>
                    <li>Trusted guidance</li>
                </ul>
                <p>Buy your exam voucher today and move one step closer to your global goals.</p>
            </div>
        </div>
    </section>
    <footer class="footer padding_top_bot">
        <div class="container">
            <div class="footer-container">

                <!-- Logo & About -->
                <div class="footer-col brand">
                    <h2 class="logo">
                        <img src="http://127.0.0.1:8000/images/company_logo.png" alt="">
                    </h2>
                    <p>
                        Secure your University Admission. Master your Exams with our LMS, and
                        save on Official Vouchers for IELTS, PTE, TOEFL, LanguageCert, Duolingo,
                        GRE and more.
                    </p>

                    <h4>Follow Us</h4>
                    <div class="socials">
                        <a href="#">
                            <svg width="64px" height="64px" viewBox="38.657999999999994 12.828 207.085 207.085" xmlns="http://www.w3.org/2000/svg" fill="#000000">
                                <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                <g id="SVGRepo_iconCarrier">
                                    <path d="M158.232 219.912v-94.461h31.707l4.747-36.813h-36.454V65.134c0-10.658 2.96-17.922 18.245-17.922l19.494-.009V14.278c-3.373-.447-14.944-1.449-28.406-1.449-28.106 0-47.348 17.155-47.348 48.661v27.149H88.428v36.813h31.788v94.461l38.016-.001z" fill="#3c5a9a"></path>
                                </g>
                            </svg>
                        </a>
                        <a href="#">
                            <svg width="64px" height="64px" viewBox="0 -4 48 48" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" fill="#000000">
                                <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                <g id="SVGRepo_iconCarrier">
                                    <title>Twitter-color</title>
                                    <desc>Created with Sketch.</desc>
                                    <defs> </defs>
                                    <g id="Icons" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                        <g id="Color-" transform="translate(-300.000000, -164.000000)" fill="#00AAEC">
                                            <path d="M348,168.735283 C346.236309,169.538462 344.337383,170.081618 342.345483,170.324305 C344.379644,169.076201 345.940482,167.097147 346.675823,164.739617 C344.771263,165.895269 342.666667,166.736006 340.418384,167.18671 C338.626519,165.224991 336.065504,164 333.231203,164 C327.796443,164 323.387216,168.521488 323.387216,174.097508 C323.387216,174.88913 323.471738,175.657638 323.640782,176.397255 C315.456242,175.975442 308.201444,171.959552 303.341433,165.843265 C302.493397,167.339834 302.008804,169.076201 302.008804,170.925244 C302.008804,174.426869 303.747139,177.518238 306.389857,179.329722 C304.778306,179.280607 303.256911,178.821235 301.9271,178.070061 L301.9271,178.194294 C301.9271,183.08848 305.322064,187.17082 309.8299,188.095341 C309.004402,188.33225 308.133826,188.450704 307.235077,188.450704 C306.601162,188.450704 305.981335,188.390033 305.381229,188.271578 C306.634971,192.28169 310.269414,195.2026 314.580032,195.280607 C311.210424,197.99061 306.961789,199.605634 302.349709,199.605634 C301.555203,199.605634 300.769149,199.559408 300,199.466956 C304.358514,202.327194 309.53689,204 315.095615,204 C333.211481,204 343.114633,188.615385 343.114633,175.270495 C343.114633,174.831347 343.106181,174.392199 343.089276,173.961719 C345.013559,172.537378 346.684275,170.760563 348,168.735283" id="Twitter"> </path>
                                        </g>
                                    </g>
                                </g>
                            </svg>
                        </a>
                        <a href="#">
                            <svg width="64px" height="64px" viewBox="0 -2 44 44" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" fill="#000000">
                                <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                <g id="SVGRepo_iconCarrier">
                                    <title>LinkedIn-color</title>
                                    <desc>Created with Sketch.</desc>
                                    <defs> </defs>
                                    <g id="Icons" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                        <g id="Color-" transform="translate(-702.000000, -265.000000)" fill="#007EBB">
                                            <path d="M746,305 L736.2754,305 L736.2754,290.9384 C736.2754,287.257796 734.754233,284.74515 731.409219,284.74515 C728.850659,284.74515 727.427799,286.440738 726.765522,288.074854 C726.517168,288.661395 726.555974,289.478453 726.555974,290.295511 L726.555974,305 L716.921919,305 C716.921919,305 717.046096,280.091247 716.921919,277.827047 L726.555974,277.827047 L726.555974,282.091631 C727.125118,280.226996 730.203669,277.565794 735.116416,277.565794 C741.21143,277.565794 746,281.474355 746,289.890824 L746,305 L746,305 Z M707.17921,274.428187 L707.117121,274.428187 C704.0127,274.428187 702,272.350964 702,269.717936 C702,267.033681 704.072201,265 707.238711,265 C710.402634,265 712.348071,267.028559 712.41016,269.710252 C712.41016,272.34328 710.402634,274.428187 707.17921,274.428187 L707.17921,274.428187 L707.17921,274.428187 Z M703.109831,277.827047 L711.685795,277.827047 L711.685795,305 L703.109831,305 L703.109831,277.827047 L703.109831,277.827047 Z" id="LinkedIn"> </path>
                                        </g>
                                    </g>
                                </g>
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Accordion Column -->
                <div class="footer-col accordion">
                    <button class="accordion-header">Verticals</button>
                    <ul class="accordion-body">
                        <li>Exam Vouchers</li>
                        <li>LMS</li>
                        <li>Admission Hub</li>
                        <li>About Us</li>
                        <li>Apply Now</li>
                        <li>
                            <a href="{{ route('auth.form.support') }}">Career</a>
                        </li>
                    </ul>
                </div>

                <div class="footer-col accordion">
                    <button class="accordion-header">Legal & Policies</button>
                    <ul class="accordion-body">
                        <li>Privacy Policy</li>
                        <li>Modern Slavery</li>
                        <li>Accessibility</li>
                        <li>Cookie Use Policy</li>
                        <li>Whistleblowing Policy</li>
                        <li>Carbon Reduction Plan</li>
                        <li>Website Terms of Use</li>
                    </ul>
                </div>

                <div class="footer-col accordion">
                    <button class="accordion-header">Contact Info</button>
                    <ul class="accordion-body">
                        <li>connect@unicou.uk</li>
                        <li>UK: Chepstow Avenue, Manchester M33 4QP</li>
                        <li>Dubai: 24695 Deira, UAE</li>
                        <li>Pakistan: Township, Lahore</li>
                    </ul>
                </div>

            </div>

            <div class="footer-bottom">
                © 2026 UNICOU All rights reserved. Designed and Developed by Veva Technology
            </div>
        </div>
    </footer>
    <script src="{{ asset('js/homeScript.js') }}"></script>
    <script src="{{ asset('js/homeParticles.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const trigger = document.getElementById('userDropdownTrigger');
            const menu = document.getElementById('profileDropdownMenu');

            if (trigger && menu) {
                trigger.addEventListener('click', function(e) {
                    e.stopPropagation();
                    menu.classList.toggle('show');
                });

                document.addEventListener('click', function(e) {
                    if (!trigger.contains(e.target)) {
                        menu.classList.remove('show');
                    }
                });
            }
        });
    </script>
</body>

</html>