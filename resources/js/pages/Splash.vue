<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Search,
    Brain,
    Sparkles,
    Target,
    BookOpen,
    TrendingUp,
    Users,
    CheckCircle,
    Zap,
    Shield,
    MessageCircle,
    Star,
    GraduationCap,
    BarChart3,
} from 'lucide-vue-next';
import { dashboard, login, home } from '@/routes';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const nextRoute = computed(() => (user.value ? dashboard() : login()));
</script>

<template>
    <Head title="EduNexus - AI-Powered Learning Resources">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div
        class="relative flex min-h-screen flex-col overflow-hidden bg-gray-50 text-gray-900"
    >
        <!-- Header Navigation -->
        <header
            class="sticky top-0 z-50 w-full border-b border-gray-200 bg-white/80 backdrop-blur-sm animate-slide-down"
        >
            <nav
                class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8"
            >
                <Link
                    :href="home()"
                    class="flex items-center gap-3 transition-all duration-300 hover:opacity-80 hover:scale-105"
                >
                    <img
                        src="/images/logo/logo.png"
                        alt="EduNexus JPENHS Logo"
                        class="h-8 w-auto sm:h-10 lg:h-12 animate-fade-in"
                    />
                    <div class="flex flex-col">
                        <span
                            class="text-xl font-bold text-gray-800 sm:text-2xl animate-fade-in-delay"
                        >
                            EduNexus
                        </span>
                        <span class="text-xs text-gray-500 sm:text-sm">
                            JPENHS
                        </span>
                    </div>
                </Link>
                <div class="flex items-center gap-4">
                    <Link
                        v-if="!user"
                        :href="login()"
                        class="text-sm font-medium text-gray-700 hover:text-gray-900"
                    >
                        Sign In
                    </Link>
                    <Link
                        :href="nextRoute"
                        class="rounded-lg bg-gradient-to-r from-[#5B21B6] to-[#7C3AED] px-4 py-2 text-sm font-semibold text-white shadow-md transition-all hover:from-[#6D28D9] hover:to-[#8B5CF6] hover:shadow-lg"
                    >
                        Get Started
                    </Link>
                </div>
            </nav>
        </header>

        <!-- Hero Section -->
        <section
            class="relative flex min-h-[85vh] flex-col items-center justify-center overflow-hidden px-4 py-12 sm:px-6 sm:py-16 lg:px-8 lg:py-20"
        >
            <!-- Background Blobs -->
            <div
                class="absolute left-1/2 top-1/4 -z-10 h-96 w-96 -translate-x-1/2 rounded-full bg-purple-400 opacity-20 blur-3xl animate-pulse-slow sm:h-[32rem] sm:w-[32rem] lg:h-[40rem] lg:w-[40rem]"
            ></div>
            <div
                class="absolute right-1/4 top-1/2 -z-10 h-64 w-64 rounded-full bg-blue-400 opacity-15 blur-3xl animate-float sm:h-80 sm:w-80 lg:h-96 lg:w-96"
            ></div>
            <div
                class="absolute left-1/4 bottom-1/4 -z-10 h-48 w-48 rounded-full bg-pink-400 opacity-10 blur-3xl animate-float-delayed sm:h-64 sm:w-64"
            ></div>

            <div class="relative z-10 mx-auto w-full max-w-6xl">
                <!-- Main Hero Content -->
                <div
                    class="mb-12 text-center sm:mb-16 lg:mb-20"
                >
                    <!-- AI Badge -->
                    <div
                        class="mb-6 inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-purple-100 to-blue-100 px-4 py-2 text-sm font-semibold text-purple-700 sm:text-base animate-fade-in-up shadow-sm"
                    >
                        <Brain class="h-4 w-4 sm:h-5 sm:w-5" />
                        <span>AI-Powered Technology</span>
                    </div>

                    <!-- Main Headline -->
                    <h1
                        class="mb-6 text-4xl font-extrabold leading-tight tracking-tight sm:mb-8 sm:text-5xl lg:mb-10 lg:text-7xl"
                    >
                        <span class="block text-gray-900 animate-fade-in-up-delay-1"
                            >Transform Learning with</span
                        >
                        <span
                            class="block bg-gradient-to-r from-[#5B21B6] via-[#7C3AED] to-[#A855F7] bg-clip-text text-transparent animate-fade-in-up-delay-2"
                        >
                            AI-Powered Intelligence
                        </span>
                        <span class="block text-gray-900 animate-fade-in-up-delay-3"
                            >Personalized for Every Student</span
                        >
                    </h1>

                    <!-- Subheadline -->
                    <p
                        class="mx-auto mb-8 max-w-3xl text-lg leading-relaxed text-gray-600 sm:mb-10 sm:text-xl lg:mb-12 lg:text-2xl animate-fade-in-up-delay-4"
                    >
                        EduNexus JPENHS leverages cutting-edge AI technology to deliver personalized learning experiences. 
                        Discover content tailored to your learning style, track your progress, and achieve your educational goals faster.
                    </p>

                    <!-- CTA Buttons -->
                    <div
                        class="flex flex-col items-center justify-center gap-4 sm:flex-row animate-fade-in-up-delay-5"
                    >
                        <Link
                            :href="nextRoute"
                            class="group flex items-center gap-2 rounded-xl bg-gradient-to-r from-[#5B21B6] to-[#7C3AED] px-8 py-4 text-base font-semibold text-white shadow-xl shadow-purple-500/25 transition-all duration-300 hover:from-[#6D28D9] hover:to-[#8B5CF6] hover:shadow-2xl hover:shadow-purple-500/40 hover:scale-105 active:scale-95 sm:text-lg"
                        >
                            <span>Get Started Free</span>
                            <ArrowRight
                                class="h-5 w-5 transition-transform duration-300 group-hover:translate-x-2"
                            />
                        </Link>
                        <Link
                            :href="login()"
                            v-if="!user"
                            class="rounded-xl border-2 border-gray-300 bg-white px-8 py-4 text-base font-semibold text-gray-700 transition-all duration-300 hover:border-[#5B21B6] hover:bg-gray-50 hover:scale-105 hover:text-[#5B21B6] sm:text-lg"
                        >
                            Sign In
                        </Link>
                    </div>

                    <!-- Trust Indicators -->
                    <div class="mt-12 flex flex-wrap items-center justify-center gap-8 text-sm text-gray-500 sm:mt-16 lg:mt-20">
                        <div class="flex items-center gap-2">
                            <CheckCircle class="h-5 w-5 text-green-500" />
                            <span>Free to Use</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <Shield class="h-5 w-5 text-blue-500" />
                            <span>Secure & Private</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <Zap class="h-5 w-5 text-yellow-500" />
                            <span>AI-Powered</span>
                        </div>
                    </div>
                </div>

                <!-- Illustration Section -->
                <div
                    class="relative mx-auto w-full max-w-2xl animate-fade-in-delay-6"
                >
                    <div class="relative flex items-center justify-center animate-float-slow">
                        <!-- Smartphone -->
                        <div
                            class="relative z-10 h-56 w-32 rotate-[-3deg] rounded-3xl border-[3px] border-gray-800 bg-white shadow-2xl transition-transform duration-500 hover:rotate-0 hover:scale-105 sm:h-72 sm:w-40 lg:h-96 lg:w-48"
                        >
                            <!-- Phone Screen Content -->
                            <div class="flex h-full flex-col p-3 sm:p-4 lg:p-5">
                                <!-- Top lines (longer, darker) -->
                                <div
                                    class="mb-2.5 h-2.5 rounded bg-gray-800 sm:mb-3 sm:h-3 lg:mb-4 lg:h-3.5"
                                ></div>
                                <div
                                    class="mb-3 h-2.5 rounded bg-gray-800 sm:mb-4 sm:h-3 lg:mb-5 lg:h-3.5"
                                ></div>
                                <!-- Shorter lines (lighter gray) -->
                                <div
                                    class="mb-2 h-1.5 w-3/4 rounded bg-gray-400 sm:mb-2.5 sm:h-2 lg:mb-3 lg:h-2.5"
                                ></div>
                                <div
                                    class="mb-2 h-1.5 w-4/5 rounded bg-gray-400 sm:mb-2.5 sm:h-2 lg:mb-3 lg:h-2.5"
                                ></div>
                                <div
                                    class="mb-2 h-1.5 w-2/3 rounded bg-gray-400 sm:mb-2.5 sm:h-2 lg:mb-3 lg:h-2.5"
                                ></div>
                                <div
                                    class="mb-2 h-1.5 w-5/6 rounded bg-gray-400 sm:mb-2.5 sm:h-2 lg:mb-3 lg:h-2.5"
                                ></div>
                                <div
                                    class="mb-2 h-1.5 w-3/5 rounded bg-gray-400 sm:mb-2.5 sm:h-2 lg:mb-3 lg:h-2.5"
                                ></div>
                                <div
                                    class="h-1.5 w-4/5 rounded bg-gray-400 sm:h-2 lg:h-2.5"
                                ></div>
                            </div>
                        </div>

                        <!-- Person (stylized) -->
                        <div
                            class="relative -ml-6 z-10 flex items-end sm:-ml-8 lg:-ml-10"
                        >
                            <!-- Head -->
                            <div
                                class="h-10 w-10 rounded-full bg-gray-800 sm:h-12 sm:w-12 lg:h-14 lg:w-14"
                            ></div>
                            <!-- Body (dark blue shirt) -->
                            <div
                                class="absolute top-10 h-20 w-14 rounded-t-xl bg-blue-800 sm:top-12 sm:h-24 sm:w-16 lg:top-14 lg:h-28 lg:w-[4.5rem]"
                            >
                                <!-- Right arm extended towards phone -->
                                <div
                                    class="absolute -left-3 top-6 h-10 w-4 rotate-[-20deg] rounded-full bg-gray-700 sm:top-7 sm:h-12 sm:w-5 lg:top-8 lg:h-14 lg:w-6"
                                ></div>
                            </div>
                            <!-- Legs (gray pants) -->
                            <div
                                class="absolute top-[6.5rem] h-10 w-5 rounded-b-xl bg-gray-600 sm:top-[7.5rem] sm:h-12 sm:w-6 lg:top-[8.5rem] lg:h-14 lg:w-7"
                            ></div>
                        </div>

                        <!-- Search Icon (Purple Circle) -->
                        <div
                            class="absolute -left-6 top-4 z-20 flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-[#5B21B6] to-[#7C3AED] shadow-xl animate-bounce-slow sm:-left-8 sm:top-6 sm:h-14 sm:w-14 lg:-left-10 lg:top-8 lg:h-16 lg:w-16"
                        >
                            <Search
                                class="h-6 w-6 text-white sm:h-7 sm:w-7 lg:h-8 lg:w-8"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="relative border-t border-gray-200 bg-gradient-to-br from-[#5B21B6] to-[#7C3AED] py-16 sm:py-20 lg:py-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 gap-8 md:grid-cols-4">
                    <div class="text-center">
                        <div class="mb-2 text-4xl font-bold text-white sm:text-5xl lg:text-6xl">1000+</div>
                        <div class="text-sm font-medium text-purple-100 sm:text-base">Learning Resources</div>
                    </div>
                    <div class="text-center">
                        <div class="mb-2 text-4xl font-bold text-white sm:text-5xl lg:text-6xl">500+</div>
                        <div class="text-sm font-medium text-purple-100 sm:text-base">Active Students</div>
                    </div>
                    <div class="text-center">
                        <div class="mb-2 text-4xl font-bold text-white sm:text-5xl lg:text-6xl">50+</div>
                        <div class="text-sm font-medium text-purple-100 sm:text-base">Expert Teachers</div>
                    </div>
                    <div class="text-center">
                        <div class="mb-2 text-4xl font-bold text-white sm:text-5xl lg:text-6xl">95%</div>
                        <div class="text-sm font-medium text-purple-100 sm:text-base">Satisfaction Rate</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section
            class="relative border-t border-gray-200 bg-white py-16 sm:py-20 lg:py-24 animate-fade-in"
        >
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12 sm:mb-16 lg:mb-20 animate-slide-up">
                    <div class="mb-4 inline-flex items-center gap-2 rounded-full bg-purple-100 px-4 py-2 text-sm font-semibold text-purple-700">
                        <Sparkles class="h-4 w-4" />
                        <span>Powerful Features</span>
                    </div>
                    <h3
                        class="mb-4 text-3xl font-bold text-gray-900 sm:text-4xl lg:text-5xl"
                    >
                        Everything You Need to
                        <span class="bg-gradient-to-r from-[#5B21B6] to-[#7C3AED] bg-clip-text text-transparent">Succeed</span>
                    </h3>
                    <p
                        class="mx-auto max-w-2xl text-lg text-gray-600 sm:text-xl"
                    >
                        Comprehensive tools and intelligent features designed to enhance your learning journey
                    </p>
                </div>

                <div
                    class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3 lg:gap-12"
                >
                    <!-- Feature 1 -->
                    <div
                        class="group relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition-all duration-300 hover:border-purple-300 hover:shadow-xl hover:scale-105 hover:-translate-y-2 sm:p-8 animate-fade-in-up-delay-1"
                    >
                        <div class="absolute inset-0 bg-gradient-to-br from-purple-50 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
                        <div class="relative">
                            <div
                                class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-purple-100 to-purple-200 text-purple-600 transition-transform duration-300 group-hover:scale-110 group-hover:rotate-6 sm:h-14 sm:w-14"
                            >
                                <Brain class="h-6 w-6 sm:h-7 sm:w-7" />
                            </div>
                            <h4
                                class="mb-3 text-xl font-bold text-gray-900 sm:text-2xl"
                            >
                                Intelligent Recommendations
                            </h4>
                            <p
                                class="text-gray-600 leading-relaxed"
                            >
                                Advanced AI analyzes your learning patterns, preferences,
                                and progress to suggest the most relevant resources
                                tailored specifically for you.
                            </p>
                        </div>
                    </div>

                    <!-- Feature 2 -->
                    <div
                        class="group relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition-all duration-300 hover:border-purple-300 hover:shadow-xl hover:scale-105 hover:-translate-y-2 sm:p-8 animate-fade-in-up-delay-2"
                    >
                        <div class="absolute inset-0 bg-gradient-to-br from-purple-50 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
                        <div class="relative">
                            <div
                                class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-blue-100 to-blue-200 text-blue-600 transition-transform duration-300 group-hover:scale-110 group-hover:rotate-6 sm:h-14 sm:w-14"
                            >
                                <Target class="h-6 w-6 sm:h-7 sm:w-7" />
                            </div>
                            <h4
                                class="mb-3 text-xl font-bold text-gray-900 sm:text-2xl"
                            >
                                Personalized Learning Paths
                            </h4>
                            <p
                                class="text-gray-600 leading-relaxed"
                            >
                                Customized learning paths that dynamically adapt to your
                                pace, learning style, and educational goals for optimal results.
                            </p>
                        </div>
                    </div>

                    <!-- Feature 3 -->
                    <div
                        class="group relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition-all duration-300 hover:border-purple-300 hover:shadow-xl hover:scale-105 hover:-translate-y-2 sm:p-8 animate-fade-in-up-delay-3"
                    >
                        <div class="absolute inset-0 bg-gradient-to-br from-purple-50 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
                        <div class="relative">
                            <div
                                class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-yellow-100 to-yellow-200 text-yellow-600 transition-transform duration-300 group-hover:scale-110 group-hover:rotate-6 sm:h-14 sm:w-14"
                            >
                                <Sparkles class="h-6 w-6 sm:h-7 sm:w-7" />
                            </div>
                            <h4
                                class="mb-3 text-xl font-bold text-gray-900 sm:text-2xl"
                            >
                                AI Learning Assistant
                            </h4>
                            <p
                                class="text-gray-600 leading-relaxed"
                            >
                                Get instant help from our AI tutor. Ask questions, get explanations,
                                and receive personalized guidance 24/7.
                            </p>
                        </div>
                    </div>

                    <!-- Feature 4 -->
                    <div
                        class="group relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition-all duration-300 hover:border-purple-300 hover:shadow-xl hover:scale-105 hover:-translate-y-2 sm:p-8 animate-fade-in-up-delay-4"
                    >
                        <div class="absolute inset-0 bg-gradient-to-br from-purple-50 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
                        <div class="relative">
                            <div
                                class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-green-100 to-green-200 text-green-600 transition-transform duration-300 group-hover:scale-110 group-hover:rotate-6 sm:h-14 sm:w-14"
                            >
                                <BookOpen class="h-6 w-6 sm:h-7 sm:w-7" />
                            </div>
                            <h4
                                class="mb-3 text-xl font-bold text-gray-900 sm:text-2xl"
                            >
                                Vast Resource Library
                            </h4>
                            <p
                                class="text-gray-600 leading-relaxed"
                            >
                                Access a comprehensive collection of learning
                                materials across various subjects, difficulty
                                levels, and formats (videos, PDFs, quizzes).
                            </p>
                        </div>
                    </div>

                    <!-- Feature 5 -->
                    <div
                        class="group relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition-all duration-300 hover:border-purple-300 hover:shadow-xl hover:scale-105 hover:-translate-y-2 sm:p-8 animate-fade-in-up-delay-5"
                    >
                        <div class="absolute inset-0 bg-gradient-to-br from-purple-50 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
                        <div class="relative">
                            <div
                                class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-100 to-indigo-200 text-indigo-600 transition-transform duration-300 group-hover:scale-110 group-hover:rotate-6 sm:h-14 sm:w-14"
                            >
                                <BarChart3 class="h-6 w-6 sm:h-7 sm:w-7" />
                            </div>
                            <h4
                                class="mb-3 text-xl font-bold text-gray-900 sm:text-2xl"
                            >
                                Progress Tracking
                            </h4>
                            <p
                                class="text-gray-600 leading-relaxed"
                            >
                                Monitor your learning progress with detailed
                                analytics, insights, and performance metrics powered by AI.
                            </p>
                        </div>
                    </div>

                    <!-- Feature 6 -->
                    <div
                        class="group relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition-all duration-300 hover:border-purple-300 hover:shadow-xl hover:scale-105 hover:-translate-y-2 sm:p-8 animate-fade-in-up-delay-6"
                    >
                        <div class="absolute inset-0 bg-gradient-to-br from-purple-50 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
                        <div class="relative">
                            <div
                                class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-pink-100 to-pink-200 text-pink-600 transition-transform duration-300 group-hover:scale-110 group-hover:rotate-6 sm:h-14 sm:w-14"
                            >
                                <GraduationCap class="h-6 w-6 sm:h-7 sm:w-7" />
                            </div>
                            <h4
                                class="mb-3 text-xl font-bold text-gray-900 sm:text-2xl"
                            >
                                Teacher Dashboard
                            </h4>
                            <p
                                class="text-gray-600 leading-relaxed"
                            >
                                Comprehensive analytics and content management tools
                                for educators to track student engagement and performance.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- How It Works Section -->
        <section class="relative border-t border-gray-200 bg-gray-50 py-16 sm:py-20 lg:py-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12 sm:mb-16 lg:mb-20">
                    <div class="mb-4 inline-flex items-center gap-2 rounded-full bg-purple-100 px-4 py-2 text-sm font-semibold text-purple-700">
                        <Zap class="h-4 w-4" />
                        <span>Simple Process</span>
                    </div>
                    <h3 class="mb-4 text-3xl font-bold text-gray-900 sm:text-4xl lg:text-5xl">
                        How It <span class="bg-gradient-to-r from-[#5B21B6] to-[#7C3AED] bg-clip-text text-transparent">Works</span>
                    </h3>
                    <p class="mx-auto max-w-2xl text-lg text-gray-600 sm:text-xl">
                        Get started in three simple steps
                    </p>
                </div>

                <div class="grid gap-8 md:grid-cols-3">
                    <div class="text-center">
                        <div class="mb-6 mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-purple-100 to-purple-200 text-purple-600 sm:h-20 sm:w-20">
                            <span class="text-2xl font-bold sm:text-3xl">1</span>
                        </div>
                        <h4 class="mb-3 text-xl font-bold text-gray-900">Sign Up & Set Preferences</h4>
                        <p class="text-gray-600">Create your account and tell us about your learning style, subjects of interest, and goals.</p>
                    </div>
                    <div class="text-center">
                        <div class="mb-6 mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-blue-100 to-blue-200 text-blue-600 sm:h-20 sm:w-20">
                            <span class="text-2xl font-bold sm:text-3xl">2</span>
                        </div>
                        <h4 class="mb-3 text-xl font-bold text-gray-900">AI Analyzes & Recommends</h4>
                        <p class="text-gray-600">Our AI engine analyzes your profile and suggests personalized learning resources tailored to you.</p>
                    </div>
                    <div class="text-center">
                        <div class="mb-6 mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-green-100 to-green-200 text-green-600 sm:h-20 sm:w-20">
                            <span class="text-2xl font-bold sm:text-3xl">3</span>
                        </div>
                        <h4 class="mb-3 text-xl font-bold text-gray-900">Learn & Track Progress</h4>
                        <p class="text-gray-600">Access recommended content, interact with the AI assistant, and monitor your learning journey.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section
            class="relative border-t border-gray-200 bg-gradient-to-br from-[#5B21B6] via-[#7C3AED] to-[#A855F7] py-16 sm:py-20 lg:py-24 overflow-hidden"
        >
            <div class="absolute inset-0 bg-grid-pattern opacity-10"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
            <div class="relative mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
                <h3
                    class="mb-6 text-3xl font-bold text-white sm:text-4xl lg:text-5xl animate-slide-up"
                >
                    Ready to Transform Your Learning?
                </h3>
                <p
                    class="mb-8 text-lg text-purple-100 sm:mb-10 sm:text-xl lg:mb-12 lg:text-2xl animate-fade-in-delay"
                >
                    Join hundreds of students and teachers already experiencing personalized
                    education powered by AI at JPENHS
                </p>
                <Link
                    :href="nextRoute"
                    class="group inline-flex items-center gap-2 rounded-xl bg-white px-8 py-4 text-base font-semibold text-purple-600 shadow-2xl shadow-purple-900/50 transition-all duration-300 hover:bg-gray-50 hover:scale-110 active:scale-95 sm:text-lg"
                >
                    <span>Get Started Free</span>
                    <ArrowRight
                        class="h-5 w-5 transition-transform duration-300 group-hover:translate-x-2"
                    />
                </Link>
            </div>
        </section>

        <!-- Footer -->
        <footer
            class="border-t border-gray-200 bg-gray-900 py-12 text-gray-400 sm:py-16"
        >
            <div
                class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
            >
                <div class="grid grid-cols-1 gap-8 md:grid-cols-4 mb-8">
                    <div>
                        <div class="flex items-center gap-2 mb-4">
                            <img
                                src="/images/logo/logo.png"
                                alt="EduNexus JPENHS Logo"
                                class="h-8 w-auto"
                            />
                            <div>
                                <div class="text-white font-bold">EduNexus</div>
                                <div class="text-xs">JPENHS</div>
                            </div>
                        </div>
                        <p class="text-sm text-gray-400">
                            AI-powered learning platform for personalized education experiences.
                        </p>
                    </div>
                    <div>
                        <h5 class="text-white font-semibold mb-4">Platform</h5>
                        <ul class="space-y-2 text-sm">
                            <li><Link :href="nextRoute" class="hover:text-white transition-colors">Dashboard</Link></li>
                            <li><Link :href="login()" v-if="!user" class="hover:text-white transition-colors">Sign In</Link></li>
                            <li><a href="#" class="hover:text-white transition-colors">Features</a></li>
                        </ul>
                    </div>
                    <div>
                        <h5 class="text-white font-semibold mb-4">Resources</h5>
                        <ul class="space-y-2 text-sm">
                            <li><a href="#" class="hover:text-white transition-colors">Learning Library</a></li>
                            <li><a href="#" class="hover:text-white transition-colors">AI Assistant</a></li>
                            <li><a href="#" class="hover:text-white transition-colors">Help Center</a></li>
                        </ul>
                    </div>
                    <div>
                        <h5 class="text-white font-semibold mb-4">About</h5>
                        <ul class="space-y-2 text-sm">
                            <li><a href="#" class="hover:text-white transition-colors">About Us</a></li>
                            <li><a href="#" class="hover:text-white transition-colors">Contact</a></li>
                            <li><a href="#" class="hover:text-white transition-colors">Privacy Policy</a></li>
                        </ul>
                    </div>
                </div>
                <div class="border-t border-gray-800 pt-8 text-center">
                    <p class="text-sm">
                        © 2024 EduNexus JPENHS. All rights reserved.
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>
