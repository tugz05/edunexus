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
                        alt="EduNexusLogo"
                        class="h-8 w-auto sm:h-10 lg:h-12 animate-fade-in"
                    />
                    <span
                        class="text-xl font-bold text-gray-800 sm:text-2xl animate-fade-in-delay"
                    >
                        EduNexus
                    </span>
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
                        class="rounded-lg bg-orange-500 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-orange-600"
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
                class="absolute left-1/2 top-1/4 -z-10 h-96 w-96 -translate-x-1/2 rounded-full bg-orange-400 opacity-20 blur-3xl animate-pulse-slow sm:h-[32rem] sm:w-[32rem] lg:h-[40rem] lg:w-[40rem]"
            ></div>
            <div
                class="absolute right-1/4 top-1/2 -z-10 h-64 w-64 rounded-full bg-purple-400 opacity-15 blur-3xl animate-float sm:h-80 sm:w-80 lg:h-96 lg:w-96"
            ></div>

            <div class="relative z-10 mx-auto w-full max-w-6xl">
                <!-- Main Hero Content -->
                <div
                    class="mb-12 text-center sm:mb-16 lg:mb-20"
                >
                    <!-- AI Badge -->
                    <div
                        class="mb-6 inline-flex items-center gap-2 rounded-full bg-orange-100 px-4 py-2 text-sm font-semibold text-orange-700 sm:text-base animate-fade-in-up"
                    >
                        <Brain class="h-4 w-4 sm:h-5 sm:w-5 animate-pulse" />
                        <span>AI-Powered Technology</span>
                    </div>

                    <!-- Main Headline -->
                    <h2
                        class="mb-6 text-4xl font-bold leading-tight sm:mb-8 sm:text-5xl lg:mb-10 lg:text-6xl"
                    >
                        <span class="block text-gray-900 animate-fade-in-up-delay-1"
                            >AI-Powered Content</span
                        >
                        <span
                            class="block bg-gradient-to-r from-orange-500 to-orange-600 bg-clip-text text-transparent animate-fade-in-up-delay-2 animate-gradient-shift"
                        >
                            Recommendation Systems
                        </span>
                        <span class="block text-gray-900 animate-fade-in-up-delay-3"
                            >for Personalized Learning Resources</span
                        >
                    </h2>

                    <!-- Subheadline -->
                    <p
                        class="mx-auto mb-8 max-w-2xl text-lg text-gray-600 sm:mb-10 sm:text-xl lg:mb-12 lg:text-2xl animate-fade-in-up-delay-4"
                    >
                        Discover tailored educational content that adapts to your
                        learning style, pace, and goals. Experience the future of
                        personalized education.
                    </p>

                    <!-- CTA Buttons -->
                    <div
                        class="flex flex-col items-center justify-center gap-4 sm:flex-row animate-fade-in-up-delay-5"
                    >
                        <Link
                            :href="nextRoute"
                            class="group flex items-center gap-2 rounded-lg bg-orange-500 px-8 py-4 text-base font-semibold text-white shadow-lg transition-all duration-300 hover:bg-orange-600 hover:shadow-xl hover:scale-110 active:scale-95 animate-pulse-subtle sm:text-lg"
                        >
                            <span>Explore Resources</span>
                            <ArrowRight
                                class="h-5 w-5 transition-transform duration-300 group-hover:translate-x-2"
                            />
                        </Link>
                        <Link
                            :href="login()"
                            v-if="!user"
                            class="rounded-lg border-2 border-gray-300 bg-white px-8 py-4 text-base font-semibold text-gray-700 transition-all duration-300 hover:border-gray-400 hover:bg-gray-50 hover:scale-105 sm:text-lg"
                        >
                            Learn More
                        </Link>
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

                        <!-- Search Icon (Orange Circle) -->
                        <div
                            class="absolute -left-6 top-4 z-20 flex h-12 w-12 items-center justify-center rounded-full bg-orange-500 shadow-xl animate-bounce-slow sm:-left-8 sm:top-6 sm:h-14 sm:w-14 lg:-left-10 lg:top-8 lg:h-16 lg:w-16"
                        >
                            <Search
                                class="h-6 w-6 text-white animate-pulse sm:h-7 sm:w-7 lg:h-8 lg:w-8"
                            />
                        </div>
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
                    <h3
                        class="mb-4 text-3xl font-bold text-gray-900 sm:text-4xl lg:text-5xl"
                    >
                        Why Choose Our
                        <span class="text-orange-500 animate-pulse-subtle">AI System</span>?
                    </h3>
                    <p
                        class="mx-auto max-w-2xl text-lg text-gray-600 sm:text-xl"
                    >
                        Experience personalized learning like never before with
                        our advanced AI-powered recommendation engine
                    </p>
                </div>

                <div
                    class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3 lg:gap-12"
                >
                    <!-- Feature 1 -->
                    <div
                        class="group rounded-2xl border border-gray-200 bg-gray-50 p-6 transition-all duration-300 hover:border-orange-300 hover:shadow-lg hover:scale-105 hover:-translate-y-2 sm:p-8 animate-fade-in-up-delay-1"
                    >
                        <div
                            class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-orange-100 text-orange-600 transition-transform duration-300 group-hover:scale-110 group-hover:rotate-6 sm:h-14 sm:w-14"
                        >
                            <Brain class="h-6 w-6 sm:h-7 sm:w-7 animate-pulse-subtle" />
                        </div>
                        <h4
                            class="mb-3 text-xl font-bold text-gray-900 sm:text-2xl"
                        >
                            Intelligent Recommendations
                        </h4>
                        <p
                            class="text-gray-600"
                        >
                            Our AI analyzes your learning patterns, preferences,
                            and progress to suggest the most relevant resources
                            tailored just for you.
                        </p>
                    </div>

                    <!-- Feature 2 -->
                    <div
                        class="group rounded-2xl border border-gray-200 bg-gray-50 p-6 transition-all duration-300 hover:border-orange-300 hover:shadow-lg hover:scale-105 hover:-translate-y-2 sm:p-8 animate-fade-in-up-delay-2"
                    >
                        <div
                            class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-orange-100 text-orange-600 transition-transform duration-300 group-hover:scale-110 group-hover:rotate-6 sm:h-14 sm:w-14"
                        >
                            <Target class="h-6 w-6 sm:h-7 sm:w-7" />
                        </div>
                        <h4
                            class="mb-3 text-xl font-bold text-gray-900 sm:text-2xl"
                        >
                            Personalized Learning Paths
                        </h4>
                        <p
                            class="text-gray-600"
                        >
                            Get customized learning paths that adapt to your
                            pace, learning style, and educational goals.
                        </p>
                    </div>

                    <!-- Feature 3 -->
                    <div
                        class="group rounded-2xl border border-gray-200 bg-gray-50 p-6 transition-all duration-300 hover:border-orange-300 hover:shadow-lg hover:scale-105 hover:-translate-y-2 sm:p-8 animate-fade-in-up-delay-3"
                    >
                        <div
                            class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-orange-100 text-orange-600 transition-transform duration-300 group-hover:scale-110 group-hover:rotate-6 sm:h-14 sm:w-14"
                        >
                            <Sparkles class="h-6 w-6 sm:h-7 sm:w-7 animate-pulse-subtle" />
                        </div>
                        <h4
                            class="mb-3 text-xl font-bold text-gray-900 sm:text-2xl"
                        >
                            Continuous Improvement
                        </h4>
                        <p
                            class="text-gray-600"
                        >
                            The system learns from your interactions, constantly
                            refining recommendations to better serve your
                            learning journey.
                        </p>
                    </div>

                    <!-- Feature 4 -->
                    <div
                        class="group rounded-2xl border border-gray-200 bg-gray-50 p-6 transition-all duration-300 hover:border-orange-300 hover:shadow-lg hover:scale-105 hover:-translate-y-2 sm:p-8 animate-fade-in-up-delay-4"
                    >
                        <div
                            class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-orange-100 text-orange-600 transition-transform duration-300 group-hover:scale-110 group-hover:rotate-6 sm:h-14 sm:w-14"
                        >
                            <BookOpen class="h-6 w-6 sm:h-7 sm:w-7" />
                        </div>
                        <h4
                            class="mb-3 text-xl font-bold text-gray-900 sm:text-2xl"
                        >
                            Vast Resource Library
                        </h4>
                        <p
                            class="text-gray-600"
                        >
                            Access a comprehensive collection of learning
                            materials across various subjects and difficulty
                            levels.
                        </p>
                    </div>

                    <!-- Feature 5 -->
                    <div
                        class="group rounded-2xl border border-gray-200 bg-gray-50 p-6 transition-all duration-300 hover:border-orange-300 hover:shadow-lg hover:scale-105 hover:-translate-y-2 sm:p-8 animate-fade-in-up-delay-5"
                    >
                        <div
                            class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-orange-100 text-orange-600 transition-transform duration-300 group-hover:scale-110 group-hover:rotate-6 sm:h-14 sm:w-14"
                        >
                            <TrendingUp class="h-6 w-6 sm:h-7 sm:w-7" />
                        </div>
                        <h4
                            class="mb-3 text-xl font-bold text-gray-900 sm:text-2xl"
                        >
                            Progress Tracking
                        </h4>
                        <p
                            class="text-gray-600"
                        >
                            Monitor your learning progress with detailed
                            analytics and insights powered by AI.
                        </p>
                    </div>

                    <!-- Feature 6 -->
                    <div
                        class="group rounded-2xl border border-gray-200 bg-gray-50 p-6 transition-all duration-300 hover:border-orange-300 hover:shadow-lg hover:scale-105 hover:-translate-y-2 sm:p-8 animate-fade-in-up-delay-6"
                    >
                        <div
                            class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-orange-100 text-orange-600 transition-transform duration-300 group-hover:scale-110 group-hover:rotate-6 sm:h-14 sm:w-14"
                        >
                            <Users class="h-6 w-6 sm:h-7 sm:w-7" />
                        </div>
                        <h4
                            class="mb-3 text-xl font-bold text-gray-900 sm:text-2xl"
                        >
                            Collaborative Learning
                        </h4>
                        <p
                            class="text-gray-600"
                        >
                            Connect with peers and discover resources recommended
                            based on successful learning paths of similar
                            learners.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section
            class="relative border-t border-gray-200 bg-gradient-to-br from-orange-500 to-orange-600 py-16 sm:py-20 lg:py-24 animate-fade-in"
        >
            <div class="mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
                <h3
                    class="mb-6 text-3xl font-bold text-white sm:text-4xl lg:text-5xl animate-slide-up"
                >
                    Ready to Transform Your Learning?
                </h3>
                <p
                    class="mb-8 text-lg text-orange-50 sm:mb-10 sm:text-xl lg:mb-12 lg:text-2xl animate-fade-in-delay"
                >
                    Join thousands of learners already experiencing personalized
                    education powered by AI
                </p>
                <Link
                    :href="nextRoute"
                    class="group inline-flex items-center gap-2 rounded-lg bg-white px-8 py-4 text-base font-semibold text-orange-600 shadow-xl transition-all duration-300 hover:bg-gray-50 hover:scale-110 active:scale-95 animate-pulse-subtle sm:text-lg"
                >
                    <span>Get Started Now</span>
                    <ArrowRight
                        class="h-5 w-5 transition-transform duration-300 group-hover:translate-x-2"
                    />
                </Link>
            </div>
        </section>

        <!-- Footer -->
        <footer
            class="border-t border-gray-200 bg-gray-900 py-8 text-gray-400 sm:py-10"
        >
            <div
                class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8"
            >
                <p class="text-sm sm:text-base">
                    © 2024 EduNexus. All rights reserved.
                </p>
            </div>
        </footer>
    </div>
</template>
