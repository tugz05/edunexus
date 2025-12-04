<script setup lang="ts">
import { computed } from 'vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import {
    Home,
    BookOpen,
    Sparkles,
    MessageCircle,
    Bookmark,
    FolderKanban,
    Plus,
    BarChart3,
} from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const userRole = computed(() => {
    return (user.value?.role as 'student' | 'teacher') || 'student';
});

// Student navigation items
const studentNavItems: NavItem[] = [
    {
        title: 'Home',
        href: dashboard(),
        icon: Home,
    },
    {
        title: 'Content',
        href: '/student/content',
        icon: BookOpen,
    },
    {
        title: 'AI Recommendations',
        href: '/student/recommendations',
        icon: Sparkles,
    },
    {
        title: 'AI Assistant',
        href: '/student/assistant',
        icon: MessageCircle,
    },
    {
        title: 'Saved',
        href: '/student/saved',
        icon: Bookmark,
    },
];

// Teacher navigation items
const teacherNavItems: NavItem[] = [
    {
        title: 'Home',
        href: dashboard(),
        icon: Home,
    },
    {
        title: 'Content Manager',
        href: '/teacher/content',
        icon: FolderKanban,
    },
    {
        title: 'Add Content',
        href: '/teacher/content/create',
        icon: Plus,
    },
    {
        title: 'AI Assistant',
        href: '/teacher/assistant',
        icon: MessageCircle,
    },
    {
        title: 'Analytics',
        href: '/teacher/analytics',
        icon: BarChart3,
    },
];

const mainNavItems = computed(() => {
    return userRole.value === 'student' ? studentNavItems : teacherNavItems;
});

// Footer items can remain empty or be removed
const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
