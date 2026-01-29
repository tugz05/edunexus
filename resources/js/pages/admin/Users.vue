<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import BottomNav from '@/components/navigation/BottomNav.vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Users, Plus, Edit, Trash2, Search, GraduationCap, UserCog, Shield } from 'lucide-vue-next';
import { useMediaQuery } from '@vueuse/core';
import { type BreadcrumbItem } from '@/types';

interface User {
    id: number;
    name: string;
    email: string;
    role: 'student' | 'teacher' | 'admin';
    created_at: string;
}

const page = usePage();
const user = computed(() => page.props.auth?.user);
const userRole = computed(() => {
    return (user.value?.role as 'student' | 'teacher' | 'admin') || 'admin';
});
const isMobile = useMediaQuery('(max-width: 768px)');

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin Dashboard', href: '/admin/home' },
    { title: 'User Management', href: '/admin/users' },
];

const loading = ref(false);
const error = ref<string | null>(null);
const users = ref<User[]>([]);
const searchQuery = ref('');
const roleFilter = ref('');
const currentPage = ref(1);
const lastPage = ref(1);

// Modal states
const showUserModal = ref(false);
const editingUser = ref<User | null>(null);
const formData = ref({
    name: '',
    email: '',
    password: '',
    role: 'student' as 'student' | 'teacher' | 'admin',
});
const saving = ref(false);

const roleIcons = {
    student: GraduationCap,
    teacher: UserCog,
    admin: Shield,
};

const getRoleIcon = (role: string) => {
    return roleIcons[role as keyof typeof roleIcons] || Users;
};

const fetchUsers = async (pageNum = 1) => {
    loading.value = true;
    error.value = null;

    try {
        const params = new URLSearchParams();
        if (searchQuery.value.trim()) params.append('search', searchQuery.value.trim());
        if (roleFilter.value) params.append('role', roleFilter.value);
        params.append('page', pageNum.toString());

        const response = await fetch(`/api/admin/users?${params.toString()}`, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || 'Failed to load users');
        }

        const result = await response.json();
        users.value = result.data;
        currentPage.value = result.meta.current_page;
        lastPage.value = result.meta.last_page;
    } catch (err: any) {
        error.value = err.message || 'Failed to load users';
    } finally {
        loading.value = false;
    }
};

const openCreateModal = () => {
    editingUser.value = null;
    formData.value = { name: '', email: '', password: '', role: 'student' };
    showUserModal.value = true;
};

const openEditModal = (user: User) => {
    editingUser.value = user;
    formData.value = { name: user.name, email: user.email, password: '', role: user.role };
    showUserModal.value = true;
};

const saveUser = async () => {
    saving.value = true;
    error.value = null;

    try {
        const metaToken = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = metaToken?.getAttribute('content') || '';
        if (!csrfToken) {
            throw new Error('CSRF token not found. Please refresh the page and try again.');
        }

        const url = editingUser.value
            ? `/api/admin/users/${editingUser.value.id}`
            : '/api/admin/users';
        const method = editingUser.value ? 'PUT' : 'POST';

        const body: any = {
            _token: csrfToken,
            name: formData.value.name,
            email: formData.value.email,
            role: formData.value.role,
        };
        if (formData.value.password) {
            body.password = formData.value.password;
        }

        const response = await fetch(url, {
            method,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify(body),
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || 'Failed to save user');
        }

        showUserModal.value = false;
        fetchUsers(currentPage.value);
    } catch (err: any) {
        error.value = err.message || 'Failed to save user';
    } finally {
        saving.value = false;
    }
};

const handleDelete = async (id: number) => {
    if (!confirm('Are you sure you want to delete this user?')) return;

    try {
        const metaToken = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = metaToken?.getAttribute('content') || '';
        if (!csrfToken) {
            error.value = 'CSRF token not found. Please refresh the page and try again.';
            return;
        }
        const response = await fetch(`/api/admin/users/${id}`, {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || 'Failed to delete user');
        }

        fetchUsers(currentPage.value);
    } catch (err: any) {
        error.value = err.message || 'Failed to delete user';
    }
};

const handleSearch = () => {
    currentPage.value = 1;
    fetchUsers(1);
};

onMounted(() => {
    fetchUsers();
});
</script>

<template>
    <Head title="User Management" />
    
    <!-- Mobile Layout -->
    <template v-if="isMobile">
        <div class="min-h-screen bg-gray-100 pt-16 pb-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-brand-primary">User Management</h1>
                        <p class="mt-2 text-gray-600">Manage all users</p>
                    </div>
                    <Button @click="openCreateModal" class="bg-brand-primary">
                        <Plus class="mr-2 h-4 w-4" />
                        Add User
                    </Button>
                </div>

                <!-- Search -->
                <div class="mb-4">
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
                        <Input
                            v-model="searchQuery"
                            placeholder="Search users..."
                            class="w-full pl-10"
                            @keyup.enter="handleSearch"
                        />
                    </div>
                </div>

                <!-- Role Filter -->
                <div class="mb-4">
                    <select
                        v-model="roleFilter"
                        @change="handleSearch"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option value="">All Roles</option>
                        <option value="student">Student</option>
                        <option value="teacher">Teacher</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <!-- Error -->
                <div v-if="error" class="mb-4 rounded-lg bg-red-50 p-4 text-red-800">
                    {{ error }}
                </div>

                <!-- Users List -->
                <div v-if="loading" class="text-center py-12 text-gray-500">Loading...</div>
                <div v-else-if="users.length > 0" class="space-y-4">
                    <div
                        v-for="userItem in users"
                        :key="userItem.id"
                        class="rounded-lg bg-white p-4 shadow-sm"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3 flex-1">
                                <component :is="getRoleIcon(userItem.role)" class="h-8 w-8 text-brand-primary" />
                                <div>
                                    <h3 class="font-semibold text-gray-900">{{ userItem.name }}</h3>
                                    <p class="text-sm text-gray-600">{{ userItem.email }}</p>
                                    <span class="mt-1 inline-block rounded-full bg-gray-100 px-2 py-0.5 text-xs capitalize text-gray-700">
                                        {{ userItem.role }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <Button @click="openEditModal(userItem)" variant="outline" size="icon">
                                    <Edit class="h-4 w-4" />
                                </Button>
                                <Button
                                    @click="handleDelete(userItem.id)"
                                    variant="outline"
                                    size="icon"
                                    class="text-red-600 hover:text-red-700"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="rounded-lg bg-white p-12 text-center">
                    <Users class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                    <p class="text-gray-600">No users found</p>
                </div>

                <!-- Pagination -->
                <div v-if="lastPage > 1" class="mt-6 flex justify-center gap-2">
                    <Button @click="fetchUsers(currentPage - 1)" :disabled="currentPage === 1" variant="outline">
                        Previous
                    </Button>
                    <span class="flex items-center px-4 text-sm text-gray-600">
                        Page {{ currentPage }} of {{ lastPage }}
                    </span>
                    <Button @click="fetchUsers(currentPage + 1)" :disabled="currentPage === lastPage" variant="outline">
                        Next
                    </Button>
                </div>
            </div>
        </div>
        <BottomNav :role="userRole" />
    </template>

    <!-- Desktop Layout -->
    <AppLayout v-else :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
            <div class="mx-auto w-full max-w-7xl">
                <!-- Header -->
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-brand-primary">User Management</h1>
                        <p class="mt-2 text-gray-600">Manage all users</p>
                    </div>
                    <Button @click="openCreateModal" class="bg-brand-primary">
                        <Plus class="mr-2 h-4 w-4" />
                        Add User
                    </Button>
                </div>

                <!-- Search & Filter -->
                <div class="mb-6 flex gap-4">
                    <div class="relative flex-1">
                        <Search class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
                        <Input
                            v-model="searchQuery"
                            placeholder="Search users..."
                            class="w-full pl-10"
                            @keyup.enter="handleSearch"
                        />
                    </div>
                    <select
                        v-model="roleFilter"
                        @change="handleSearch"
                        class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option value="">All Roles</option>
                        <option value="student">Student</option>
                        <option value="teacher">Teacher</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <!-- Error -->
                <div v-if="error" class="mb-4 rounded-lg bg-red-50 p-4 text-red-800">
                    {{ error }}
                </div>

                <!-- Users Table -->
                <div v-if="loading" class="text-center py-12 text-gray-500">Loading...</div>
                <div v-else-if="users.length > 0" class="rounded-lg bg-white shadow-sm overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="userItem in users" :key="userItem.id">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <component :is="getRoleIcon(userItem.role)" class="h-6 w-6 text-brand-primary" />
                                        <span class="font-medium text-gray-900">{{ userItem.name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-600">{{ userItem.email }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="rounded-full bg-gray-100 px-2 py-1 text-xs capitalize text-gray-700">
                                        {{ userItem.role }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex gap-2">
                                        <Button @click="openEditModal(userItem)" variant="outline" size="sm">
                                            <Edit class="mr-2 h-4 w-4" />
                                            Edit
                                        </Button>
                                        <Button
                                            @click="handleDelete(userItem.id)"
                                            variant="outline"
                                            size="sm"
                                            class="text-red-600 hover:text-red-700"
                                        >
                                            <Trash2 class="mr-2 h-4 w-4" />
                                            Delete
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="rounded-lg bg-white p-12 text-center">
                    <Users class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                    <p class="text-gray-600">No users found</p>
                </div>

                <!-- Pagination -->
                <div v-if="lastPage > 1" class="mt-6 flex justify-center gap-2">
                    <Button @click="fetchUsers(currentPage - 1)" :disabled="currentPage === 1" variant="outline">
                        Previous
                    </Button>
                    <span class="flex items-center px-4 text-sm text-gray-600">
                        Page {{ currentPage }} of {{ lastPage }}
                    </span>
                    <Button @click="fetchUsers(currentPage + 1)" :disabled="currentPage === lastPage" variant="outline">
                        Next
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>

    <!-- User Modal -->
    <Dialog :open="showUserModal" @update:open="showUserModal = $event">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ editingUser ? 'Edit User' : 'Create User' }}</DialogTitle>
                <DialogDescription>
                    {{ editingUser ? 'Update user information' : 'Add a new user to the system' }}
                </DialogDescription>
            </DialogHeader>
            <div class="space-y-4 py-4">
                <div>
                    <Label for="name">Name</Label>
                    <Input id="name" v-model="formData.name" class="mt-1" />
                </div>
                <div>
                    <Label for="email">Email</Label>
                    <Input id="email" v-model="formData.email" type="email" class="mt-1" />
                </div>
                <div>
                    <Label for="password">{{ editingUser ? 'New Password (leave blank to keep current)' : 'Password' }}</Label>
                    <Input id="password" v-model="formData.password" type="password" class="mt-1" />
                </div>
                <div>
                    <Label for="role">Role</Label>
                    <select
                        id="role"
                        v-model="formData.role"
                        class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option value="student">Student</option>
                        <option value="teacher">Teacher</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="showUserModal = false">Cancel</Button>
                <Button @click="saveUser" :disabled="saving" class="bg-brand-primary">
                    {{ saving ? 'Saving...' : editingUser ? 'Update' : 'Create' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
